<?php
declare(strict_types=1);

function presets(): array {
    return [
        'general'     => ['label' => 'General Store', 'desc' => 'Any physical product, standard catalogue', 'variant_fields' => 'Option', 'mods' => []],
        'fashion'     => ['label' => 'Fashion and Clothing', 'desc' => 'Size and colour variants, size guide', 'variant_fields' => 'Size,Colour', 'mods' => ['variants' => 1, 'size_guide' => 1]],
        'electronics' => ['label' => 'Electronics', 'desc' => 'Specification table, warranty field, brand filter', 'variant_fields' => 'Option', 'mods' => ['specs' => 1, 'warranty' => 1, 'brand_filter' => 1]],
        'grocery'     => ['label' => 'Grocery and Food', 'desc' => 'Units and weight, minimum order, delivery area', 'variant_fields' => 'Pack size', 'mods' => ['units' => 1]],
        'beauty'      => ['label' => 'Beauty and Cosmetics', 'desc' => 'Shade variants, ingredient tab', 'variant_fields' => 'Shade', 'mods' => ['variants' => 1, 'ingredients' => 1]],
        'digital'     => ['label' => 'Digital Goods (Phase 2)', 'desc' => 'Instant download, licence keys - arrives in Phase 2', 'variant_fields' => 'Option', 'mods' => [], 'disabled' => true],
    ];
}

function default_settings(string $preset = 'general'): array {
    $p = presets()[$preset] ?? presets()['general'];
    $s = [
        'store_name' => 'My Store', 'tagline' => '', 'logo' => '', 'favicon' => '',
        'primary_color' => '#1f6feb', 'secondary_color' => '#0b2545', 'accent_color' => '#f5a524',
        'font_heading' => 'Georgia, serif', 'font_body' => 'system-ui, -apple-system, Segoe UI, Roboto, sans-serif',
        'theme' => 'default', 'preset' => $preset, 'variant_fields' => $p['variant_fields'],
        'currency_code' => 'PKR', 'currency_symbol' => 'Rs.', 'currency_position' => 'before', 'currency_decimals' => '0',
        'tax_percent' => '0', 'tax_mode' => 'added', 'tax_label' => 'GST',
        'contact_email' => '', 'phone' => '', 'whatsapp_number' => '', 'address' => '', 'country' => 'Pakistan',
        'facebook' => '', 'instagram' => '', 'footer_text' => '',
        'mod_variants' => '1', 'mod_specs' => '0', 'mod_units' => '0', 'mod_ingredients' => '0', 'mod_warranty' => '0',
        'mod_size_guide' => '0', 'mod_brand_filter' => '0', 'mod_whatsapp_button' => '1', 'mod_guest_checkout' => '1',
        'size_guide_text' => "XS: chest 34 in\nS: chest 36 in\nM: chest 38 in\nL: chest 40 in\nXL: chest 42 in",
        'min_order_amount' => '0', 'return_days' => '7', 'allow_backorders_default' => '0', 'low_stock_default' => '5',
        'home_sections' => 'banner,featured,categories,new', 'products_per_page' => '12',
        'test_mode' => '1', 'force_https' => '0', 'licence_key' => '',
        'tpl_order_placed_subject' => 'Order {order_number} received - {store_name}',
        'tpl_order_placed_body' => "Hello {name},\n\nThank you for your order {order_number}. Total: {total}.\nTrack it here: {link}\n\n{store_name}",
        'tpl_order_status_subject' => 'Order {order_number} is now {status} - {store_name}',
        'tpl_order_status_body' => "Hello {name},\n\nYour order {order_number} is now: {status}.\n{tracking}\nTrack it here: {link}\n\n{store_name}",
        'wa_order_text' => 'Hello {store_name}, my order {order_number} (total {total}). Please confirm.',
    ];
    foreach ($p['mods'] as $k => $v) $s['mod_' . $k] = (string)$v;
    return $s;
}

function default_payment_methods(): array {
    return [
        ['cod', 'Cash on Delivery', 1, 'Pay in cash when the courier delivers your order. We may confirm your order by phone or WhatsApp.', '', 0, 0, 1],
        ['jazzcash', 'JazzCash', 0, 'Send the total amount to the JazzCash account below, then enter the transaction ID and upload the screenshot.', 'Account title: \nJazzCash number: ', 0, 1, 2],
        ['easypaisa', 'Easypaisa', 0, 'Send the total amount to the Easypaisa account below, then enter the transaction ID and upload the screenshot.', 'Account title: \nEasypaisa number: ', 0, 1, 3],
        ['bank', 'Bank Transfer', 0, 'Transfer the total amount to the bank account below and upload the receipt.', 'Bank: \nAccount title: \nIBAN: ', 0, 1, 4],
    ];
}

function provinces(): array {
    return ['Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan', 'Islamabad Capital Territory', 'Gilgit-Baltistan', 'Azad Kashmir'];
}

function role_labels(): array {
    return ['owner' => 'Store Owner', 'manager' => 'Manager', 'order_handler' => 'Order Handler', 'content_editor' => 'Content Editor', 'inventory_clerk' => 'Inventory Clerk'];
}

/* Access matrix from the documentation, section 3.1 */
function role_permissions(): array {
    return [
        'owner'           => ['*'],
        'manager'         => ['dashboard', 'products.manage', 'products.view', 'inventory.manage', 'inventory.view', 'orders.manage', 'customers.manage', 'customers.view', 'content.manage', 'reports.view'],
        'order_handler'   => ['dashboard', 'orders.manage', 'inventory.view', 'customers.view'],
        'content_editor'  => ['dashboard', 'products.manage', 'products.view', 'content.manage'],
        'inventory_clerk' => ['dashboard', 'inventory.manage', 'inventory.view', 'products.view'],
    ];
}

function order_statuses(): array { return ['Pending', 'Confirmed', 'Packed', 'Shipped', 'Delivered', 'Completed', 'Cancelled', 'Return Requested', 'Returned']; }
