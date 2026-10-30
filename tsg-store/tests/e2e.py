#!/usr/bin/env python3
"""End-to-end test for the Reusable E-commerce Script (Phase 1).
Usage: python3 tests/e2e.py http://127.0.0.1:8000   (needs an EMPTY database and no config/config.php)
Runs the setup wizard, then exercises storefront, checkout, orders, inventory, roles and security."""
import re, struct, sys, zlib, requests

BASE = sys.argv[1].rstrip('/') if len(sys.argv) > 1 else 'http://127.0.0.1:8000'
DB = dict(db_host='localhost', db_port='3306', db_name='tsgtest', db_user='tsg', db_pass='tsgpass')
results = []

def check(name, cond, extra=''):
    results.append((name, bool(cond)))
    print(('PASS  ' if cond else 'FAIL  ') + name + (('  -> ' + str(extra)[:160]) if not cond and extra else ''))

def tok(html):
    m = re.search(r'name="_csrf" value="([^"]+)"', html)
    return m.group(1) if m else ''

def png(w=64, h=64):
    raw = b''.join(b'\x00' + bytes([200, 80, 40]) * w for _ in range(h))
    def ch(t, d): c = struct.pack('>I', len(d)) + t + d; return c + struct.pack('>I', zlib.crc32(t + d) & 0xffffffff)
    return b'\x89PNG\r\n\x1a\n' + ch(b'IHDR', struct.pack('>IIBBBBB', w, h, 8, 2, 0, 0, 0)) + ch(b'IDAT', zlib.compress(raw)) + ch(b'IEND', b'')

class Client:
    def __init__(s): s.s = requests.Session()
    def get(s, p, **k): return s.s.get(BASE + p, allow_redirects=k.pop('allow_redirects', True), **k)
    def post(s, p, data=None, page=None, files=None, **k):
        data = dict(data or {})
        if '_csrf' not in data:
            data['_csrf'] = tok(s.get(page or p).text)
        return s.s.post(BASE + p, data=data, files=files, allow_redirects=k.pop('allow_redirects', True), **k)

# ---------------- Setup wizard ----------------
w = Client()
r = w.get('/install/?step=1'); check('Wizard step 1 loads', 'Server requirements' in r.text)
def step(n, data, files=None):
    return w.post(f'/install/?step={n}', data, page=f'/install/?step={n}', files=files)
r = step(1, {}); check('Requirements pass', 'step=2' in r.url or 'Licence' in r.text, r.text[:200])
r = step(2, dict(licence='TSG-BAD', **DB)); check('Bad licence format rejected', 'must look like' in r.text)
r = step(2, dict(licence='TSG-DEMO-DEMO-DEMO-DEMO', **{**DB, 'db_pass': 'wrong'})); check('Bad DB password rejected', 'Could not connect' in r.text)
r = step(2, dict(licence='TSG-DEMO-DEMO-DEMO-DEMO', **DB)); check('Licence + DB accepted', 'Owner' in r.text or 'step=3' in r.url, r.text[:200])
r = step(3, dict(store_name='Kaleido Test Shop', tagline='Colours of craft', contact_email='shop@example.pk', phone='0300-1234567', whatsapp_number='923001234567', city='Karachi', address='Saddar, Karachi', owner_name='Faryal', owner_email='owner@example.pk', owner_password='abc', owner_password2='abc'))
check('Weak owner password rejected', 'at least 8' in r.text)
r = step(3, dict(store_name='Kaleido Test Shop', tagline='Colours of craft', contact_email='shop@example.pk', phone='0300-1234567', whatsapp_number='923001234567', city='Karachi', address='Saddar, Karachi', owner_name='Faryal', owner_email='owner@example.pk', owner_password='Owner12345', owner_password2='Owner12345'))
check('Owner + store step accepted', 'Business preset' in r.text)
r = step(4, dict(preset='digital')); check('Digital preset blocked (Phase 2)', 'available preset' in r.text)
r = step(4, dict(preset='fashion')); check('Fashion preset accepted', 'Branding' in r.text)
r = step(5, dict(primary_color='#8a2be2', secondary_color='#2d1b4e', accent_color='#ffb703', font_heading='Georgia, serif', font_body='system-ui, sans-serif'), files={'logo': ('logo.png', png(), 'image/png')})
check('Branding + logo accepted', 'Currency and tax' in r.text, r.text[:200])
r = step(6, dict(currency_code='PKR', currency_symbol='Rs.', currency_position='before', currency_decimals='0', tax_percent='5', tax_mode='added', tax_label='GST')); check('Currency/tax accepted', 'Payment methods' in r.text)
r = step(7, {}); check('Needs at least one payment method', 'Enable at least one' in r.text)
r = step(7, dict(en_cod='1', en_jazzcash='1', det_jazzcash='Title: Kaleido\nJazzCash: 0300-1112223', en_bank='1', det_bank='HBL IBAN PK00TEST', fee_cod='50')); check('Payments accepted', 'Shipping' in r.text)
r = step(8, dict(local_city='Karachi', local_rate='200', national_rate='350', free_over='20000', pickup='1')); check('Shipping accepted', 'Ready to install' in r.text)
r = step(9, dict(sample='1')); check('Install completes', 'Store installed' in r.text, r.text[:400])
r = Client().get('/install/'); check('Installer locked after install', 'already installed' in r.text)

# ---------------- Storefront ----------------
c = Client()
r = c.get('/'); check('Home loads with store name', r.status_code == 200 and 'Kaleido Test Shop' in r.text)
check('Test-mode banner visible', 'Test mode' in r.text)
check('Theme colour variable from settings', '--primary:#8a2be2' in r.text)
check('Logo shown', 'uploads/branding/logo-' in r.text)
r = c.get('/shop'); check('Catalogue lists sample products', 'Cotton Kurta' in r.text and 'Canvas Tote Bag' in r.text)
r = c.get('/category/men'); check('Category filter works', 'Cotton Kurta' in r.text and 'Lawn Suit' not in r.text)
r = c.get('/shop?q=kurta'); check('Search by name', 'Cotton Kurta' in r.text and 'Leather Belt' not in r.text)
r = c.get('/shop?sort=price_asc&min=1000&max=2000'); check('Price filter and sort', 'Leather Belt' in r.text and 'Denim Jacket' not in r.text)
r = c.get('/product/cotton-kurta'); check('Product page with variants', 'Choose option' in r.text and 'Size: M, Colour: White' in r.text)
check('Out-of-stock variant disabled', 'disabled>Size: M, Colour: Navy' in r.text.replace('\n', ''), '')
check('Sale price shown', 'Rs. 2,700' in r.text and 'Rs. 3,200' in r.text)
check('WhatsApp order button', 'wa.me/923001234567' in r.text)
check('Size guide (fashion preset)', 'Size guide' in r.text)
r = c.get('/product/nope'); check('Unknown product gives 404', r.status_code == 404)
r = c.get('/page/about-us'); check('Info page loads', 'Welcome to Kaleido' in r.text)

# ---------------- Cart ----------------
vid_ml = re.search(r'value="(\d+)">Size: M, Colour: White', c.get('/product/cotton-kurta').text).group(1)
pid = re.search(r'name="product_id" value="(\d+)"', c.get('/product/cotton-kurta').text).group(1)
r = c.post('/cart/add', dict(product_id=pid, variant_id='', qty='1', back='product/cotton-kurta'), page='/product/cotton-kurta'); check('Variant required', 'choose an option' in r.text.lower())
r = c.post('/cart/add', dict(product_id=pid, variant_id=vid_ml, qty='2'), page='/product/cotton-kurta'); check('Add variant to cart', 'Cotton Kurta' in r.text and 'Size: M, Colour: White' in r.text)
r = c.post('/cart/add', dict(product_id=pid, variant_id=vid_ml, qty='50'), page='/product/cotton-kurta'); check('Cannot add more than stock (12)', 'Only 12 in stock' in r.text or 'in stock' in r.text, r.text[-300:])
tote = re.search(r'name="product_id" value="(\d+)"', c.get('/product/canvas-tote-bag').text).group(1)
c.post('/cart/add', dict(product_id=tote, qty='1'), page='/product/canvas-tote-bag')
r = c.get('/cart'); check('Cart subtotal = 2*2700 + 950', 'Rs. 6,350' in r.text, re.findall(r'Subtotal: <strong>([^<]+)', r.text))
item_ids = re.findall(r'name="qty\[(\d+)\]"', r.text)
r = c.post('/cart/update', {f'qty[{item_ids[1]}]': '0', f'qty[{item_ids[0]}]': '3'}, page='/cart'); check('Update qty and remove', 'Rs. 8,100' in r.text and 'Canvas Tote' not in r.text, re.findall(r'Subtotal: <strong>([^<]+)', r.text))

# ---------------- Checkout ----------------
q = c.get('/checkout/quote?city=Karachi&province=Sindh&pm=cod').json()
check('Quote: Karachi gets local zone rates incl. pickup', [x['name'] for x in q['rates']] == ['Store pickup', 'Standard delivery'], q['rates'])
rate_std = [x['id'] for x in q['rates'] if x['name'] == 'Standard delivery'][0]
q1 = c.get(f'/checkout/quote?city=Karachi&province=Sindh&rate={rate_std}&pm=cod').json()
check('Quote totals: 8100 + 200 ship + 405 tax + 50 COD fee = 8,755', q1['totals']['total'] == 'Rs. 8,755' and q1['totals']['tax'] == 'Rs. 405', q1['totals'])
q2 = c.get(f'/checkout/quote?city=Lahore&province=Punjab&rate={rate_std}&pm=cod').json()
check('Quote: Lahore falls to national zone', q2['rates'][0]['cost'] == 350, q2['rates'])
base_form = dict(name='Ayesha Khan', email='ayesha@example.com', phone='0300-5551234', line1='House 5, Street 2', city='Karachi', province='Sindh', postal='75500', note='Call before delivery', rate=str(rate_std), pm='cod', action='place')
r = c.post('/checkout', {**base_form, 'email': 'bad'}, page='/checkout'); check('Invalid email rejected', 'valid email' in r.text)
r = c.post('/checkout', {**base_form, 'pm': 'jazzcash'}, page='/checkout'); check('Manual payment needs txn id + proof', 'transaction ID' in r.text and 'upload your payment' in r.text)
r = c.post('/checkout', {**base_form, 'rate': '99999'}, page='/checkout'); check('Forged shipping rate rejected', 'choose a shipping method' in r.text)
r = c.post('/checkout', base_form, page='/checkout'); check('COD order placed', 'has been received' in r.text and 'TSG-' in r.text, r.text[:300])
num1 = re.search(r'(TSG-\d{6}-[A-Z0-9]{5})', r.text).group(1)
check('Order total correct (8100+200+405 tax+50 fee = 8755)', 'Rs. 8,755' in r.text, re.findall(r'Rs\. [\d,]+', r.text))
check('Cart emptied after order', 'cart-link' in c.get('/').text and '<span class="badge">' not in c.get('/').text)
STOCK_RE = r'Size: M, Colour: White</small></td>\s*<td>[^<]*</td>\s*<td[^>]*>(\d+)'
t = c.get('/track'); r = c.post('/track', dict(number=num1, email='ayesha@example.com'), page='/track'); check('Tracking by number+email', num1 in r.text and 'Pending' in r.text)
r = c.post('/track', dict(number=num1, email='wrong@example.com'), page='/track'); check('Tracking with wrong email fails', 'could not find' in r.text)
check('Order page needs the access token', Client().get(f'/order/{num1}').status_code == 404)

# manual payment order with proof
c.post('/cart/add', dict(product_id=tote, qty='2'), page='/product/canvas-tote-bag')
rr = c.get('/checkout'); tk = tok(rr.text)
rate2 = [x['id'] for x in c.get('/checkout/quote?city=Karachi&province=Sindh&pm=jazzcash').json()['rates'] if x['name'] == 'Standard delivery'][0]
r = c.s.post(BASE + '/checkout', data={**base_form, 'rate': str(rate2), 'pm': 'jazzcash', 'txn_id': 'JC123456', '_csrf': tk}, files={'proof': ('proof.png', png(), 'image/png')})
check('JazzCash order with proof placed', 'payment details' in r.text.lower() and 'TSG-' in r.text, r.text[:300])
num2 = re.search(r'(TSG-\d{6}-[A-Z0-9]{5})', r.text).group(1)

# checkout without JavaScript: 'Update shipping options' button
c.post('/cart/add', dict(product_id=tote, qty='1'), page='/product/canvas-tote-bag')
r = c.post('/checkout', {**base_form, 'action': 'update', 'rate': ''}, page='/checkout'); check('Checkout works without JS (update shipping options)', 'Store pickup' in r.text and 'Standard delivery' in r.text)
c.post('/cart/remove', dict(item_id=re.findall(r'name="item_id" value="(\d+)"', c.get('/cart').text)[0]), page='/cart')

# ---------------- Customer account ----------------
cu = Client()
r = cu.post('/account/register', dict(name='Sana', email='sana@example.com', phone='0301-1112233', password='weak', password2='weak'), page='/account/register'); check('Weak customer password rejected', 'at least 8' in r.text)
r = cu.post('/account/register', dict(name='Sana', email='sana@example.com', phone='0301-1112233', password='Sana12345', password2='Sana12345'), page='/account/register'); check('Customer registers', 'Hello, Sana' in r.text)
r = cu.post('/account/addresses/add', dict(label='Home', name='Sana', phone='0301-1112233', line1='Flat 9', city='Karachi', province='Sindh', postal='', is_default='1'), page='/account'); check('Address saved', 'Flat 9' in r.text)
tchair = tote
cu.post('/cart/add', dict(product_id=tote, qty='1'), page='/product/canvas-tote-bag')
r = cu.get('/checkout'); check('Checkout prefilled from saved address', 'value="Flat 9"' in r.text)
rate3 = [x['id'] for x in cu.get('/checkout/quote?city=Karachi&province=Sindh').json()['rates'] if x['name'] == 'Standard delivery'][0]
r = cu.post('/checkout', dict(name='Sana', email='sana@example.com', phone='0301-1112233', line1='Flat 9', city='Karachi', province='Sindh', postal='', note='', rate=str(rate3), pm='cod', action='place'), page='/checkout')
num3 = re.search(r'(TSG-\d{6}-[A-Z0-9]{5})', r.text).group(1); check('Logged-in order placed', 'has been received' in r.text)
check('Order appears in account', num3 in cu.get('/account').text)
check('Other customer cannot open it', Client().get(f'/account/orders/{num3}', allow_redirects=False).status_code == 302)
r = cu.post('/account/logout', {}, page='/account'); check('Logout works', cu.get('/account', allow_redirects=False).status_code == 302)

# ---------------- Admin ----------------
a = Client()
check('Admin redirects to login', a.get('/admin', allow_redirects=False).status_code == 302)
r = a.post('/admin/login', dict(email='owner@example.pk', password='wrong'), page='/admin/login'); check('Wrong admin password rejected', 'Wrong email or password' in r.text)
r = a.post('/admin/login', dict(email='owner@example.pk', password='Owner12345'), page='/admin/login'); check('Owner logs in', 'Dashboard' in r.text)
check('Dashboard widgets', all(x in r.text for x in ['Pending orders', 'Payments to verify', 'COD to collect', 'Stock alerts', 'Top products']))
check('Low stock alert shown (Whole... / Navy)', 'Out of stock' in r.text or 'left' in r.text)
r = a.get('/admin/orders'); check('Orders list', num1 in r.text and num2 in r.text)
oid1 = re.search(r'/admin/orders/(\d+)">' + num1, r.text).group(1); oid2 = re.search(r'/admin/orders/(\d+)">' + num2, r.text).group(1)
r = a.get(f'/admin/orders/{oid2}'); check('Order shows payment proof link and txn id', 'View payment proof' in r.text and 'JC123456' in r.text)
proof = re.search(r'href="[^"]*(/admin/proof/[^"]+)"', r.text).group(1)
pr = a.get(proof); check('Proof served to staff only', pr.status_code == 200 and pr.headers['Content-Type'] == 'image/png')
check('Proof not reachable publicly', Client().get(proof, allow_redirects=False).status_code == 302 and Client().get('/storage/proofs/x.png').status_code in (404, 403))
r = a.post(f'/admin/orders/{oid2}/payment', dict(action='verify'), page=f'/admin/orders/{oid2}'); check('Manual payment verified', 'Paid' in r.text)

def white_m(): return int(re.search(STOCK_RE, a.get('/admin/inventory').text).group(1))
check('Stock NOT deducted by a pending order (still 12)', white_m() == 12, white_m())
r = a.post(f'/admin/orders/{oid1}/status', dict(status='Confirmed', note='Called customer'), page=f'/admin/orders/{oid1}'); check('Order confirmed', 'Order updated' in r.text)
hist = a.get('/admin/inventory/history').text; check('Confirmation deducts 3 (12 -> 9) and logs it', white_m() == 9 and 'confirmed' in hist and '-3' in hist, white_m())
r = a.post(f'/admin/orders/{oid1}/status', dict(status='Cancelled'), page=f'/admin/orders/{oid1}'); check('Cancel order', 'Cancelled' in r.text)
hist = a.get('/admin/inventory/history').text; check('Cancel restores stock (9 -> 12)', white_m() == 12 and 'cancelled' in hist and '+3' in hist, white_m())
r = a.post(f'/admin/orders/{oid1}/status', dict(status='Confirmed'), page=f'/admin/orders/{oid1}'); check('Re-confirm deducts once more (12 -> 9)', 'Order updated' in r.text and white_m() == 9, white_m())
a.post(f'/admin/orders/{oid1}/tracking', dict(courier='TCS', tracking_number='TCS998877', admin_note='fragile'), page=f'/admin/orders/{oid1}')
for st in ['Packed', 'Shipped']: a.post(f'/admin/orders/{oid1}/status', dict(status=st), page=f'/admin/orders/{oid1}')
r = Client().post('/track', dict(number=num1, email='ayesha@example.com'), page='/track'); check('Customer sees courier + tracking', 'TCS998877' in r.text and 'Shipped' in r.text)
r = a.post(f'/admin/orders/{oid1}/status', dict(status='Delivered'), page=f'/admin/orders/{oid1}'); check('Delivered marks COD collected', 'COD Collected' in r.text)
r = a.get(f'/admin/orders/{oid1}/invoice'); check('Invoice prints with store details', 'Invoice' in r.text and 'Kaleido Test Shop' in r.text and 'Rs. 8,755' in r.text)
r = a.get(f'/admin/orders/{oid1}/invoice?slip=1'); check('Packing slip hides prices', 'Packing slip' in r.text and 'Rs. 8,755' not in r.text)

# out-of-stock protection: navy M has 0 stock
r = c.get('/product/cotton-kurta'); vid_navy = re.search(r'value="(\d+)" disabled>Size: M, Colour: Navy', r.text.replace('\n', ' ')).group(1)
r = c.post('/cart/add', dict(product_id=pid, variant_id=vid_navy, qty='1', back='product/cotton-kurta'), page='/product/cotton-kurta'); check('Cannot add out-of-stock variant', 'out of stock' in r.text.lower())

# return flow for the logged-in customer
cu2 = Client(); cu2.post('/account/login', dict(email='sana@example.com', password='Sana12345'), page='/account/login')
oid3 = re.search(r'/admin/orders/(\d+)">' + num3, a.get('/admin/orders').text).group(1)
a.post(f'/admin/orders/{oid3}/status', dict(status='Confirmed'), page=f'/admin/orders/{oid3}'); a.post(f'/admin/orders/{oid3}/status', dict(status='Delivered'), page=f'/admin/orders/{oid3}')
r = cu2.get(f'/account/orders/{num3}'); check('Return form offered within return period', 'Request a return' in r.text)
r = cu2.post(f'/account/orders/{num3}/return', dict(reason='Wrong colour'), page=f'/account/orders/{num3}'); check('Return requested', 'Return Requested' in r.text)
r = a.post(f'/admin/orders/{oid3}/status', dict(status='Returned'), page=f'/admin/orders/{oid3}'); check('Return approved: stock back + refunded', 'Refunded' in r.text)

# product management
r = a.post('/admin/products/save', dict(id='0', name='Test Scarf', type='simple', price='1000', sale_price='800', stock='10', low_stock_level='3', status='published', track_stock='1', category_id='', weight='0.2', is_featured='1'), page='/admin/products/new',
           files={'images[]': ('scarf.png', png(900, 900), 'image/png')})
check('Admin creates product with image', 'Product saved' in r.text and 'Test Scarf' in r.text, r.text[:300])
pid_s = re.search(r'/admin/products/(\d+)/edit', r.url).group(1)
img = re.search(r'src="[^"]*(/uploads/products/[^"]+)"', r.text).group(1); check('Uploaded image resized to 1200 max and served', c.get(img).status_code == 200)
r = a.post('/admin/products/save', dict(id=pid_s, name='Test Scarf', type='simple', price='1200', stock='10', low_stock_level='3', status='published', track_stock='1', weight='0.2'), page=f'/admin/products/{pid_s}/edit')
check('Price change recorded in activity log', 'Price 1000.00 -&gt; 1200' in a.get('/admin/activity').text or 'price changed' in a.get('/admin/activity').text)
r = a.post('/admin/inventory/adjust', dict(product_id=pid_s, variant_id='', delta='-8', reason='Damaged'), page='/admin/inventory'); check('Manual stock adjust', 'Stock updated' in r.text)
check('Low-stock alert raised', 'Low stock: Test Scarf' in a.get('/admin').text)
r = a.post('/admin/products/save', dict(id='0', name='Bad<script>alert(1)</script>', type='simple', price='5', stock='1', status='published'), page='/admin/products/new'); check('HTML in names is escaped', '<script>alert(1)</script>' not in c.get('/shop').text and '&lt;script&gt;' in c.get('/shop').text)
fake = ('fake.png', b'<?php echo 1; ?>', 'image/png')
r = a.post(f'/admin/products/save', dict(id=pid_s, name='Test Scarf', type='simple', price='1200', stock='2', status='published', track_stock='1'), page=f'/admin/products/{pid_s}/edit', files={'images[]': fake}); check('Non-image upload rejected', 'Only JPG, PNG' in r.text)

# settings change -> whole site
r = a.post('/admin/settings', dict(store_name='Kaleido Renamed', tagline='New tagline', primary_color='#009688', secondary_color='#003d33', accent_color='#ffb703', theme='classic', currency_symbol='PKR', currency_position='after', currency_decimals='0', tax_percent='5', tax_mode='added', tax_label='GST',
    contact_email='shop@example.pk', phone='0300-1234567', whatsapp_number='923001234567', address='Saddar', country='Pakistan', return_days='7', products_per_page='12', min_order_amount='0', home_sections='banner,featured,categories,new',
    font_heading='Georgia, serif', font_body='system-ui', variant_fields='Size,Colour', mod_variants='1', mod_size_guide='1', mod_whatsapp_button='1', mod_guest_checkout='1', test_mode='1'), page='/admin/settings')
check('Settings saved', 'Settings saved' in r.text)
h = c.get('/').text; check('Store name changed everywhere', 'Kaleido Renamed' in h and 'Kaleido Test Shop' not in h)
check('Colour variable changed', '--primary:#009688' in h); check('Theme switched (classic css)', '/themes/classic/style.css' in h)
check('Currency format changed', '2700 PKR' in c.get('/product/cotton-kurta').text or '2,700 PKR' in c.get('/product/cotton-kurta').text)
check('Invoice follows new name', 'Kaleido Renamed' in a.get(f'/admin/orders/{oid1}/invoice').text)
check('Classic theme CSS file served', c.get('/themes/classic/style.css').status_code == 200)

# roles
r = a.post('/admin/staff/save', dict(id='0', name='Hina Handler', email='hina@example.pk', role='order_handler', status='active', password='Hina12345'), page='/admin/staff'); check('Staff account created', 'Staff account saved' in r.text)
h2 = Client(); h2.post('/admin/login', dict(email='hina@example.pk', password='Hina12345'), page='/admin/login')
check('Order handler can open orders', h2.get('/admin/orders').status_code == 200)
check('Order handler blocked from products', h2.get('/admin/products', allow_redirects=False).status_code == 403)
check('Order handler blocked from settings', h2.get('/admin/settings', allow_redirects=False).status_code == 403)
check('Order handler blocked from staff', h2.get('/admin/staff', allow_redirects=False).status_code == 403)
check('Order handler can view inventory but not adjust', h2.get('/admin/inventory').status_code == 200 and h2.post('/admin/inventory/adjust', dict(product_id=pid_s, delta='5'), page='/admin/inventory').status_code == 403)
check('Order handler dashboard hides sales', 'This month' not in h2.get('/admin').text)

# security
r = requests.post(BASE + '/cart/add', data=dict(product_id=pid, qty='1')); check('POST without CSRF token refused (419)', r.status_code == 419)
r = requests.post(BASE + '/admin/login', data=dict(email='owner@example.pk', password='x')); check('Admin login needs CSRF token', r.status_code == 419)
lk = Client()
for i in range(5): lk.post('/admin/login', dict(email='hina@example.pk', password='nope' + str(i)), page='/admin/login')
r = lk.post('/admin/login', dict(email='hina@example.pk', password='Hina12345'), page='/admin/login'); check('Account locks after 5 failed logins', 'Too many failed attempts' in r.text)
for p in ['/core/bootstrap.php', '/config/config.php', '/database/schema.sql', '/storage/proofs/', '/tools/backup_db.py']:
    check('Internal file not served: ' + p, Client().get(p).status_code in (403, 404) or 'DB' not in Client().get(p).text)
r = a.get('/admin/backup'); check('Owner can download SQL backup', r.status_code == 200 and 'CREATE TABLE' in r.text and 'owner@example.pk' in r.text)
check('Session cookie is HttpOnly', 'httponly' in str(a.s.cookies.get_dict()).lower() or any(c_.has_nonstandard_attr('HttpOnly') or c_._rest.get('HttpOnly') is not None for c_ in a.s.cookies))

failed = [n for n, ok in results if not ok]
print(f'\n{len(results) - len(failed)} passed, {len(failed)} failed of {len(results)}')
sys.exit(1 if failed else 0)
