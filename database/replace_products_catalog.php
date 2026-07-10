<?php
/**
 * Replace active product catalog with the official master product list.
 * Creates SQL backup first. Preserves sales history (order_list) via soft-delete + inventory retention.
 *
 * Usage: php database/replace_products_catalog.php
 */
require_once __DIR__ . '/../config.php';

$product_names = array(
    'Arabica', 'Ashwaganda', 'Assantee', 'Bahasha Kilkilaha', 'Batana Oil', 'Biotin', 'Black Horse', 'Boost',
    'Calcium', 'CBC', 'Chocolate', 'DR5', 'Face Wash', 'Gates Bay', 'Glysolid Mid', 'Glysolid Pig', 'Glysolid Small',
    'Hadjoul', 'Hair Treatmen', 'Happy Cleaner', 'Indho-Kuul', 'Jilbahaha', 'Kojii', 'Location Pig', 'M2 Tone',
    'Macun', 'Macun Bac', 'Magnesium', 'Mayonese', 'Mega', 'Men Coffee', 'Mino Glow', 'Minoxidil', 'Miski', 'MK',
    'Morocan', 'Neem', 'Oliva', 'Organic', 'Papaya', 'Penfera', 'Pretty Small', 'Prostate', 'Qays', 'Sakiin',
    'Saliid Herbal', 'Scar Remover', 'Shampoo 3 in 1', 'Shampoo Mino Glow', 'Shark Power', 'Shilajid Seamoses',
    'Silky', 'Skala', 'Skin Doctor', 'Slim Cream', 'Spray', 'Teeth Restoration', 'Titan hel', 'Tumeric', 'Ultra',
    'Unsi', 'Vitamin C', 'Xanjo',
);

$category_for = array(
    'Arabica' => 'Herbal Drinks & Foods',
    'Ashwaganda' => 'Herbal Supplements',
    'Assantee' => 'Beauty & Cosmetics',
    'Bahasha Kilkilaha' => 'Traditional Herbal Products',
    'Batana Oil' => 'Natural Oils',
    'Biotin' => 'Herbal Supplements',
    'Black Horse' => "Men's Health",
    'Boost' => 'Energy & Wellness',
    'Calcium' => 'Herbal Supplements',
    'CBC' => 'Herbal Supplements',
    'Chocolate' => 'Herbal Drinks & Foods',
    'DR5' => 'Herbal Supplements',
    'Face Wash' => 'Personal Care',
    'Gates Bay' => 'Beauty & Cosmetics',
    'Glysolid Mid' => 'Skin Care',
    'Glysolid Pig' => 'Skin Care',
    'Glysolid Small' => 'Skin Care',
    'Hadjoul' => 'Traditional Herbal Products',
    'Hair Treatmen' => 'Hair Care',
    'Happy Cleaner' => 'Personal Care',
    'Indho-Kuul' => 'Beauty & Cosmetics',
    'Jilbahaha' => 'Traditional Herbal Products',
    'Kojii' => 'Skin Care',
    'Location Pig' => 'Skin Care',
    'M2 Tone' => "Women's Health",
    'Macun' => 'Traditional Herbal Products',
    'Macun Bac' => 'Traditional Herbal Products',
    'Magnesium' => 'Herbal Supplements',
    'Mayonese' => 'Personal Care',
    'Mega' => 'Herbal Supplements',
    'Men Coffee' => 'Herbal Drinks & Foods',
    'Mino Glow' => 'Beauty & Cosmetics',
    'Minoxidil' => 'Hair Care',
    'Miski' => 'Perfumes & Fragrances',
    'MK' => 'Herbal Supplements',
    'Morocan' => 'Natural Oils',
    'Neem' => 'Traditional Herbal Products',
    'Oliva' => 'Natural Oils',
    'Organic' => 'Hair Care',
    'Papaya' => 'Skin Care',
    'Penfera' => 'Beauty & Cosmetics',
    'Pretty Small' => 'Beauty & Cosmetics',
    'Prostate' => "Men's Health",
    'Qays' => 'Beauty & Cosmetics',
    'Sakiin' => 'Herbal Drinks & Foods',
    'Saliid Herbal' => 'Natural Oils',
    'Scar Remover' => 'Skin Care',
    'Shampoo 3 in 1' => 'Hair Care',
    'Shampoo Mino Glow' => 'Hair Care',
    'Shark Power' => "Men's Health",
    'Shilajid Seamoses' => 'Herbal Supplements',
    'Silky' => 'Hair Care',
    'Skala' => 'Hair Care',
    'Skin Doctor' => 'Skin Care',
    'Slim Cream' => 'Weight Management',
    'Spray' => 'Personal Care',
    'Teeth Restoration' => 'Oral & Dental Care',
    'Titan hel' => "Men's Health",
    'Tumeric' => 'Herbal Supplements',
    'Ultra' => 'Herbal Supplements',
    'Unsi' => 'Beauty & Cosmetics',
    'Vitamin C' => 'Herbal Supplements',
    'Xanjo' => 'Herbal Supplements',
);

$brand_name = 'Generic';
$default_price = 1000;
$default_qty = 0;

function ean13_check_digit($twelveDigits){
    $sum = 0;
    for($i = 0; $i < 12; $i++){
        $sum += (int)$twelveDigits[$i] * ($i % 2 === 0 ? 1 : 3);
    }
    return (string)((10 - ($sum % 10)) % 10);
}

function ash_ean13_for_index($index){
    $base = '628' . str_pad((string)$index, 9, '0', STR_PAD_LEFT);
    return $base . ean13_check_digit($base);
}

function sql_quote($conn, $value){
    return "'" . $conn->real_escape_string($value) . "'";
}

function export_table_backup($conn, $table, $fh){
    fwrite($fh, "\n-- Table: {$table}\n");
    $q = $conn->query("SELECT * FROM `{$table}`");
    if(!$q) return;
    while($row = $q->fetch_assoc()){
        $cols = array_keys($row);
        $vals = array();
        foreach($row as $v){
            if($v === null) $vals[] = 'NULL';
            else $vals[] = "'" . $conn->real_escape_string($v) . "'";
        }
        fwrite($fh, "INSERT INTO `{$table}` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $vals) . ");\n");
    }
}

if(!isset($conn) || !$conn){
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$backup_file = __DIR__ . '/backup_products_before_replace_' . date('Y-m-d_His') . '.sql';
$fh = fopen($backup_file, 'w');
fwrite($fh, "-- Kalmoy POS product catalog backup\n");
fwrite($fh, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");
fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n");
foreach(array('cart', 'inventory', 'products', 'brands', 'categories') as $tbl){
    export_table_backup($conn, $tbl, $fh);
}
fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
fclose($fh);
echo "Backup saved: {$backup_file}\n";

$report = array(
    'inserted' => 0,
    'reactivated' => 0,
    'soft_deleted' => 0,
    'hard_deleted_products' => 0,
    'inventory_created' => 0,
    'brands_created' => 0,
    'categories_created' => 0,
    'skipped' => array(),
    'corrected' => array(),
    'barcodes_assigned' => 0,
);

$conn->begin_transaction();
try{
    $conn->query('DELETE FROM cart');

    $brand_id = 0;
    $bq = $conn->query("SELECT id FROM brands WHERE delete_flag = 0 AND LOWER(name) = LOWER('Generic') LIMIT 1");
    if($bq && $bq->num_rows){
        $brand_id = (int)$bq->fetch_assoc()['id'];
    }else{
        $conn->query("INSERT INTO brands SET name = 'Generic', status = 1");
        $brand_id = (int)$conn->insert_id;
        $report['brands_created']++;
    }

    $category_ids = array();
    $cq = $conn->query("SELECT id, category FROM categories WHERE delete_flag = 0");
    while($cq && ($cr = $cq->fetch_assoc())){
        $category_ids[$cr['category']] = (int)$cr['id'];
    }
    $needed_categories = array_unique(array_values($category_for));
    foreach($needed_categories as $cat_name){
        if(isset($category_ids[$cat_name])) continue;
        $esc = $conn->real_escape_string($cat_name);
        $conn->query("INSERT INTO categories SET category = '{$esc}', status = 1");
        $category_ids[$cat_name] = (int)$conn->insert_id;
        $report['categories_created']++;
    }

    $canonical = array();
    foreach($product_names as $name){
        $canonical[strtolower(trim($name))] = trim($name);
    }

    $conn->query('UPDATE products SET delete_flag = 1, status = 0');

    $used_barcodes = array();
    $be = $conn->query("SELECT barcode FROM products WHERE barcode IS NOT NULL AND barcode != ''");
    while($be && ($br = $be->fetch_assoc())){
        $used_barcodes[$br['barcode']] = true;
    }

    $barcode_index = 1000;
    $active_ids = array();

    foreach($product_names as $name){
        $cat_name = isset($category_for[$name]) ? $category_for[$name] : 'Personal Care';
        $category_id = $category_ids[$cat_name];
        $specs = htmlentities('<p>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ' is a product stocked at Kalmoy POS.</p>');
        $esc_name = $conn->real_escape_string($name);

        $pq = $conn->query("SELECT id, barcode, delete_flag FROM products WHERE LOWER(name) = LOWER('{$esc_name}') ORDER BY id ASC LIMIT 1");
        $product_id = 0;
        $was_deleted = false;
        if($pq && $pq->num_rows){
            $prow = $pq->fetch_assoc();
            $product_id = (int)$prow['id'];
            $was_deleted = ((int)$prow['delete_flag'] === 1);
            $dup = $conn->query("SELECT id FROM products WHERE LOWER(name) = LOWER('{$esc_name}') AND id != {$product_id}");
            if($dup && $dup->num_rows){
                while($d = $dup->fetch_assoc()){
                    $conn->query('UPDATE products SET delete_flag = 1, status = 0 WHERE id = ' . (int)$d['id']);
                    $report['corrected'][] = 'Merged duplicate product #' . $d['id'] . ' into #' . $product_id . ' (' . $name . ')';
                }
            }
            $barcode = trim((string)$prow['barcode']);
            if($barcode === ''){
                do{
                    $barcode = ash_ean13_for_index($barcode_index++);
                }while(isset($used_barcodes[$barcode]));
                $used_barcodes[$barcode] = true;
                $report['barcodes_assigned']++;
            }
            $sql = "UPDATE products SET
                brand_id = '{$brand_id}',
                category_id = '{$category_id}',
                name = '{$esc_name}',
                barcode = " . sql_quote($conn, $barcode) . ",
                specs = " . sql_quote($conn, $specs) . ",
                status = 1,
                delete_flag = 0
                WHERE id = '{$product_id}'";
            $conn->query($sql);
            if($was_deleted) $report['reactivated']++;
        }else{
            do{
                $barcode = ash_ean13_for_index($barcode_index++);
            }while(isset($used_barcodes[$barcode]));
            $used_barcodes[$barcode] = true;
            $sql = "INSERT INTO products SET
                brand_id = '{$brand_id}',
                category_id = '{$category_id}',
                name = '{$esc_name}',
                barcode = " . sql_quote($conn, $barcode) . ",
                specs = " . sql_quote($conn, $specs) . ",
                status = 1,
                delete_flag = 0";
            $conn->query($sql);
            $product_id = (int)$conn->insert_id;
            $report['inserted']++;
            $report['barcodes_assigned']++;
        }
        $active_ids[] = $product_id;

        $iq = $conn->query("SELECT id FROM inventory WHERE product_id = '{$product_id}' AND variant = 'Default' LIMIT 1");
        if($iq && $iq->num_rows){
            $inv_id = (int)$iq->fetch_assoc()['id'];
            $conn->query("UPDATE inventory SET price = IF(price <= 0, {$default_price}, price) WHERE id = '{$inv_id}'");
        }else{
            $conn->query("INSERT INTO inventory SET product_id = '{$product_id}', variant = 'Default', quantity = '{$default_qty}', price = '{$default_price}', cost_price = '0'");
            $report['inventory_created']++;
        }
    }

    $sold_inventory = array();
    $sq = $conn->query('SELECT DISTINCT inventory_id FROM order_list');
    while($sq && ($sr = $sq->fetch_assoc())){
        $sold_inventory[(int)$sr['inventory_id']] = true;
    }

    $inactive = $conn->query('SELECT id FROM products WHERE delete_flag = 1');
    while($inactive && ($row = $inactive->fetch_assoc())){
        $pid = (int)$row['id'];
        $report['soft_deleted']++;
        $inv_q = $conn->query("SELECT id FROM inventory WHERE product_id = '{$pid}'");
        while($inv_q && ($inv = $inv_q->fetch_assoc())){
            $iid = (int)$inv['id'];
            if(!isset($sold_inventory[$iid])){
                $conn->query("DELETE FROM inventory WHERE id = '{$iid}'");
            }
        }
        $has_sales = $conn->query("SELECT 1 FROM inventory i INNER JOIN order_list ol ON ol.inventory_id = i.id WHERE i.product_id = '{$pid}' LIMIT 1");
        if(!$has_sales || $has_sales->num_rows === 0){
            $conn->query("DELETE FROM products WHERE id = '{$pid}'");
            $report['hard_deleted_products']++;
        }
    }

    if(function_exists('notifications_sync_system')){
        notifications_sync_system();
    }

    $conn->commit();
}catch(Exception $e){
    $conn->rollback();
    fwrite(STDERR, "FAILED: " . $e->getMessage() . "\n");
    exit(1);
}

$active_count = $conn->query('SELECT COUNT(*) AS c FROM products WHERE delete_flag = 0')->fetch_assoc()['c'];
echo "\n=== Product catalog replacement complete ===\n";
echo "Backup file: {$backup_file}\n";
echo "Active products in database: {$active_count}\n";
echo "New products inserted: {$report['inserted']}\n";
echo "Existing products reactivated/updated: {$report['reactivated']}\n";
echo "Products soft-deleted (sales history retained): " . max(0, $report['soft_deleted'] - $report['hard_deleted_products']) . "\n";
echo "Products hard-deleted (no sales): {$report['hard_deleted_products']}\n";
echo "Inventory rows created: {$report['inventory_created']}\n";
echo "Barcodes assigned: {$report['barcodes_assigned']}\n";
echo "Brands created: {$report['brands_created']}\n";
echo "Categories created: {$report['categories_created']}\n";
if(!empty($report['corrected'])){
    echo "Corrections:\n";
    foreach($report['corrected'] as $c) echo "  - {$c}\n";
}
if(!empty($report['skipped'])){
    echo "Skipped:\n";
    foreach($report['skipped'] as $s) echo "  - {$s}\n";
}else{
    echo "Skipped: none\n";
}
