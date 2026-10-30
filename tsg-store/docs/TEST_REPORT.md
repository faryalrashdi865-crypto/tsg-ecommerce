# Automated test report

Run: `python3 tests/e2e.py http://127.0.0.1:8000` against PHP 8.3 built-in server + MariaDB 10.11, empty database, on 30 October 2026.

Result: **113 passed, 0 failed of 113**

Not covered by this test: real browsers and phones, Apache/cPanel .htaccess behaviour, real email delivery, JavaScript behaviour (syntax-checked only), load and performance.

```
PASS Wizard step 1 loads
PASS Requirements pass
PASS Bad licence format rejected
PASS Bad DB password rejected
PASS Licence + DB accepted
PASS Weak owner password rejected
PASS Owner + store step accepted
PASS Digital preset blocked (Phase 2)
PASS Fashion preset accepted
PASS Branding + logo accepted
PASS Currency/tax accepted
PASS Needs at least one payment method
PASS Payments accepted
PASS Shipping accepted
PASS Install completes
PASS Installer locked after install
PASS Home loads with store name
PASS Test-mode banner visible
PASS Theme colour variable from settings
PASS Logo shown
PASS Catalogue lists sample products
PASS Category filter works
PASS Search by name
PASS Price filter and sort
PASS Product page with variants
PASS Out-of-stock variant disabled
PASS Sale price shown
PASS WhatsApp order button
PASS Size guide (fashion preset)
PASS Unknown product gives 404
PASS Info page loads
PASS Variant required
PASS Add variant to cart
PASS Cannot add more than stock (12)
PASS Cart subtotal = 2*2700 + 950
PASS Update qty and remove
PASS Quote: Karachi gets local zone rates incl. pickup
PASS Quote totals: 8100 + 200 ship + 405 tax + 50 COD fee = 8,755
PASS Quote: Lahore falls to national zone
PASS Invalid email rejected
PASS Manual payment needs txn id + proof
PASS Forged shipping rate rejected
PASS COD order placed
PASS Order total correct (8100+200+405 tax+50 fee = 8755)
PASS Cart emptied after order
PASS Tracking by number+email
PASS Tracking with wrong email fails
PASS Order page needs the access token
PASS JazzCash order with proof placed
PASS Checkout works without JS (update shipping options)
PASS Weak customer password rejected
PASS Customer registers
PASS Address saved
PASS Checkout prefilled from saved address
PASS Logged-in order placed
PASS Order appears in account
PASS Other customer cannot open it
PASS Logout works
PASS Admin redirects to login
PASS Wrong admin password rejected
PASS Owner logs in
PASS Dashboard widgets
PASS Low stock alert shown (Whole... / Navy)
PASS Orders list
PASS Order shows payment proof link and txn id
PASS Proof served to staff only
PASS Proof not reachable publicly
PASS Manual payment verified
PASS Stock NOT deducted by a pending order (still 12)
PASS Order confirmed
PASS Confirmation deducts 3 (12 -> 9) and logs it
PASS Cancel order
PASS Cancel restores stock (9 -> 12)
PASS Re-confirm deducts once more (12 -> 9)
PASS Customer sees courier + tracking
PASS Delivered marks COD collected
PASS Invoice prints with store details
PASS Packing slip hides prices
PASS Cannot add out-of-stock variant
PASS Return form offered within return period
PASS Return requested
PASS Return approved: stock back + refunded
PASS Admin creates product with image
PASS Uploaded image resized to 1200 max and served
PASS Price change recorded in activity log
PASS Manual stock adjust
PASS Low-stock alert raised
PASS HTML in names is escaped
PASS Non-image upload rejected
PASS Settings saved
PASS Store name changed everywhere
PASS Colour variable changed
PASS Theme switched (classic css)
PASS Currency format changed
PASS Invoice follows new name
PASS Classic theme CSS file served
PASS Staff account created
PASS Order handler can open orders
PASS Order handler blocked from products
PASS Order handler blocked from settings
PASS Order handler blocked from staff
PASS Order handler can view inventory but not adjust
PASS Order handler dashboard hides sales
PASS POST without CSRF token refused (419)
PASS Admin login needs CSRF token
PASS Account locks after 5 failed logins
PASS Internal file not served: /core/bootstrap.php
PASS Internal file not served: /config/config.php
PASS Internal file not served: /database/schema.sql
PASS Internal file not served: /storage/proofs/
PASS Internal file not served: /tools/backup_db.py
PASS Owner can download SQL backup
PASS Session cookie is HttpOnly

113 passed, 0 failed of 113
```
