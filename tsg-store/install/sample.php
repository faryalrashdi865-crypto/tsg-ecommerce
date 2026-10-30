<?php
/* Sample catalogue per business preset (optional in the setup wizard). Each product: name, category index, price, sale, stock, description, variants [[options, stock, price|null]] */
function sample_data(string $preset): array {
    $sets = [
        'fashion' => ['cats' => ['Men', 'Women', 'Accessories'], 'fields' => ['Size', 'Colour'], 'items' => [
            ['Cotton Kurta', 0, 3200, 2700, 0, 'Soft everyday cotton kurta.', [[['M', 'White'], 12], [['L', 'White'], 8], [['M', 'Navy'], 0], [['L', 'Navy'], 6]]],
            ['Denim Jacket', 0, 6500, null, 0, 'Classic mid-wash denim jacket.', [[['M', 'Blue'], 5], [['L', 'Blue'], 4]]],
            ['Lawn Suit 3-Piece', 1, 5400, 4800, 0, 'Printed lawn shirt, dupatta and trouser.', [[['S', 'Teal'], 7], [['M', 'Teal'], 9], [['M', 'Rose'], 3]]],
            ['Embroidered Shawl', 1, 4200, null, 15, 'Warm shawl with hand embroidery.', []],
            ['Leather Belt', 2, 1500, null, 30, 'Genuine leather belt.', []],
            ['Canvas Tote Bag', 2, 1200, 950, 40, 'Roomy everyday tote.', []]]],
        'electronics' => ['cats' => ['Phones', 'Audio', 'Accessories'], 'fields' => ['Option'], 'items' => [
            ['Smartphone X1 128GB', 0, 64999, 59999, 14, '6.5 inch display, 5000 mAh battery.', []],
            ['Wireless Earbuds Pro', 1, 7999, null, 25, 'Active noise cancelling earbuds.', []],
            ['Bluetooth Speaker', 1, 5499, 4799, 18, 'Portable waterproof speaker.', []],
            ['Fast Charger 33W', 2, 1999, null, 60, 'USB-C fast charger.', []],
            ['Power Bank 20000mAh', 2, 4499, null, 3, 'Dual-port power bank.', []],
            ['Phone Case', 2, 899, null, 100, 'Shock-proof case.', []]]],
        'grocery' => ['cats' => ['Staples', 'Fresh', 'Snacks'], 'fields' => ['Pack size'], 'items' => [
            ['Basmati Rice', 0, 520, null, 80, 'Aged long-grain basmati, per kg.', []],
            ['Cooking Oil', 0, 640, 610, 50, 'Refined canola oil, per litre.', []],
            ['Fresh Eggs', 1, 330, null, 40, 'Farm eggs, per dozen.', []],
            ['Whole Milk', 1, 220, null, 2, 'Fresh milk, per litre.', []],
            ['Potato Chips', 2, 120, null, 120, 'Salted crisps.', []],
            ['Mixed Nuts', 2, 1450, null, 20, 'Roasted mixed nuts, 500 g.', []]], 'unit' => ['kg', 'litre', 'dozen', 'litre', 'pack', 'pack']],
        'beauty' => ['cats' => ['Skincare', 'Makeup', 'Fragrance'], 'fields' => ['Shade'], 'items' => [
            ['Hydrating Face Cream', 0, 1800, null, 35, 'Daily moisturiser for all skin types.', []],
            ['Vitamin C Serum', 0, 2400, 2100, 22, 'Brightening serum.', []],
            ['Matte Lipstick', 1, 1100, null, 0, 'Long-wear matte lipstick.', [[['Rose'], 20], [['Berry'], 15], [['Nude'], 0]]],
            ['Foundation', 1, 2200, null, 0, 'Lightweight coverage.', [[['Fair'], 10], [['Medium'], 12], [['Deep'], 6]]],
            ['Eau de Parfum', 2, 6500, 5900, 9, 'Warm floral fragrance, 50 ml.', []],
            ['Body Mist', 2, 1300, null, 28, 'Light everyday mist.', []]]],
        'general' => ['cats' => ['Home', 'Office', 'Gifts'], 'fields' => ['Option'], 'items' => [
            ['Ceramic Mug', 0, 650, null, 50, 'Hand-glazed mug.', []],
            ['Scented Candle', 0, 1100, 950, 30, 'Soy wax candle.', []],
            ['Notebook A5', 1, 450, null, 90, 'Hardcover ruled notebook.', []],
            ['Desk Organiser', 1, 1800, null, 12, 'Wooden desk organiser.', []],
            ['Gift Box Small', 2, 2500, null, 8, 'Curated gift box.', []],
            ['Greeting Card Set', 2, 600, null, 70, 'Set of six cards.', []]]],
    ];
    return $sets[$preset] ?? $sets['general'];
}
