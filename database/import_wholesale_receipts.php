<?php
/**
 * Import wholesale purchase receipts into purchase_receipts, products, and inventory.
 *
 * Usage:
 *   php database/import_wholesale_receipts.php           # run import
 *   php database/import_wholesale_receipts.php --dry-run # preview only
 *
 * Skips receipts whose receipt_no already exists in purchase_receipts.
 * Currency: KES. Missing unit prices are calculated as line_total / qty.
 */
require_once __DIR__ . '/../config.php';

$dry_run = in_array('--dry-run', $argv, true);

if (!isset($conn) || !$conn) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$default_category = 'Wholesale Goods';
$default_brand_fallback = 'General Supplier';
$currency = 'KES';
$retail_markup = 1.25;

$receipts = array(
    array(
        'receipt_no' => '0315956',
        'supplier' => 'Better Deal Wholesalers Ltd',
        'date' => '2026-06-23',
        'customer' => 'Jazila G15',
        'total' => 14160.00,
        'items' => array(
            array('code' => '55292783', 'name' => 'OMAR 48*95G STT (CT)', 'qty' => 1, 'unit_price' => 5540.00, 'total' => 5540.00),
            array('code' => '55294775', 'name' => 'WHITE WOSH 200G STT (CT)', 'qty' => 1, 'unit_price' => 1600.00, 'total' => 1600.00),
            array('code' => '55294541', 'name' => 'KRACHLES 48*30G (CT)', 'qty' => 1, 'unit_price' => 2160.00, 'total' => 2160.00),
            array('code' => '55294024', 'name' => 'SODA PLASTIC 350ML (BX)', 'qty' => 1, 'unit_price' => 990.00, 'total' => 990.00),
            array('code' => '000003613', 'name' => 'SOFTCARE JUNIA NO 5 STT (CT)', 'qty' => 1, 'unit_price' => 3870.00, 'total' => 3870.00),
        ),
    ),
    array(
        'receipt_no' => '0229062',
        'supplier' => 'Bishal Mini Shop',
        'date' => '2025-06-05',
        'customer' => 'Abdinasib Jazila',
        'total' => 77745.00,
        'items' => array(
            array('code' => '000001773', 'name' => 'EAFRICA RICE (BG)', 'qty' => 2, 'total' => 7000.00),
            array('code' => '000003469', 'name' => 'TANA MACARONI BIG UNCLEAR', 'qty' => 1, 'total' => 2050.00),
            array('code' => '000007354', 'name' => 'QUEEN MACARONI ELBOW', 'qty' => 1, 'total' => 1600.00),
            array('code' => '000003260', 'name' => 'SOFT CARE BG', 'qty' => 1, 'total' => 3680.00),
            array('code' => '1000521', 'name' => 'POPCO SALIT OIL 1LTR', 'qty' => 1, 'total' => 2600.00),
            array('code' => '1001177', 'name' => 'POPCO SALIT OIL 3LTR', 'qty' => 1, 'total' => 4355.00),
            array('code' => '000007530', 'name' => 'SALIT 1L CTN', 'qty' => 1, 'total' => 3143.00),
            array('code' => '000007564', 'name' => 'SALIT 500ML (CTN)', 'qty' => 1, 'total' => 1700.00),
            array('code' => '00002015', 'name' => 'AMEFI TUNA (CNT)', 'qty' => 1, 'total' => 4800.00),
            array('code' => '00000480', 'name' => 'SEASONING UNCLEAR', 'qty' => 2, 'total' => 2000.00),
            array('code' => '000003363', 'name' => 'CIFA SEGMETTES', 'qty' => 2, 'total' => 2260.00),
            array('code' => '000002317', 'name' => 'BROOKSIDE MILK 200ML', 'qty' => 6, 'total' => 690.00),
            array('code' => '000005345', 'name' => 'WATER 1 LTR', 'qty' => 3, 'total' => 555.00),
            array('code' => '000001166', 'name' => 'RUDA TONE 500ML', 'qty' => 1, 'total' => 1500.00),
            array('code' => '000001899', 'name' => 'FANTA SODA ORANGE 350ML', 'qty' => 1, 'total' => 1000.00),
            array('code' => '00007465', 'name' => 'DELA FLAVOURED 500ML', 'qty' => 5, 'total' => 1750.00),
            array('code' => '00007569', 'name' => 'DELA FLAVOURED UNCLEAR', 'qty' => 5, 'total' => 1450.00),
            array('code' => '00003114', 'name' => 'LIMATE MAID APPLE 500ML', 'qty' => 3, 'total' => 2400.00),
            array('code' => '000003169', 'name' => 'JUICE CTN', 'qty' => 3, 'total' => 1950.00),
            array('code' => '000003001', 'name' => 'BRAVA APPLE', 'qty' => 4, 'total' => 1600.00),
            array('code' => '000003341', 'name' => 'CRYSTAL ICE WATER', 'qty' => 10, 'total' => 2600.00),
            array('code' => '1006173', 'name' => 'STAR WATER', 'qty' => 10, 'total' => 2600.00),
            array('code' => '000002015', 'name' => 'NESCAFE SACHET', 'qty' => 2, 'total' => 670.00),
            array('code' => '000007560', 'name' => 'CHARCOAL BAG', 'qty' => 3, 'total' => 1050.00),
            array('code' => '000002379', 'name' => 'HAPPY BISCUIT', 'qty' => 3, 'total' => 780.00),
            array('code' => '1000421', 'name' => 'MARIE BISCUITS', 'qty' => 2, 'total' => 480.00),
            array('code' => '000002362', 'name' => 'TROPICAL MINT KSL', 'qty' => 7, 'total' => 700.00),
            array('code' => '00000467', 'name' => 'BROWN BREAD', 'qty' => 1, 'total' => 540.00),
        ),
    ),
    array(
        'receipt_no' => '0137347',
        'supplier' => 'Better Deal Wholesalers Ltd',
        'date' => '2025-12-05',
        'customer' => 'JAZILA HEIGHT G15 & KA',
        'total' => 103750.00,
        'items' => array(
            array('code' => '000003520', 'name' => 'SOOPER BISCUIT (CTN)', 'qty' => 5, 'total' => 6350.00),
            array('code' => '000003520', 'name' => 'SOOPER BISCUIT (CTN)', 'qty' => 5, 'total' => 6350.00),
            array('code' => '55294504', 'name' => 'COLGATE HERBAL 6X70ML', 'qty' => 3, 'total' => 4410.00),
            array('code' => '55294700', 'name' => 'COLGATE HERBAL 10CML', 'qty' => 3, 'total' => 4830.00),
            array('code' => '55294603', 'name' => 'NUTELLA CHO 15X350G', 'qty' => 1, 'total' => 6950.00),
            array('code' => '55293447', 'name' => 'TAK SP STT (CT)', 'qty' => 1, 'total' => 2130.00),
            array('code' => '55292784', 'name' => 'OMAR 48X95G STT (CT)', 'qty' => 1, 'total' => 8450.00),
            array('code' => '55293503', 'name' => 'PEANUT BUTTER 12X400G', 'qty' => 1, 'total' => 2650.00),
            array('code' => '000003613', 'name' => 'SOFTCARE JUNIA NO 5 STT', 'qty' => 1, 'total' => 3820.00),
            array('code' => '55292809', 'name' => 'SOFTCARE MAXI 4 STT', 'qty' => 1, 'total' => 3820.00),
            array('code' => '55294118', 'name' => 'MILK 48X390', 'qty' => 1, 'total' => 7100.00),
            array('code' => '55294776', 'name' => 'WHITE WOSH UNCLEAR', 'qty' => 2, 'total' => 3040.00),
            array('code' => '000003686', 'name' => 'SNICKERS 55G', 'qty' => 1, 'total' => 1850.00),
            array('code' => '000001810', 'name' => 'GX HARPIC 750ML', 'qty' => 1, 'total' => 1780.00),
            array('code' => '55294437', 'name' => 'FINOLESA 12X60ML', 'qty' => 5, 'total' => 3550.00),
            array('code' => '55294949', 'name' => 'MILANO DONUT 24PCS', 'qty' => 1, 'total' => 3750.00),
            array('code' => '55293747', 'name' => 'PILAU MASALA', 'qty' => 6, 'total' => 930.00),
            array('code' => '55293751', 'name' => 'CHICKEN MASALA', 'qty' => 6, 'total' => 570.00),
            array('code' => '55294446', 'name' => 'ABU WAAL BISO STT', 'qty' => 1, 'total' => 2250.00),
            array('code' => '55292992', 'name' => 'BLUE BAND 24X500G', 'qty' => 5, 'total' => 2900.00),
            array('code' => '55292981', 'name' => 'BLUE BAND 48X250G', 'qty' => 5, 'total' => 3125.00),
            array('code' => '55292801', 'name' => 'BLUE BAND 48X100G', 'qty' => 5, 'total' => 1305.00),
            array('code' => '55299289', 'name' => 'WIFTOS 8X500 STT', 'qty' => 1, 'total' => 4500.00),
            array('code' => '55294443', 'name' => 'BABY WIPES SOFTCARE', 'qty' => 2, 'total' => 3040.00),
            array('code' => '55290737', 'name' => 'ALWAYS SOFTCARE SB3', 'qty' => 2, 'total' => 3000.00),
            array('code' => '55294757', 'name' => 'MABUYUZ CHILI BALLS', 'qty' => 1, 'total' => 1630.00),
            array('code' => '55294338', 'name' => 'BELA TISHY STT', 'qty' => 1, 'total' => 1220.00),
            array('code' => '55294192', 'name' => 'BROOKSIDE 12X500ML', 'qty' => 1, 'total' => 680.00),
            array('code' => '55294024', 'name' => 'SODA PLASTIC 350ML', 'qty' => 2, 'total' => 1960.00),
            array('code' => '55294205', 'name' => 'INDOMI CHICKEN STT', 'qty' => 2, 'total' => 1460.00),
            array('code' => '55294541', 'name' => 'KRACHLES 48X30G', 'qty' => 2, 'total' => 4250.00),
            array('code' => '55292527', 'name' => 'BROWN SUGAR 50KG', 'qty' => 1, 'total' => 6450.00),
        ),
    ),
);

function esc_sql($conn, $value)
{
    return $conn->real_escape_string((string)$value);
}

function normalize_barcode($code)
{
    $code = trim((string)$code);
    if ($code === '') {
        return '';
    }
    if (preg_match('/^\d+\.\d+$/', $code)) {
        $f = (float)$code;
        if ($f == floor($f)) {
            return sprintf('%.0f', $f);
        }
    }
    return $code;
}

function extract_variant($item_name)
{
    if (preg_match('/\(([^)]+)\)\s*$/u', $item_name, $m)) {
        return trim($m[1]);
    }
    if (stripos($item_name, ' UNCLEAR') !== false) {
        return 'UNCLEAR';
    }
    return 'Default';
}

function suggest_retail_price($unit_cost, $existing_price, $markup)
{
    $unit_cost = (float)$unit_cost;
    $existing_price = (float)$existing_price;
    if ($existing_price > 0) {
        return $existing_price;
    }
    if ($unit_cost <= 0) {
        return 0;
    }
    $retail = $unit_cost * $markup;
    return ceil($retail / 10) * 10;
}

function ensure_purchase_tables($conn, $dry_run)
{
    $sql = "CREATE TABLE IF NOT EXISTS `purchase_receipts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `receipt_no` varchar(50) NOT NULL,
        `supplier` varchar(250) NOT NULL,
        `receipt_date` date NOT NULL,
        `customer` varchar(250) DEFAULT NULL,
        `total_amount` double NOT NULL DEFAULT 0,
        `currency` varchar(10) NOT NULL DEFAULT 'KES',
        `notes` text DEFAULT NULL,
        `date_imported` datetime NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_purchase_receipt_no` (`receipt_no`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $sql2 = "CREATE TABLE IF NOT EXISTS `purchase_receipt_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `purchase_receipt_id` int(11) NOT NULL,
        `line_no` int(11) NOT NULL DEFAULT 0,
        `item_code` varchar(100) DEFAULT NULL,
        `item_name` varchar(250) NOT NULL,
        `quantity` double NOT NULL DEFAULT 0,
        `unit_price` double NOT NULL DEFAULT 0,
        `line_total` double NOT NULL DEFAULT 0,
        `product_id` int(11) DEFAULT NULL,
        `inventory_id` int(11) DEFAULT NULL,
        `variant` varchar(100) NOT NULL DEFAULT 'Default',
        PRIMARY KEY (`id`),
        KEY `idx_pri_receipt` (`purchase_receipt_id`),
        KEY `idx_pri_product` (`product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($dry_run) {
        echo "[dry-run] Would ensure purchase_receipts and purchase_receipt_items tables exist.\n";
        return true;
    }
    if (!$conn->query($sql)) {
        fwrite(STDERR, "Failed to create purchase_receipts: {$conn->error}\n");
        return false;
    }
    if (!$conn->query($sql2)) {
        fwrite(STDERR, "Failed to create purchase_receipt_items: {$conn->error}\n");
        return false;
    }
    return true;
}

function receipt_exists($conn, $receipt_no)
{
    $no = esc_sql($conn, $receipt_no);
    $q = $conn->query("SELECT id FROM purchase_receipts WHERE receipt_no = '{$no}' LIMIT 1");
    return ($q && $q->num_rows > 0);
}

function ensure_brand($conn, $name, $dry_run, &$report)
{
    $name = trim($name);
    if ($name === '') {
        $name = 'General Supplier';
    }
    $q = $conn->query("SELECT id FROM brands WHERE delete_flag = 0 AND LOWER(name) = LOWER('" . esc_sql($conn, $name) . "') LIMIT 1");
    if ($q && $q->num_rows) {
        return (int)$q->fetch_assoc()['id'];
    }
    if ($dry_run) {
        $report['brands_created']++;
        return 0;
    }
    $sql = "INSERT INTO brands SET name = '" . esc_sql($conn, $name) . "', status = 1, delete_flag = 0";
    if (!$conn->query($sql)) {
        throw new Exception('Could not create brand: ' . $name);
    }
    $report['brands_created']++;
    return (int)$conn->insert_id;
}

function ensure_category($conn, $name, $dry_run, &$report)
{
    $name = trim($name);
    if ($name === '') {
        $name = 'Wholesale Goods';
    }
    $q = $conn->query("SELECT id FROM categories WHERE delete_flag = 0 AND LOWER(category) = LOWER('" . esc_sql($conn, $name) . "') LIMIT 1");
    if ($q && $q->num_rows) {
        return (int)$q->fetch_assoc()['id'];
    }
    if ($dry_run) {
        $report['categories_created']++;
        return 0;
    }
    $sql = "INSERT INTO categories SET category = '" . esc_sql($conn, $name) . "', status = 1, delete_flag = 0";
    if (!$conn->query($sql)) {
        throw new Exception('Could not create category: ' . $name);
    }
    $report['categories_created']++;
    return (int)$conn->insert_id;
}

function find_product($conn, $barcode, $name)
{
    if ($barcode !== '') {
        $q = $conn->query("SELECT * FROM products WHERE delete_flag = 0 AND barcode = '" . esc_sql($conn, $barcode) . "' LIMIT 1");
        if ($q && $q->num_rows) {
            return $q->fetch_assoc();
        }
    }
    $q = $conn->query("SELECT * FROM products WHERE delete_flag = 0 AND LOWER(name) = LOWER('" . esc_sql($conn, $name) . "') LIMIT 1");
    if ($q && $q->num_rows) {
        return $q->fetch_assoc();
    }
    return null;
}

function find_inventory($conn, $product_id, $variant)
{
    $q = $conn->query("SELECT * FROM inventory WHERE product_id = '" . (int)$product_id . "' AND variant = '" . esc_sql($conn, $variant) . "' LIMIT 1");
    if ($q && $q->num_rows) {
        return $q->fetch_assoc();
    }
    if ($variant !== 'Default') {
        $q = $conn->query("SELECT * FROM inventory WHERE product_id = '" . (int)$product_id . "' AND variant = 'Default' LIMIT 1");
        if ($q && $q->num_rows) {
            return $q->fetch_assoc();
        }
    }
    return null;
}

function sold_qty($conn, $inventory_id)
{
    $q = $conn->query("SELECT COALESCE(SUM(ol.quantity), 0) AS sold
        FROM order_list ol
        INNER JOIN orders o ON o.id = ol.order_id
        WHERE o.status != 4 AND ol.inventory_id = '" . (int)$inventory_id . "'");
    if ($q && ($row = $q->fetch_assoc())) {
        return (float)$row['sold'];
    }
    return 0.0;
}

function normalize_line_item($item)
{
    $qty = (float)$item['qty'];
    $total = (float)$item['total'];
    $unit = isset($item['unit_price']) ? (float)$item['unit_price'] : 0.0;
    if ($unit <= 0 && $qty > 0) {
        $unit = round($total / $qty, 2);
    }
    return array(
        'code' => normalize_barcode($item['code']),
        'name' => trim($item['name']),
        'qty' => $qty,
        'unit_price' => $unit,
        'total' => $total,
        'variant' => extract_variant($item['name']),
    );
}

function upsert_product_inventory($conn, $line, $supplier, $default_category, $default_brand_fallback, $retail_markup, $dry_run, &$report)
{
    $brand_name = $supplier !== '' ? $supplier : $default_brand_fallback;
    $brand_id = ensure_brand($conn, $brand_name, $dry_run, $report);
    $category_id = ensure_category($conn, $default_category, $dry_run, $report);

    $product = find_product($conn, $line['code'], $line['name']);
    $product_id = 0;
    $inventory_id = 0;

    if (!$product) {
        $report['products_created']++;
        if (!$dry_run) {
            $barcode_sql = $line['code'] !== '' ? "'" . esc_sql($conn, $line['code']) . "'" : 'NULL';
            $specs = htmlentities('<p>Imported from wholesale purchase receipt.</p>');
            $sql = "INSERT INTO products SET
                brand_id = '" . (int)$brand_id . "',
                category_id = '" . (int)$category_id . "',
                name = '" . esc_sql($conn, $line['name']) . "',
                barcode = {$barcode_sql},
                specs = '" . esc_sql($conn, $specs) . "',
                status = 1,
                delete_flag = 0";
            if (!$conn->query($sql)) {
                throw new Exception('Failed to create product: ' . $line['name']);
            }
            $product_id = (int)$conn->insert_id;
        }
    } else {
        $product_id = (int)$product['id'];
        $report['products_matched']++;
        if (!$dry_run && $line['code'] !== '' && empty($product['barcode'])) {
            $conn->query("UPDATE products SET barcode = '" . esc_sql($conn, $line['code']) . "' WHERE id = '" . $product_id . "'");
            $report['barcodes_assigned']++;
        }
    }

    $inventory = ($product_id > 0) ? find_inventory($conn, $product_id, $line['variant']) : null;
    $retail = suggest_retail_price($line['unit_price'], $inventory ? $inventory['price'] : 0, $retail_markup);

    if (!$inventory) {
        $report['inventory_created']++;
        if (!$dry_run && $product_id > 0) {
            $stored_qty = (float)$line['qty'];
            $sql = "INSERT INTO inventory SET
                product_id = '" . $product_id . "',
                variant = '" . esc_sql($conn, $line['variant']) . "',
                quantity = '" . $stored_qty . "',
                price = '" . (float)$retail . "',
                cost_price = '" . (float)$line['unit_price'] . "'";
            if (!$conn->query($sql)) {
                throw new Exception('Failed to create inventory for: ' . $line['name']);
            }
            $inventory_id = (int)$conn->insert_id;
        }
    } else {
        $inventory_id = (int)$inventory['id'];
        $report['inventory_updated']++;
        if (!$dry_run) {
            $sold = sold_qty($conn, $inventory_id);
            $new_stored = (float)$inventory['quantity'] + (float)$line['qty'];
            $retail = suggest_retail_price($line['unit_price'], $inventory['price'], $retail_markup);
            $sql = "UPDATE inventory SET
                quantity = '" . $new_stored . "',
                price = '" . (float)$retail . "',
                cost_price = '" . (float)$line['unit_price'] . "'
                WHERE id = '" . $inventory_id . "'";
            if (!$conn->query($sql)) {
                throw new Exception('Failed to update inventory for: ' . $line['name']);
            }
            $report['stock_added'] += (float)$line['qty'];
            unset($sold);
        }
    }

    if ($dry_run && !$inventory) {
        $report['stock_added'] += (float)$line['qty'];
    }

    return array(
        'product_id' => $product_id,
        'inventory_id' => $inventory_id,
    );
}

$report = array(
    'receipts_imported' => 0,
    'receipts_skipped' => 0,
    'line_items' => 0,
    'products_created' => 0,
    'products_matched' => 0,
    'barcodes_assigned' => 0,
    'inventory_created' => 0,
    'inventory_updated' => 0,
    'stock_added' => 0,
    'brands_created' => 0,
    'categories_created' => 0,
    'errors' => array(),
);

echo ($dry_run ? "DRY RUN — no database writes\n" : "Importing wholesale purchase receipts (KES)\n");
echo str_repeat('-', 60) . "\n";

if (!ensure_purchase_tables($conn, $dry_run)) {
    exit(1);
}

foreach ($receipts as $receipt) {
    $receipt_no = $receipt['receipt_no'];
    if (receipt_exists($conn, $receipt_no)) {
        echo "SKIP receipt {$receipt_no} — already imported.\n";
        $report['receipts_skipped']++;
        continue;
    }

    $line_sum = 0.0;
    $normalized_items = array();
    foreach ($receipt['items'] as $idx => $item) {
        $line = normalize_line_item($item);
        $line_sum += $line['total'];
        $normalized_items[] = $line;
    }

    $header_total = (float)$receipt['total'];
    $notes = '';
    if (abs($line_sum - $header_total) > 0.01) {
        $notes = 'Line total ' . number_format($line_sum, 2) . ' differs from receipt total ' . number_format($header_total, 2) . '. Header total stored.';
    }

    echo ($dry_run ? '[dry-run] ' : '') . "Receipt {$receipt_no} | {$receipt['supplier']} | {$receipt['date']} | KES " . number_format($header_total, 2) . "\n";

    if ($dry_run) {
        $report['receipts_imported']++;
        foreach ($normalized_items as $i => $line) {
            $report['line_items']++;
            echo "  line " . ($i + 1) . ": [{$line['code']}] {$line['name']} x{$line['qty']} @ " . number_format($line['unit_price'], 2) . " = " . number_format($line['total'], 2) . " (variant: {$line['variant']})\n";
            upsert_product_inventory($conn, $line, $receipt['supplier'], $default_category, $default_brand_fallback, $retail_markup, true, $report);
        }
        if ($notes !== '') {
            echo "  note: {$notes}\n";
        }
        continue;
    }

    $conn->begin_transaction();
    try {
        $sql = "INSERT INTO purchase_receipts SET
            receipt_no = '" . esc_sql($conn, $receipt_no) . "',
            supplier = '" . esc_sql($conn, $receipt['supplier']) . "',
            receipt_date = '" . esc_sql($conn, $receipt['date']) . "',
            customer = '" . esc_sql($conn, $receipt['customer']) . "',
            total_amount = '" . $header_total . "',
            currency = 'KES',
            notes = " . ($notes !== '' ? "'" . esc_sql($conn, $notes) . "'" : 'NULL');
        if (!$conn->query($sql)) {
            throw new Exception('Failed to insert purchase receipt header: ' . $conn->error);
        }
        $purchase_id = (int)$conn->insert_id;

        foreach ($normalized_items as $i => $line) {
            $ids = upsert_product_inventory($conn, $line, $receipt['supplier'], $default_category, $default_brand_fallback, $retail_markup, false, $report);
            $sql = "INSERT INTO purchase_receipt_items SET
                purchase_receipt_id = '" . $purchase_id . "',
                line_no = '" . ($i + 1) . "',
                item_code = " . ($line['code'] !== '' ? "'" . esc_sql($conn, $line['code']) . "'" : 'NULL') . ",
                item_name = '" . esc_sql($conn, $line['name']) . "',
                quantity = '" . $line['qty'] . "',
                unit_price = '" . $line['unit_price'] . "',
                line_total = '" . $line['total'] . "',
                product_id = " . ($ids['product_id'] > 0 ? "'" . $ids['product_id'] . "'" : 'NULL') . ",
                inventory_id = " . ($ids['inventory_id'] > 0 ? "'" . $ids['inventory_id'] . "'" : 'NULL') . ",
                variant = '" . esc_sql($conn, $line['variant']) . "'";
            if (!$conn->query($sql)) {
                throw new Exception('Failed to insert purchase line item: ' . $conn->error);
            }
            $report['line_items']++;
            echo "  + [{$line['code']}] {$line['name']} x{$line['qty']} (stock +{$line['qty']})\n";
        }

        $conn->commit();
        $report['receipts_imported']++;
        if ($notes !== '') {
            echo "  note: {$notes}\n";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $report['errors'][] = "Receipt {$receipt_no}: " . $e->getMessage();
        echo "  ERROR: " . $e->getMessage() . "\n";
    }
}

echo str_repeat('-', 60) . "\n";
echo "Summary\n";
echo "  Receipts imported: {$report['receipts_imported']}\n";
echo "  Receipts skipped (duplicate): {$report['receipts_skipped']}\n";
echo "  Line items: {$report['line_items']}\n";
echo "  Products created: {$report['products_created']}\n";
echo "  Products matched: {$report['products_matched']}\n";
echo "  Barcodes assigned: {$report['barcodes_assigned']}\n";
echo "  Inventory rows created: {$report['inventory_created']}\n";
echo "  Inventory rows updated: {$report['inventory_updated']}\n";
echo "  Stock units added: {$report['stock_added']}\n";
echo "  Brands created: {$report['brands_created']}\n";
echo "  Categories created: {$report['categories_created']}\n";
if (!empty($report['errors'])) {
    echo "  Errors: " . count($report['errors']) . "\n";
    foreach ($report['errors'] as $err) {
        echo "    - {$err}\n";
    }
    exit(1);
}
echo "Done.\n";
