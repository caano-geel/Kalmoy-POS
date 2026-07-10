<?php
/**
 * FK-safe business data cleanup for admin Backup page and CLI tools.
 */
class BusinessDataCleaner
{
    const SCOPE_SALES = 'sales';
    const SCOPE_PURCHASES = 'purchases';
    const SCOPE_EXPENSES = 'expenses';
    const SCOPE_NOTIFICATIONS = 'notifications';
    const SCOPE_FULL = 'full';

    private $conn;
    private $master_product_names = array(
        'Arabica', 'Ashwaganda', 'Assantee', 'Bahasha Kilkilaha', 'Batana Oil', 'Biotin', 'Black Horse', 'Boost',
        'Calcium', 'CBC', 'Chocolate', 'DR5', 'Face Wash', 'Gates Bay', 'Glysolid Mid', 'Glysolid Pig', 'Glysolid Small',
        'Hadjoul', 'Hair Treatmen', 'Happy Cleaner', 'Indho-Kuul', 'Jilbahaha', 'Kojii', 'Location Pig', 'M2 Tone',
        'Macun', 'Macun Bac', 'Magnesium', 'Mayonese', 'Mega', 'Men Coffee', 'Mino Glow', 'Minoxidil', 'Miski', 'MK',
        'Morocan', 'Neem', 'Oliva', 'Organic', 'Papaya', 'Penfera', 'Pretty Small', 'Prostate', 'Qays', 'Sakiin',
        'Saliid Herbal', 'Scar Remover', 'Shampoo 3 in 1', 'Shampoo Mino Glow', 'Shark Power', 'Shilajid Seamoses',
        'Silky', 'Skala', 'Skin Doctor', 'Slim Cream', 'Spray', 'Teeth Restoration', 'Titan hel', 'Tumeric', 'Ultra',
        'Unsi', 'Vitamin C', 'Xanjo',
    );

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public static function allowedScopes()
    {
        return array(
            self::SCOPE_SALES,
            self::SCOPE_PURCHASES,
            self::SCOPE_EXPENSES,
            self::SCOPE_NOTIFICATIONS,
            self::SCOPE_FULL,
        );
    }

    public function run($scope, $options = array())
    {
        $scope = strtolower(trim((string)$scope));
        if (!in_array($scope, self::allowedScopes(), true)) {
            return array('status' => 'failed', 'msg' => 'Invalid clean scope.');
        }

        $backup = $this->createBackup();
        if ($backup['status'] !== 'success') {
            return $backup;
        }

        $this->conn->query('SET FOREIGN_KEY_CHECKS=0');
        try {
            $summary = array('scope' => $scope, 'tables' => array(), 'backup_file' => $backup['filename']);
            switch ($scope) {
                case self::SCOPE_SALES:
                    $summary['tables'] = $this->cleanSales();
                    $label = 'Sales data cleaned';
                    $action = 'data_clean_sales';
                    break;
                case self::SCOPE_PURCHASES:
                    $reset_stock = !isset($options['reset_stock']) || (bool)$options['reset_stock'];
                    $summary['tables'] = $this->cleanPurchases($reset_stock);
                    $summary['reset_stock'] = $reset_stock;
                    $label = 'Purchase data cleaned';
                    $action = 'data_clean_purchases';
                    break;
                case self::SCOPE_EXPENSES:
                    $summary['tables'] = $this->cleanExpenses();
                    $label = 'Expenses cleaned';
                    $action = 'data_clean_expenses';
                    break;
                case self::SCOPE_NOTIFICATIONS:
                    $summary['tables'] = $this->cleanNotifications();
                    $label = 'Notifications cleaned';
                    $action = 'data_clean_notifications';
                    break;
                case self::SCOPE_FULL:
                    $summary['tables'] = $this->cleanFullBusiness();
                    $label = 'Full business reset completed';
                    $action = 'data_clean_full';
                    break;
                default:
                    throw new Exception('Unsupported scope.');
            }
            $this->conn->query('SET FOREIGN_KEY_CHECKS=1');
            if (function_exists('admin_activity_log')) {
                admin_activity_log($action, $label . ' | Backup: ' . $backup['filename']);
            }
            return array(
                'status' => 'success',
                'msg' => $label . '. Backup saved as ' . $backup['filename'] . '.',
                'summary' => $summary,
            );
        } catch (Exception $e) {
            $this->conn->query('SET FOREIGN_KEY_CHECKS=1');
            return array('status' => 'failed', 'msg' => $e->getMessage());
        }
    }

    public function createBackup()
    {
        if (!function_exists('backup_dir_path')) {
            return array('status' => 'failed', 'msg' => 'Backup helpers are not available.');
        }
        $dir = backup_dir_path();
        $now = date('Y-m-d H:i:s');
        $filename = 'Kalmoy_Backup_pre_clean_' . date('Y-m-d_His') . '.sql';
        $filepath = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;
        $dump = $this->generateSqlDump();
        if ($dump === false) {
            return array('status' => 'failed', 'msg' => 'Failed to generate backup.');
        }
        if (file_put_contents($filepath, $dump) === false) {
            return array('status' => 'failed', 'msg' => 'Failed to write backup file.');
        }
        $size = filesize($filepath);
        if (function_exists('backup_logs_table_enabled') && backup_logs_table_enabled()) {
            $user_id = isset($_SESSION['userdata']['id']) ? (int)$_SESSION['userdata']['id'] : 0;
            $user_name = function_exists('dashboard_user_display_name') ? dashboard_user_display_name() : 'System';
            $file_esc = $this->conn->real_escape_string($filename);
            $name_esc = $this->conn->real_escape_string($user_name);
            $msg_esc = $this->conn->real_escape_string('Automatic backup before data clean');
            $this->conn->query("INSERT INTO backup_logs SET filename = '{$file_esc}', file_size = '{$size}',
                created_by = '{$user_id}', created_by_name = '{$name_esc}', status = 'success',
                message = '{$msg_esc}', date_created = '{$now}'");
        }
        return array('status' => 'success', 'filename' => $filename, 'filepath' => $filepath, 'size' => $size);
    }

    private function generateSqlDump()
    {
        $out = "-- Kalmoy POS automatic backup before data clean\n-- Date: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";
        $tables = $this->conn->query('SHOW TABLES');
        if (!$tables) {
            return false;
        }
        while ($trow = $tables->fetch_array()) {
            $table = $trow[0];
            $create = $this->conn->query("SHOW CREATE TABLE `{$table}`");
            if (!$create) {
                continue;
            }
            $crow = $create->fetch_array();
            $out .= "DROP TABLE IF EXISTS `{$table}`;\n" . $crow[1] . ";\n\n";
            $rows = $this->conn->query("SELECT * FROM `{$table}`");
            if ($rows && $rows->num_rows > 0) {
                while ($row = $rows->fetch_assoc()) {
                    $cols = array_keys($row);
                    $vals = array();
                    foreach ($row as $v) {
                        if ($v === null) {
                            $vals[] = 'NULL';
                        } else {
                            $vals[] = "'" . $this->conn->real_escape_string($v) . "'";
                        }
                    }
                    $out .= 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(', ', $vals) . ");\n";
                }
                $out .= "\n";
            }
        }
        $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $out;
    }

    private function cleanSales()
    {
        $tables = array('order_list', 'sales', 'cart', 'orders');
        return $this->deleteTables($tables);
    }

    private function cleanPurchases($reset_stock)
    {
        $result = array();
        if ($reset_stock && $this->tableExists('purchase_receipt_items')) {
            $result['inventory_stock_adjusted'] = $this->reversePurchaseStock();
        }
        $tables = array('purchase_receipt_items', 'purchase_receipts');
        $deleted = $this->deleteTables($tables);
        return array_merge($result, $deleted);
    }

    private function cleanExpenses()
    {
        if (!function_exists('expenses_table_enabled') || !expenses_table_enabled()) {
            return array('expenses' => 0);
        }
        return $this->deleteTables(array('expenses'));
    }

    private function cleanNotifications()
    {
        if (!$this->tableExists('notifications')) {
            return array('notifications' => 0);
        }
        return $this->deleteTables(array('notifications'));
    }

    private function cleanFullBusiness()
    {
        $result = array();
        $sales = $this->cleanSales();
        $purchases = $this->cleanPurchases(true);
        $expenses = $this->cleanExpenses();
        $notifications = $this->cleanNotifications();
        $inventory = $this->deleteTables(array('inventory'));
        $products = $this->removeNonMasterProducts();

        $result = array_merge($result, $sales, $purchases, $expenses, $notifications, $inventory, $products);
        if ($this->tableExists('admin_activity_log')) {
            $result = array_merge($result, $this->deleteTables(array('admin_activity_log')));
        }
        return $result;
    }

    private function reversePurchaseStock()
    {
        if (!$this->tableExists('purchase_receipt_items')) {
            return 0;
        }
        $adjustments = array();
        $q = $this->conn->query("SELECT inventory_id, SUM(quantity) AS qty
            FROM purchase_receipt_items
            WHERE inventory_id IS NOT NULL AND inventory_id > 0
            GROUP BY inventory_id");
        while ($q && ($row = $q->fetch_assoc())) {
            $adjustments[(int)$row['inventory_id']] = (float)$row['qty'];
        }
        $adjusted = 0;
        foreach ($adjustments as $inventory_id => $subtract) {
            if ($subtract <= 0) {
                continue;
            }
            $inv_q = $this->conn->query("SELECT quantity FROM inventory WHERE id = '" . (int)$inventory_id . "' LIMIT 1");
            if (!$inv_q || !$inv_q->num_rows) {
                continue;
            }
            $current = (float)$inv_q->fetch_assoc()['quantity'];
            $sold = $this->soldQty($inventory_id);
            $new_qty = max($sold, $current - $subtract);
            $this->conn->query("UPDATE inventory SET quantity = '" . $new_qty . "' WHERE id = '" . (int)$inventory_id . "'");
            $adjusted++;
        }
        return $adjusted;
    }

    private function removeNonMasterProducts()
    {
        $removed = 0;
        $kept = 0;
        $pq = $this->conn->query('SELECT id, name, specs, delete_flag FROM products');
        $remove_ids = array();
        while ($pq && ($row = $pq->fetch_assoc())) {
            if ($this->isMasterProduct($row['name'])) {
                $kept++;
            } else {
                $remove_ids[] = (int)$row['id'];
            }
        }
        if (!empty($remove_ids)) {
            $ids = implode(',', $remove_ids);
            $this->conn->query("DELETE FROM products WHERE id IN ({$ids})");
            $removed = count($remove_ids);
        }
        return array('products_removed' => $removed, 'products_kept' => $kept);
    }

    private function deleteTables($tables)
    {
        $result = array();
        foreach ($tables as $table) {
            if (!$this->tableExists($table)) {
                $result[$table] = 0;
                continue;
            }
            $count_q = $this->conn->query("SELECT COUNT(*) AS c FROM `{$table}`");
            $count = ($count_q && ($r = $count_q->fetch_assoc())) ? (int)$r['c'] : 0;
            if (!$this->conn->query("DELETE FROM `{$table}`")) {
                throw new Exception('Failed to clean `' . $table . '`: ' . $this->conn->error);
            }
            $this->conn->query("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
            $result[$table] = $count;
        }
        return $result;
    }

    private function tableExists($table)
    {
        $t = $this->conn->real_escape_string($table);
        $q = $this->conn->query("SHOW TABLES LIKE '{$t}'");
        return ($q && $q->num_rows > 0);
    }

    private function soldQty($inventory_id)
    {
        $q = $this->conn->query("SELECT COALESCE(SUM(ol.quantity), 0) AS sold
            FROM order_list ol
            INNER JOIN orders o ON o.id = ol.order_id
            WHERE o.status != 4 AND ol.inventory_id = '" . (int)$inventory_id . "'");
        if ($q && ($row = $q->fetch_assoc())) {
            return (float)$row['sold'];
        }
        return 0.0;
    }

    private function isMasterProduct($name)
    {
        foreach ($this->master_product_names as $master) {
            if (strcasecmp(trim($name), trim($master)) === 0) {
                return true;
            }
        }
        return false;
    }
}
