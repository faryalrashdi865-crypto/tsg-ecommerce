# Handover notes: what Phase 1 contains, and what it does not

## Delivered (Phase 1 scope)
- **Setup wizard** (9 steps): requirements check, licence + database, owner and store details, business preset, branding, currency and tax, payment methods, shipping zones, optional sample products. Writes `config.php`, creates tables, owner account, default pages, locks itself.
- **Settings-driven store**: name, logo, colours, fonts, currency format, tax (added or included), modules, email and WhatsApp templates. Two themes (default, classic) and a view-override mechanism.
- **Business presets**: General, Fashion, Electronics, Grocery, Beauty switch module flags and variant fields. Digital is listed but disabled (Phase 2).
- **Storefront**: homepage sections and banner slider, catalogue with search, category, brand (when enabled), price filter and sorting, product page (gallery with zoom, variants, specifications, ingredients, warranty, size guide, related products, WhatsApp order button), cart saved in the database, checkout with live shipping and totals, guest or account checkout, order confirmation, order tracking by number + email, customer accounts (register, login, addresses, orders, return request, profile), lockout after 5 wrong passwords.
- **Admin**: dashboard, products (variants, images, sale dates, stock), categories and brands, inventory with full history and low-stock alerts, orders (status flow, courier, payment verification, proof viewer, invoice, packing slip, WhatsApp message), customers (block/unblock), shipping zones and rates, payment methods, pages, banners, settings, staff with 5 roles, activity log, one-click SQL backup.
- **Payments**: Cash on Delivery, JazzCash, Easypaisa, bank transfer (customer uploads transaction ID and proof; staff verify).
- **Python tools**: licence generator, daily/weekly backup, image optimiser.
- **Security**: prepared statements everywhere, output escaping, CSRF token on every form, password hashing, session cookie HttpOnly + SameSite, uploads validated by real image type and size and resized, PHP execution blocked in `/uploads`, payment proofs stored outside public access, internal folders blocked, role checks on every admin route.

## Not in Phase 1 (planned in the documentation for later phases)
Coupons, reviews, wishlist, reports and CSV export, CSV product import, blog, SEO sitemap and schema markup, abandoned-cart emails, digital products, bundles, multi-language, card gateways, courier APIs, WhatsApp API automation, PWA, one-click update system, rich-text editor for descriptions (plain text with line breaks for now), live search suggestions.

## Known limits and honest notes
- **Not yet tested in a real browser or on real Apache/cPanel.** The automated test (113 checks) drives the server over HTTP against PHP's built-in server and MariaDB. The JavaScript (checkout live totals, slider, variant rows) passed a syntax check and its server endpoints are tested, but it should be clicked through once on a phone and a desktop browser before any client sees it. The checkout also works without JavaScript through the "Update shipping options" button.
- **Emails** use PHP `mail()`, which many hosts throttle or send to spam. Every email is also stored in the `notifications` table. For production use SMTP (a small mailer class can replace `send_mail()` in `core/lib/helpers.php`).
- **Licence check** is an HMAC of business name + domain with a secret inside the code. It deters casual copying only. Change `LICENCE_SECRET` in `install/licence.php` and `tools/generate_licence.py` before the first sale; Phase 3 should use signed keys or an online check.
- Cart is saved in the database against a session token (guests) or the customer account; abandoned carts are kept but not emailed yet.
- Stock reduces when an order is **confirmed** (as the documentation specifies), so two pending orders can both be placed against the last unit; the second confirmation is blocked with a clear message.
- Tax is applied to the product subtotal (not shipping). Shipping zones match on exact city or province names.
- Money is stored as DECIMAL(12,2); display decimals come from Settings.

## Where to extend
New page or feature: add a route in `core/routes.php`, a function in `core/controllers/`, a view in `core/views/`. New setting: add a default in `core/lib/defaults.php` and an input in `core/views/admin/settings.php`. New preset: add to `presets()`. New payment method: add a row in `default_payment_methods()`.
