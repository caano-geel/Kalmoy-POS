<?php
/**
 * Reset business/transaction data, backup first, then re-import wholesale receipts.
 *
 * KEEPS: users, system_info (settings), categories, brands (structure), walk-in client
 * CLEANS: sales, orders, cart, expenses, purchases, inventory, non-master products,
 *          activity logs, notifications, backup log rows
 *
 * Usage:
 *   php database/reset_business_data.php              # backup + clean + import receipts
 *   php database/reset_business_data.php --dry-run    # preview only
 *   php database/reset_business_data.php --clean-only # backup + clean, no import
 */
require_once __DIR__ . '/../config.php';

$dry_run = in_array('--dry-run', $argv, true);
$clean_only = in_array('--clean-only', $argv, true);

if (!isset($conn) || !$conn) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

/** Official master catalog — kept with zero stock when present. */
$master_product_names = array(
    'Arabica', 'Ashwaganda', 'Assantee', 'Bahasha Kilkilaha', 'Batana Oil', 'Biotin', 'Black Horse', 'Boost',
    'Calcium', 'CBC', 'Chocolate', 'DR5', 'Face Wash', 'Gates Bay', 'Glysolid Mid', 'Glysolid Pig', 'Glysolid Small',
    'Hadjoul', 'Hair Treatmen', 'Happy Cleaner', 'Indho-Kuul', 'Jilbahaha', 'Kojii', 'Location Pig', 'M2 Tone',
    'Macun', 'Macun Bac', 'Magnesium', 'Mayonese', 'Mega', 'Men Coffee', 'Mino Glow', 'Minoxidil', 'Miski', 'MK',
    'Morocan', 'Neem', 'Oliva', 'Organic', 'Papaya', 'Penfera', 'Pretty Small', 'Prostate', 'Qays', 'Sakiin',
    'Saliid Herbal', 'Scar Remover', 'Shampoo 3 in 1', 'Shampoo Mino Glow', 'Shark Power', 'Shilajid Seamoses',
    'Silky', 'Skala', 'Skin Doctor', 'Slim Cream', 'Spray', 'Teeth Restoration', 'Titan hel', 'Tumeric', 'Ultra',
    'Unsi', 'Vitamin C', 'Xanjo',
);

$tables_cleaned = array();
$report = array(
    'backup_file' => '',
    'rows_deleted' => array(),
    'products_kept' => 0,
    'products_removed' => 0,
    'inventory_reset' => 0,
);

function esc_sql($conn, $value)
{
    return $conn->real_escape_string((string)$value);
}

function table_exists($conn, $table)
{
    $t = esc_sql($conn, $table);
    $q = $conn->query("SHOW TABLES LIKE '{$t}'");
    return ($q && $q->num_rows > 0);
}

function count_rows($conn, $table)
{
    if (!table_exists($conn, $table)) {
        return 0;
    }
    $q = $conn->query("SELECT COUNT(*) AS c FROM `{$table}`");
    return ($q && ($r = $q->fetch_assoc())) ? (int)$r['c'] : 0;
}

function delete_all($conn, $table, $dry_run, &$tables_cleaned, &$report)
{
    if (!table_exists($conn, $table)) {
        return;
    }
    $before = count_rows($conn, $table);
    if ($dry_run) {
        echo "[dry-run] Would DELETE {$before} rows from `{$table}`\n";
    } else {
        if (!$conn->query("DELETE FROM `{$table}`")) {
            throw new Exception("DELETE FROM `{$table}` failed: " . $conn->error);
        }
    }
    $tables_cleaned[] = $table;
    $report['rows_deleted'][$table] = $before;
}

function reset_auto_increment($conn, $table, $dry_run)
{
    if (!table_exists($conn, $table)) {
        return;
    }
    if ($dry_run) {
        echo "[dry-run] Would reset AUTO_INCREMENT on `{$table}`\n";
        return;
    }
    $conn->query("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
}

function export_database_backup($conn, $dir)
{
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $filename = 'Kalmoy_Backup_pre_reset_' . date('Y-m-d_His') . '.sql';
    $filepath = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;
    $fh = fopen($filepath, 'w');
    if (!$fh) {
        throw new Exception('Cannot write backup file: ' . $filepath);
    }
    fwrite($fh, "-- Kalmoy POS backup before business data reset\n");
    fwrite($fh, '-- Generated: ' . date('Y-m-d H:i:s') . "\n\n");
    fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n\n");
    $tables = $conn->query('SHOW TABLES');
    while ($tables && ($trow = $tables->fetch_array())) {
        $table = $trow[0];
        $create = $conn->query("SHOW CREATE TABLE `{$table}`");
        if (!$create) {
            continue;
        }
        $crow = $create->fetch_array();
        fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n" . $crow[1] . ";\n\n");
        $rows = $conn->query("SELECT * FROM `{$table}`");
        if ($rows && $rows->num_rows > 0) {
            while ($row = $rows->fetch_assoc()) {
                $cols = array_keys($row);
                $vals = array();
                foreach ($row as $v) {
                    if ($v === null) {
                        $vals[] = 'NULL';
                    } else {
                        $vals[] = "'" . $conn->real_escape_string($v) . "'";
                    }
                }
                fwrite($fh, 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(', ', $vals) . ");\n");
            }
            fwrite($fh, "\n");
        }
    }
    fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fh);
    return $filepath;
}

function is_dummy_product_row($row)
{
    $name = strtolower(trim($row['name']));
    if ((int)$row['delete_flag'] === 1) {
        return true;
    }
    if (strpos($name, 'sample') === 0 || $name === 'test' || $name === 'book1' || $name === 'tiish') {
        return true;
    }
    if (preg_match('/^book\d*$/', $name)) {
        return true;
    }
    $specs = strtolower(html_entity_decode(strip_tags($row['specs'])));
    if (strpos($specs, 'dummy') !== false || strpos($specs, 'seed data') !== false || strpos($specs, 'test data') !== false) {
        return true;
    }
    return false;
}

function is_master_product($name, $master_names)
{
    foreach ($master_names as $master) {
        if (strcasecmp(trim($name), trim($master)) === 0) {
            return true;
        }
    }
    return false;
}

function ensure_walkin_client($conn, $dry_run)
{
    $q = $conn->query("SELECT id FROM clients WHERE email = 'pos.walkin@local' LIMIT 1");
    if ($q && $q->num_rows) {
        return;
    }
    if ($dry_run) {
        echo "[dry-run] Would ensure walk-in POS client exists.\n";
        return;
    }
    $conn->query("INSERT INTO clients (
        firstname, lastname, gender, contact, email, password,
        default_delivery_address, status, delete_flag, date_created
    ) VALUES (
        'Walk-in', 'Customer', 'N/A', '0000000000', 'pos.walkin@local',
        'e516cfe749aa7f31dbaf567b07985bde', 'In-Store POS', 1, 0, NOW()
    )");
}

function clean_non_master_clients($conn, $dry_run)
{
    $before = count_rows($conn, 'clients');
    if ($dry_run) {
        echo "[dry-run] Would delete {$before} non-walk-in clients (keeping pos.walkin@local).\n";
        return;
    }
    $conn->query("DELETE FROM clients WHERE email != 'pos.walkin@local'");
}

echo ($dry_run ? "DRY RUN — business data reset\n" : "Business data reset\n");
echo str_repeat('-', 60) . "\n";

try {
    if (!$dry_run) {
        $backup = export_database_backup($conn, backup_dir_path());
        $report['backup_file'] = $backup;
        echo "Backup saved: {$backup}\n\n";
    } else {
        echo "[dry-run] Would create full database backup in uploads/backups/\n\n";
    }

    if (!$dry_run) {
        $conn->query('SET FOREIGN_KEY_CHECKS=0');
    }

    // Child tables first (FK-safe order)
    $ordered_tables = array(
        'purchase_receipt_items',
        'purchase_receipts',
        'order_list',
        'sales',
        'cart',
        'orders',
        'expenses',
        'notifications',
        'admin_activity_log',
        'backup_logs',
    );

    foreach ($ordered_tables as $table) {
        delete_all($conn, $table, $dry_run, $tables_cleaned, $report);
    }

    // Inventory: remove all rows (stock comes from receipt import)
    delete_all($conn, 'inventory', $dry_run, $tables_cleaned, $report);

    // Products: remove dummy + non-master; keep master catalog only
    $products_to_keep = array();
    $products_to_remove = array();
    $pq = $conn->query('SELECT id, name, specs, delete_flag FROM products');
    while ($pq && ($row = $pq->fetch_assoc())) {
        $id = (int)$row['id'];
        if (is_master_product($row['name'], $master_product_names)) {
            $products_to_keep[] = $id;
        } elseif (is_dummy_product_row($row)) {
            $products_to_remove[] = $id;
        } else {
            $products_to_remove[] = $id;
        }
    }

    $report['products_kept'] = count($products_to_keep);
    $report['products_removed'] = count($products_to_remove);

    if ($dry_run) {
        echo "[dry-run] Would keep {$report['products_kept']} master products (stock 0).\n";
        echo "[dry-run] Would remove {$report['products_removed']} products.\n";
    } else {
        if (!empty($products_to_remove)) {
            $ids = implode(',', array_map('intval', $products_to_remove));
            if (!$conn->query("DELETE FROM products WHERE id IN ({$ids})")) {
                throw new Exception('Failed to delete products: ' . $conn->error);
            }
        }
        if (!empty($products_to_keep)) {
            $ids = implode(',', array_map('intval', $products_to_keep));
            $conn->query("UPDATE products SET delete_flag = 0, status = 1 WHERE id IN ({$ids})");
        }
        echo "Products kept (master): {$report['products_kept']}\n";
        echo "Products removed: {$report['products_removed']}\n";
    }
    $tables_cleaned[] = 'products (selective delete)';

    clean_non_master_clients($conn, $dry_run);
    $tables_cleaned[] = 'clients (walk-in only)';

    ensure_walkin_client($conn, $dry_run);

    $auto_tables = array(
        'purchase_receipt_items',
        'purchase_receipts',
        'order_list',
        'sales',
        'cart',
        'orders',
        'expenses',
        'inventory',
        'notifications',
        'admin_activity_log',
        'backup_logs',
    );
    foreach ($auto_tables as $table) {
        reset_auto_increment($conn, $table, $dry_run);
    }

    if (!$dry_run) {
        $conn->query('SET FOREIGN_KEY_CHECKS=1');
    }

    echo "\n" . str_repeat('-', 60) . "\n";
    echo "Cleanup summary\n";
    foreach ($report['rows_deleted'] as $table => $count) {
        echo "  {$table}: {$count} rows removed\n";
    }
    echo "  Tables touched: " . implode(', ', array_unique($tables_cleaned)) . "\n";
    echo "  Preserved: users, system_info, categories, brands\n";

    if ($dry_run || $clean_only) {
        if ($clean_only && !$dry_run) {
            echo "\nClean complete (--clean-only, import skipped).\n";
        }
        exit(0);
    }

    echo "\nRunning wholesale receipt import...\n";
    echo str_repeat('-', 60) . "\n";
    passthru('"' . PHP_BINARY . '" "' . __DIR__ . '/import_wholesale_receipts.php"', $exit_code);
    if ($exit_code !== 0) {
        throw new Exception('Receipt import failed with exit code ' . $exit_code);
    }

    // Post-import dashboard sanity check
    $sales_today = 0;
    $orders_today = 0;
    $pending = 0;
    $q = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM orders WHERE DATE(date_created) = CURDATE() AND status != 4");
    if ($q) {
        $sales_today = (float)$q->fetch_assoc()['t'];
    }
    $q = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE DATE(date_created) = CURDATE() AND status != 4");
    if ($q) {
        $orders_today = (int)$q->fetch_assoc()['c'];
    }
    $q = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status = 0");
    if ($q) {
        $pending = (int)$q->fetch_assoc()['c'];
    }

    echo "\nDashboard check (should be zero before any new POS sales today):\n";
    echo "  Sales today: Ksh " . number_format($sales_today, 2) . "\n";
    echo "  Orders today: {$orders_today}\n";
    echo "  Open orders: {$pending}\n";
    echo "\nDone.\n";
} catch (Exception $e) {
    if (!$dry_run) {
        @$conn->query('SET FOREIGN_KEY_CHECKS=1');
    }
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
