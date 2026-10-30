# Tech SG Studio - Reusable E-commerce Script (Phase 1: Core)

One codebase for many online stores. Each client gets the same script; the **setup wizard**, the **settings table** and the **theme folder** make it look and behave like their own store. No code is edited per client.

Built to the "Reusable E-commerce Script - Documentation", Phase 1 scope: core store, setup wizard, theme system, business presets, admin roles, orders, inventory, manual payments, shipping and WhatsApp.

## Stack
PHP 8 + MySQL/MariaDB (store and admin), HTML + CSS (themes), vanilla JavaScript (slider, gallery, live checkout totals, admin helpers), Python (backup, image optimiser, licence generator in `/tools`). No framework, no Composer, no build step: upload and run on ordinary cPanel hosting.

## Folder map
```
index.php            front controller (every page goes through it)
router.php           development server router only (php -S)
install/             setup wizard (delete after installing)
core/                the product: lib/ controllers/ views/ routes.php  (never edited per client)
themes/<name>/       style.css, theme.json, optional views/ overrides  (per client look)
assets/              admin.css, js/store.js, js/admin.js
uploads/             product, banner and logo images (public, PHP execution blocked)
storage/             payment proofs (private, served only to staff), backups
config/config.php    created by the wizard (database login, timezone)
database/schema.sql  all tables
tools/               generate_licence.py, backup_db.py, optimize_images.py
tests/e2e.py         113-check automated test
docs/                installation, admin guide, handover notes, test report
```

## Quick start (development)
```
mysql -e "CREATE DATABASE shop CHARACTER SET utf8mb4; CREATE USER 'shop'@'localhost' IDENTIFIED BY 'secret'; GRANT ALL ON shop.* TO 'shop'@'localhost';"
php -S localhost:8000 router.php
# open http://localhost:8000  -> the setup wizard starts
# on localhost the licence key TSG-DEMO-DEMO-DEMO-DEMO is accepted
```
Run the tests against an empty database: `python3 tests/e2e.py http://127.0.0.1:8000` (edit the DB constants at the top of the file).

See `docs/INSTALLATION.md` for cPanel, `docs/ADMIN_GUIDE.md` for the store owner, `docs/HANDOVER.md` for scope, limits and next steps.
