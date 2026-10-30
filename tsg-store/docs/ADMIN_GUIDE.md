# Store owner guide

Log in at `yourstore.pk/admin`. The menu on the left shows only what your role may use.

## Daily routine
1. **Dashboard**: new orders, payments waiting for checking, cash on delivery still to collect, stock alerts, top products.
2. **Orders > Pending**: open an order. If payment was by JazzCash, Easypaisa or bank, click **View payment proof**, compare with your account, then **Mark verified** (or Reject). Cash on delivery orders are confirmed by phone or WhatsApp (button on the order page).
3. Set the status to **Confirmed**. This is the moment stock is reduced. Then **Packed**, **Shipped** (add courier and tracking number first, the customer sees it), **Delivered** (cash on delivery is marked collected automatically).
4. Print the **invoice** or **packing slip** from the order page.

## Order statuses
Pending, Confirmed, Packed, Shipped, Delivered, Completed, Cancelled, Return Requested, Returned. Cancelling or returning an order puts the stock back. A customer can request a return from their account within the return period (Settings). Approve it by setting **Returned**; reject it by setting **Delivered** again.

## Products
Products > Add product. Simple product: one price and one stock number. Variable product (size, colour, shade): choose "Variable", fill one row per combination, each with its own stock and optional price. Add a sale price and dates for offers. Tick "Show on the homepage" for featured items. Stock changes made on a product page are written to the stock history.

## Inventory
Inventory shows every item and variant. Type a plus or minus number and a reason to adjust stock. Every movement (orders, adjustments) is logged under "stock history". Items at or below their alert level appear on the dashboard.

## Shipping
Shipping > zones. A zone lists cities or provinces separated by commas; `*` means everywhere else. Each zone has rates: flat, by weight (kg) or by order value. "Free shipping from" applies to the whole zone. Mark a rate as Pickup for store collection.

## Payments
Payments: turn methods on or off, edit the instructions and the account details customers see, add an extra fee if you want. Card payments arrive in a later phase.

## Settings (one place, whole website)
Store name, logo, colours, theme, currency, tax, modules (variants, specifications, units, ingredients, warranty, size guide, brand filter, WhatsApp button, guest checkout), email templates. Save and every page, email and invoice follows.

## Staff roles
Owner (everything), Manager (no payments, shipping, settings or staff), Order Handler (orders; view stock and customers), Content Editor (products, pages, banners), Inventory Clerk (stock; view products). Staff > add an account and choose a role. Accounts lock for 15 minutes after 5 wrong passwords. Use the Activity log to see who changed what.

## Backups
"Download backup" (bottom of the menu) saves a full database file. Also schedule the daily backup script (see the installation guide) and keep copies away from the server.
