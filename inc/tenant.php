<?php
/**
 * Multi-tenant helpers for Kalmoy POS SaaS.
 */

if (!function_exists('tenant_id')) {
    function tenant_id()
    {
        if (!isset($_SESSION['userdata']['business_id'])) {
            return 0;
        }
        return (int) $_SESSION['userdata']['business_id'];
    }
}

if (!function_exists('tenant_sql')) {
    /** Safe AND clause for business_id (never from request input). */
    function tenant_sql($alias = '')
    {
        $bid = tenant_id();
        if ($bid <= 0) {
            return ' AND 1=0 ';
        }
        $col = ($alias !== '') ? rtrim($alias, '.') . '.business_id' : 'business_id';
        return " AND {$col} = '{$bid}' ";
    }
}

if (!function_exists('tenant_bid')) {
    function tenant_bid()
    {
        return tenant_id();
    }
}

if (!function_exists('tenant_owned')) {
    function tenant_owned($conn, $table, $id, $idCol = 'id')
    {
        $bid = tenant_id();
        $id = (int) $id;
        if ($bid <= 0 || $id <= 0 || !isset($conn) || !$conn) {
            return false;
        }
        $table = preg_replace('/[^a-z0-9_]/i', '', $table);
        $idCol = preg_replace('/[^a-z0-9_]/i', '', $idCol);
        $q = $conn->query("SELECT id FROM `{$table}` WHERE `{$idCol}` = '{$id}' AND business_id = '{$bid}' LIMIT 1");
        return ($q && $q->num_rows > 0);
    }
}

if (!function_exists('tenant_require_auth')) {
    function tenant_require_auth()
    {
        if (!isset($_SESSION['userdata']) || (int) ($_SESSION['userdata']['login_type'] ?? 0) !== 1) {
            return false;
        }
        if (tenant_id() <= 0) {
            return false;
        }
        return true;
    }
}

if (!function_exists('tenant_subscription_status')) {
    function tenant_subscription_status($business_id = null)
    {
        global $conn;
        $bid = $business_id !== null ? (int) $business_id : tenant_id();
        if ($bid <= 0 || !isset($conn) || !$conn) {
            return array('allowed' => false, 'status' => 'none', 'message' => 'No business context.');
        }
        $bq = $conn->query("SELECT status FROM businesses WHERE id = '{$bid}' LIMIT 1");
        if (!$bq || !$bq->num_rows) {
            return array('allowed' => false, 'status' => 'none', 'message' => 'Business not found.');
        }
        $bstatus = $bq->fetch_assoc()['status'];
        if (in_array($bstatus, array('suspended', 'inactive', 'cancelled'), true)) {
            return array('allowed' => false, 'status' => $bstatus, 'message' => 'This business account is suspended.');
        }
        $effective = SubscriptionService::effective($conn, $bid);
        if (empty($effective['subscription'])) {
            return array('allowed' => false, 'status' => 'none', 'message' => 'No active subscription.');
        }
        $messages = array(
            'trial' => 'Trial active.',
            'active' => 'Subscription active.',
            'expired' => 'Subscription has expired.',
            'suspended' => 'Subscription suspended.',
            'cancelled' => 'Subscription cancelled.',
        );
        $status = $effective['status'];
        return array('allowed' => !empty($effective['allowed']), 'status' => $status, 'message' => isset($messages[$status]) ? $messages[$status] : 'Subscription not valid.', 'subscription' => $effective['subscription']);
    }
}

if (!function_exists('tenant_require_subscription')) {
    function tenant_require_subscription($allowRenewalPage = false)
    {
        $sub = tenant_subscription_status();
        if ($sub['allowed']) {
            return true;
        }
        if ($allowRenewalPage) {
            return false;
        }
        return false;
    }
}

if (!function_exists('tenant_api_guard')) {
    function tenant_api_guard()
    {
        if (!tenant_require_auth()) {
            http_response_code(401);
            echo json_encode(array('status' => 'failed', 'msg' => 'Authentication required.'));
            exit;
        }
        $sub = tenant_subscription_status();
        if (!$sub['allowed']) {
            http_response_code(403);
            echo json_encode(array('status' => 'failed', 'msg' => $sub['message'], 'subscription' => $sub['status']));
            exit;
        }
    }
}

if (!function_exists('tenant_csrf_token')) {
    function tenant_csrf_token()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('tenant_verify_csrf')) {
    function tenant_verify_csrf($token)
    {
        return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('platform_logged_in')) {
    function platform_logged_in()
    {
        return isset($_SESSION['platform_user']) && is_array($_SESSION['platform_user']);
    }
}

if (!function_exists('platform_user')) {
    function platform_user($key = null)
    {
        if (!platform_logged_in()) {
            return null;
        }
        if ($key === null) {
            return $_SESSION['platform_user'];
        }
        return $_SESSION['platform_user'][$key] ?? null;
    }
}

if (!function_exists('platform_audit_log')) {
    function platform_audit_log($action, $details = '', $business_id = null)
    {
        global $conn;
        if (!isset($conn) || !$conn) {
            return false;
        }
        $uid = platform_user('id');
        $uname = platform_user('username');
        $action = $conn->real_escape_string((string) $action);
        $details = $conn->real_escape_string((string) $details);
        $uidSql = $uid ? (int) $uid : 'NULL';
        $unameSql = $uname ? "'".$conn->real_escape_string($uname)."'" : 'NULL';
        $bidSql = ($business_id !== null && (int)$business_id > 0) ? (int) $business_id : 'NULL';
        return $conn->query("INSERT INTO platform_audit_log SET platform_user_id = {$uidSql}, username = {$unameSql}, action = '{$action}', details = '{$details}', business_id = {$bidSql}, date_created = NOW()");
    }
}

if (!function_exists('tenant_setting_get')) {
    function tenant_setting_get($field, $default = '')
    {
        global $conn;
        $bid = tenant_id();
        if ($bid <= 0 || !isset($conn) || !$conn) {
            return $default;
        }
        $field = $conn->real_escape_string((string) $field);
        $q = $conn->query("SELECT meta_value FROM business_settings WHERE business_id = '{$bid}' AND meta_field = '{$field}' LIMIT 1");
        if ($q && $q->num_rows > 0) {
            return $q->fetch_assoc()['meta_value'];
        }
        return $default;
    }
}

if (!function_exists('tenant_setting_set')) {
    function tenant_setting_set($field, $value)
    {
        global $conn;
        $bid = tenant_id();
        if ($bid <= 0 || !isset($conn) || !$conn) {
            return false;
        }
        $field = $conn->real_escape_string((string) $field);
        $value = $conn->real_escape_string((string) $value);
        $exists = $conn->query("SELECT id FROM business_settings WHERE business_id = '{$bid}' AND meta_field = '{$field}' LIMIT 1");
        if ($exists && $exists->num_rows > 0) {
            return $conn->query("UPDATE business_settings SET meta_value = '{$value}' WHERE business_id = '{$bid}' AND meta_field = '{$field}'");
        }
        return $conn->query("INSERT INTO business_settings SET business_id = '{$bid}', meta_field = '{$field}', meta_value = '{$value}'");
    }
}

if (!function_exists('tenant_short_name')) {
    function tenant_short_name($business_name)
    {
        $name = trim((string) $business_name);
        if ($name === '') {
            return '';
        }
        if (preg_match('/^(.+?)\s+Test$/i', $name, $m)) {
            return trim($m[1]);
        }
        if (strlen($name) <= 30) {
            return $name;
        }
        $parts = preg_split('/\s+/', $name, 3);
        return implode(' ', array_slice($parts, 0, 2));
    }
}

if (!function_exists('tenant_upload_dir')) {
    function tenant_upload_dir($business_id = null)
    {
        $bid = $business_id !== null ? (int) $business_id : tenant_id();
        if ($bid <= 0) {
            return 'uploads/';
        }
        return 'uploads/business_' . $bid . '/';
    }
}

if (!function_exists('tenant_ensure_upload_dir')) {
    function tenant_ensure_upload_dir($business_id = null)
    {
        $dir = base_app . tenant_upload_dir($business_id);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }
}

if (!function_exists('tenant_setting_upsert')) {
    function tenant_setting_upsert($business_id, $field, $value, $dbConn = null)
    {
        global $conn;
        if ($dbConn !== null) {
            $conn = $dbConn;
        }
        $bid = (int) $business_id;
        if ($bid <= 0 || !isset($conn) || !$conn) {
            return false;
        }
        $field = $conn->real_escape_string((string) $field);
        $value = $conn->real_escape_string((string) $value);
        $exists = $conn->query("SELECT id FROM business_settings WHERE business_id = '{$bid}' AND meta_field = '{$field}' LIMIT 1");
        if ($exists && $exists->num_rows > 0) {
            return (bool) $conn->query("UPDATE business_settings SET meta_value = '{$value}' WHERE business_id = '{$bid}' AND meta_field = '{$field}'");
        }
        return (bool) $conn->query("INSERT INTO business_settings SET business_id = '{$bid}', meta_field = '{$field}', meta_value = '{$value}'");
    }
}

if (!function_exists('tenant_seed_default_settings')) {
    function tenant_seed_default_settings($business_id, $business_name, $dbConn = null)
    {
        global $conn;
        if ($dbConn !== null) {
            $conn = $dbConn;
        }
        $bid = (int) $business_id;
        if ($bid <= 0 || !isset($conn) || !$conn) {
            return false;
        }
        $defaults = array(
            'name' => $business_name,
            'short_name' => tenant_short_name($business_name),
            'about_us' => '',
            'privacy_policy' => '',
            'currency' => 'KES',
            'currency_symbol' => 'Ksh',
            'low_stock_threshold' => '10',
            'business_start_date' => date('Y-m-d'),
            'cashier_permissions' => json_encode(admin_default_cashier_permissions()),
        );
        foreach ($defaults as $k => $v) {
            tenant_setting_upsert($bid, $k, $v, $conn);
        }
        return true;
    }
}

if (!function_exists('tenant_seed_default_catalog')) {
    function tenant_seed_default_catalog($business_id, $dbConn = null)
    {
        global $conn;
        if ($dbConn !== null) {
            $conn = $dbConn;
        }
        $bid = (int) $business_id;
        if ($bid <= 0 || !isset($conn) || !$conn) {
            return false;
        }
        $hasBrand = $conn->query("SELECT id FROM brands WHERE business_id = '{$bid}' AND delete_flag = 0 LIMIT 1");
        if (!$hasBrand || !$hasBrand->num_rows) {
            $conn->query("INSERT INTO brands (business_id, name, description, status) VALUES ('{$bid}', 'House Brand', '', 1)");
        }
        $hasCat = $conn->query("SELECT id FROM categories WHERE business_id = '{$bid}' AND delete_flag = 0 LIMIT 1");
        if (!$hasCat || !$hasCat->num_rows) {
            $conn->query("INSERT INTO categories (business_id, category, description, status) VALUES ('{$bid}', 'General', '', 1)");
        }
        return true;
    }
}

if (!function_exists('tenant_ensure_business_settings')) {
    function tenant_ensure_business_settings($business_id, $dbConn = null)
    {
        global $conn;
        if ($dbConn !== null) {
            $conn = $dbConn;
        }
        $bid = (int) $business_id;
        if ($bid <= 0 || !isset($conn) || !$conn) {
            return false;
        }
        $bq = $conn->query("SELECT name FROM businesses WHERE id = '{$bid}' LIMIT 1");
        if (!$bq || !$bq->num_rows) {
            return false;
        }
        $businessName = $bq->fetch_assoc()['name'];
        $changed = false;

        $existing = array();
        $sq = $conn->query("SELECT meta_field, meta_value FROM business_settings WHERE business_id = '{$bid}'");
        if ($sq) {
            while ($row = $sq->fetch_assoc()) {
                $existing[$row['meta_field']] = $row['meta_value'];
            }
        }
        if (empty($existing)) {
            tenant_seed_default_settings($bid, $businessName, $conn);
            return true;
        }

        $storedName = trim((string) ($existing['name'] ?? ''));
        if ($storedName === '' || $storedName === 'Kalmoy POS') {
            if (trim($businessName) !== '' && $storedName !== $businessName) {
                tenant_setting_upsert($bid, 'name', $businessName, $conn);
                $changed = true;
            }
        }
        $storedShort = trim((string) ($existing['short_name'] ?? ''));
        $expectedShort = tenant_short_name($businessName);
        if ($storedShort === '' || $storedShort === 'Kalmoy POS' || ($storedShort === $businessName && $expectedShort !== '' && $expectedShort !== $businessName)) {
            if ($expectedShort !== '' && $storedShort !== $expectedShort) {
                tenant_setting_upsert($bid, 'short_name', $expectedShort, $conn);
                $changed = true;
            }
        }
        foreach (array('about_us', 'privacy_policy') as $field) {
            if (!array_key_exists($field, $existing)) {
                tenant_setting_upsert($bid, $field, '', $conn);
                $changed = true;
            }
        }
        return $changed;
    }
}

if (!function_exists('tenant_ensure_tenant_resources')) {
    function tenant_ensure_tenant_resources($business_id, $dbConn = null)
    {
        $settingsChanged = tenant_ensure_business_settings($business_id, $dbConn);
        tenant_seed_default_catalog($business_id, $dbConn);
        return $settingsChanged;
    }
}

if (!function_exists('admin_default_cashier_permissions')) {
    function admin_default_cashier_permissions()
    {
        $perms = array();
        foreach (admin_permission_catalog() as $key => $meta) {
            if (!empty($meta['admin_only'])) {
                $perms[$key] = 0;
            } else {
                $perms[$key] = in_array($key, array('pos', 'orders_view', 'clients', 'my_account', 'dashboard_limited'), true) ? 1 : 0;
            }
        }
        return $perms;
    }
}

if (!function_exists('tenant_backup_tables')) {
    function tenant_backup_tables()
    {
        return array(
            'business_settings', 'users', 'brands', 'categories', 'products', 'inventory',
            'clients', 'orders', 'order_list', 'sales', 'cart', 'expenses',
            'customer_debts', 'debt_payments', 'purchase_receipts', 'purchase_receipt_items',
            'notifications', 'backup_logs', 'admin_activity_log',
        );
    }
}

if (!function_exists('tenant_generate_sql_dump')) {
    function tenant_generate_sql_dump($conn)
    {
        $bid = tenant_id();
        if ($bid <= 0 || !isset($conn) || !$conn) {
            return false;
        }
        $out = "-- Kalmoy POS tenant backup (business_id={$bid})\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        foreach (tenant_backup_tables() as $table) {
            $tq = $conn->query("SHOW TABLES LIKE '{$table}'");
            if (!$tq || !$tq->num_rows) {
                continue;
            }
            $rows = $conn->query("SELECT * FROM `{$table}` WHERE business_id = '{$bid}'");
            if (!$rows || $rows->num_rows === 0) {
                continue;
            }
            $out .= "-- Table: {$table}\n";
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
                $out .= 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(', ', $vals) . ");\n";
            }
            $out .= "\n";
        }
        return $out;
    }
}
