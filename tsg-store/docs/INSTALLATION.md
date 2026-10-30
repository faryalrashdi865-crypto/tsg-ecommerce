# Installation guide (cPanel / shared hosting)

## Hosting checklist (check before you start)
- PHP 8.0 or newer with pdo_mysql, mbstring, fileinfo (GD recommended for image resizing)
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite and .htaccess allowed (most cPanel hosts)
- SSL certificate active (Let's Encrypt is free in cPanel) - then switch on "Force HTTPS" in Settings
- Cron enabled; storage for backups; PHP mail() working or an SMTP-capable host

## Steps
1. **Licence**: on your own machine run `python3 tools/generate_licence.py "Client Business Name" clientdomain.pk`. The business name must be typed in the wizard exactly as given here (case does not matter).
2. In cPanel create an empty MySQL database and a user with all privileges on it.
3. Upload the project to `public_html` (or the domain's folder) and extract it. Do NOT upload `tests/`.
4. Make sure `config/`, `uploads/` and `storage/` are writable (755, or 775 if the host needs it).
5. Open `https://clientdomain.pk/`. The setup wizard starts: requirements, licence + database, owner and store, preset, branding, currency and tax, payments, shipping, install.
6. **Delete the `/install` folder.** Log in at `/admin`.
7. Add real products, check payment account details and shipping rates, place a test order, then switch **Test mode** off in Settings.
8. Add the backup cron job (cPanel > Cron Jobs): `0 2 * * * /usr/bin/python3 /home/USER/public_html/tools/backup_db.py` and copy `storage/backups` off the server regularly.

## If the site shows 500 or blank pages
Check that `.htaccess` was uploaded (hidden file) and that mod_rewrite is on. Temporarily set `'debug' => true` in `config/config.php` to see the error, then set it back to false.

## Changing a client's look
Settings > Theme (colours, fonts, logo, homepage order). For deeper changes copy `themes/default` to `themes/clientname`, edit `style.css`, and optionally put replacement templates in `themes/clientname/views/` using the same file names as `core/views/` (for example `shop/product.php`). Select the theme in Settings. Never edit `/core`.
