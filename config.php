<?php
/**
 * Main application bootstrap (helpers, session, DB handle).
 * Environment / DB credentials are loaded by initialize.php from:
 *   config.local.php (XAMPP) or config.production.php (InfinityFree).
 * Do not put passwords in this file.
 */
ob_start();
require_once('initialize.php');
app_set_kenya_timezone();
session_start();
require_once('classes/DBConnection.php');
require_once('classes/SystemSettings.php');
require_once('classes/CustomerDebtService.php');
require_once('classes/MpesaService.php');
require_once('classes/SubscriptionService.php');
require_once('classes/PaymentService.php');
if(file_exists(__DIR__ . '/vendor/autoload.php')){
    require_once(__DIR__ . '/vendor/autoload.php');
}
$db = new DBConnection;
$conn = $db->conn;
require_once(__DIR__ . '/inc/tenant.php');

function redirect($url=''){
	if(!empty($url))
	echo '<script>location.href="'.base_url .$url.'"</script>';
}
function validate_image($file){
	if(!empty($file)){
			// exit;
        $ex = explode("?",$file);
        $file = $ex[0];
        $ts = isset($ex[1]) ? "?".$ex[1] : '';
		if(is_file(base_app.$file)){
			return base_url.$file.$ts;
		}else{
			return base_url.'dist/img/no-image-available.png';
		}
	}else{
		return base_url.'dist/img/no-image-available.png';
	}
}
function format_num($number = '' , $decimal = ''){
    if(is_numeric($number)){
        $ex = explode(".",$number);
        $decLen = isset($ex[1]) ? strlen($ex[1]) : 0;
        if(is_numeric($decimal)){
            return number_format($number,$decimal);
        }else{
            return number_format($number,$decLen);
        }
    }else{
        return "Invalid Input";
    }
}
function format_price($number = '' , $decimal = ''){
    if($number === '' || $number === null || !is_numeric($number)){
        $number = 0;
    }else{
        $number = $number + 0;
    }
    $ex = explode(".",(string)$number);
    $decLen = isset($ex[1]) ? strlen($ex[1]) : 0;
    if(is_numeric($decimal)){
        return 'Ksh '.number_format($number,$decimal);
    }
    return 'Ksh '.number_format($number,$decLen);
}
function isMobileDevice(){
    $aMobileUA = array(
        '/iphone/i' => 'iPhone', 
        '/ipod/i' => 'iPod', 
        '/ipad/i' => 'iPad', 
        '/android/i' => 'Android', 
        '/blackberry/i' => 'BlackBerry', 
        '/webos/i' => 'Mobile'
    );

    //Return true if Mobile User Agent is detected
    foreach($aMobileUA as $sMobileKey => $sMobileOS){
        if(preg_match($sMobileKey, $_SERVER['HTTP_USER_AGENT'])){
            return true;
        }
    }
    //Otherwise return false..  
    return false;
}
function admin_user_type(){
    if(!isset($_SESSION['userdata']['type']))
        return 1;
    return (int)$_SESSION['userdata']['type'];
}
/**
 * Role definitions (users.type in database).
 * 1 = Admin/Owner, 2 = Cashier/Shop Keeper
 */
function admin_role_definitions(){
    return array(
        1 => array('key' => 'owner', 'label' => 'Business Owner'),
        2 => array('key' => 'cashier', 'label' => 'Cashier'),
        3 => array('key' => 'manager', 'label' => 'Manager'),
        4 => array('key' => 'stock_manager', 'label' => 'Stock Manager'),
        5 => array('key' => 'accountant', 'label' => 'Accountant'),
    );
}
/**
 * Post-login landing page per role (users.type).
 * Value: admin path relative to site root (no leading slash).
 */
function admin_role_landing_pages(){
    return array(
        1 => 'admin/',
        2 => 'admin/?page=pos',
        3 => 'admin/',
        4 => 'admin/?page=inventory',
        5 => 'admin/?page=analytics',
    );
}
function admin_is_cashier(){
    return isset($_SESSION['userdata']['login_type'])
        && $_SESSION['userdata']['login_type'] == 1
        && admin_user_type() === 2;
}
function admin_is_owner(){
    return isset($_SESSION['userdata']['login_type'])
        && (int)$_SESSION['userdata']['login_type'] === 1
        && admin_user_type() === 1;
}
function app_hash_password($password){
    return password_hash((string)$password, PASSWORD_DEFAULT);
}
function app_verify_password($password, $hash){
    $hash = (string)$hash;
    if($hash === '') return false;
    if(strlen($hash) === 32 && ctype_xdigit($hash)){
        return hash_equals($hash, md5((string)$password));
    }
    return password_verify((string)$password, $hash);
}
function app_upgrade_password_hash($user_id, $plain){
    global $conn;
    if(!isset($conn) || !$conn) return false;
    $hash = app_hash_password($plain);
    $hash_esc = $conn->real_escape_string($hash);
    return $conn->query("UPDATE users SET password = '{$hash_esc}' WHERE id = '".(int)$user_id."'");
}
function users_table_has_status(){
    return db_table_has_column('users', 'status');
}
function users_ensure_schema(){
    global $conn;
    if(!isset($conn) || !$conn) return false;
    static $done = false;
    if($done) return users_table_has_status();
    $columns = array(
        'email' => "ADD COLUMN `email` varchar(250) DEFAULT NULL AFTER `username`",
        'phone' => "ADD COLUMN `phone` varchar(50) DEFAULT NULL AFTER `email`",
        'status' => "ADD COLUMN `status` tinyint(1) NOT NULL DEFAULT 1 AFTER `type`",
        'permissions' => "ADD COLUMN `permissions` text DEFAULT NULL AFTER `status`",
    );
    foreach($columns as $col => $ddl){
        if(!db_table_has_column('users', $col)){
            @$conn->query("ALTER TABLE `users` {$ddl}");
        }
    }
    if(users_table_has_status()){
        @$conn->query("UPDATE `users` SET `status` = 1 WHERE `status` IS NULL");
    }
    $done = true;
    return users_table_has_status();
}
function admin_can_view_profit(){
    return admin_cashier_can('profit_analytics');
}
function db_table_has_column($table, $column){
    global $conn;
    static $cache = array();
    $key = strtolower($table).'.'.strtolower($column);
    if(array_key_exists($key, $cache))
        return $cache[$key];
    $cache[$key] = false;
    if(!isset($conn) || !$conn)
        return false;
    $table = preg_replace('/[^a-z0-9_]/i', '', $table);
    $column = preg_replace('/[^a-z0-9_]/i', '', $column);
    if($table === '' || $column === '')
        return false;
    $q = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    if($q && $q->num_rows > 0)
        $cache[$key] = true;
    return $cache[$key];
}
function inventory_low_stock_threshold(){
    global $_settings;
    $val = '';
    if(isset($_settings)){
        $val = $_settings->info('low_stock_threshold');
    }
    if($val === '' || $val === null || !is_numeric($val)){
        return 5;
    }
    $val = (int)$val;
    return $val < 0 ? 0 : $val;
}
function inventory_sold_subquery_sql(){
    $ts = function_exists('tenant_sql') ? tenant_sql('o') : '';
    return "(
        SELECT ol.inventory_id, SUM(ol.quantity) AS qty
        FROM order_list ol
        INNER JOIN orders o ON o.id = ol.order_id
        WHERE o.status != 4{$ts}
        GROUP BY ol.inventory_id
    )";
}
function inventory_available_stock_sql($inventory_alias = 'i'){
    return "({$inventory_alias}.quantity - IFNULL(sold.qty, 0))";
}
function inventory_stock_status($available, $threshold = null){
    if($threshold === null){
        $threshold = inventory_low_stock_threshold();
    }
    $available = (float)$available;
    if($available <= 0){
        return 'out';
    }
    if($available <= (float)$threshold){
        return 'low';
    }
    return 'in';
}
function inventory_stock_status_label($status){
    switch($status){
        case 'out':
            return 'Out of Stock';
        case 'low':
            return 'Low Stock';
        default:
            return 'In Stock';
    }
}
function inventory_stock_status_badge($status){
    switch($status){
        case 'out':
            return '<span class="ash-status-badge badge ash-stock-out">Out of Stock</span>';
        case 'low':
            return '<span class="ash-status-badge badge ash-stock-low">Low Stock</span>';
        default:
            return '<span class="ash-status-badge badge ash-stock-in">In Stock</span>';
    }
}
function inventory_stock_counts($threshold = null){
    global $conn;
    if($threshold === null){
        $threshold = inventory_low_stock_threshold();
    }
    if(!isset($conn) || !$conn){
        return array('low' => 0, 'out' => 0);
    }
    $threshold = (int)$threshold;
    $avail_sql = inventory_available_stock_sql('i');
    $sold_sub = inventory_sold_subquery_sql();
    $sql = "SELECT
        SUM(CASE WHEN {$avail_sql} <= 0 THEN 1 ELSE 0 END) AS out_count,
        SUM(CASE WHEN {$avail_sql} > 0 AND {$avail_sql} <= {$threshold} THEN 1 ELSE 0 END) AS low_count
        FROM inventory i
        INNER JOIN products p ON p.id = i.product_id
        LEFT JOIN {$sold_sub} sold ON sold.inventory_id = i.id
        WHERE p.delete_flag = 0 AND p.status = 1".tenant_sql('i');
    $qry = $conn->query($sql);
    if($qry && ($row = $qry->fetch_assoc())){
        return array(
            'low' => (int)$row['low_count'],
            'out' => (int)$row['out_count'],
        );
    }
    return array('low' => 0, 'out' => 0);
}
function app_cost_price_column(){
    global $conn;
    static $column = false;
    if($column !== false) return $column;
    $column = null;
    if(!isset($conn) || !$conn) return $column;
    $check = $conn->query("SHOW COLUMNS FROM `inventory`");
    if($check){
        $allowed = array('cost_price', 'cost', 'buy_price');
        while($col = $check->fetch_assoc()){
            if(in_array(strtolower($col['Field']), $allowed, true)){
                $column = $col['Field'];
                break;
            }
        }
    }
    return $column;
}
function dashboard_order_customer_name($delivery_address, $client_name){
    if(!empty($delivery_address) && preg_match('/Customer:\s*(.+)$/i', $delivery_address, $matches)){
        return trim($matches[1]);
    }
    $name = trim($client_name);
    if($name === '' || stripos($name, 'Walk-in') !== false) return 'Walk-in Customer';
    return $name;
}
function dashboard_payment_label($method){
    if(strcasecmp($method, 'Cash') === 0) return 'Cash';
    if(strcasecmp($method, 'M-Pesa') === 0) return 'M-Pesa';
    return $method;
}
function dashboard_sales_trend_days($days = 7){
    $chart = dashboard_chart_analytics($days === 7 ? '7d' : '30d');
    return array(
        'labels' => $chart['labels'],
        'values' => $chart['sales'],
    );
}
function dashboard_chart_range_bounds($range){
    $today = date('Y-m-d');
    switch($range){
        case '30d':
            return array(
                'start' => report_clamp_date(date('Y-m-d', strtotime('-29 days'))),
                'end' => business_effective_end_date(),
                'mode' => 'day',
                'title' => 'Last 30 days',
            );
        case 'month':
            return array(
                'start' => report_clamp_date(date('Y-m-01')),
                'end' => business_effective_end_date(),
                'mode' => 'day',
                'title' => 'This Month',
            );
        case 'year':
            return array(
                'start' => report_clamp_date(date('Y-01-01')),
                'end' => business_effective_end_date(),
                'mode' => 'month',
                'title' => 'This Year',
            );
        case 'week':
            return array(
                'start' => report_clamp_date(date('Y-m-d', strtotime('monday this week'))),
                'end' => business_effective_end_date(),
                'mode' => 'day',
                'title' => 'This Week',
            );
        case 'today':
            $d = report_clamp_date(date('Y-m-d'));
            return array(
                'start' => $d,
                'end' => $d,
                'mode' => 'day',
                'title' => 'Today',
            );
        case 'lifetime':
            $bounds = business_operating_bounds();
            return array(
                'start' => $bounds['start'],
                'end' => $bounds['end'],
                'mode' => 'month',
                'title' => 'All Time',
            );
        default:
            return array(
                'start' => report_clamp_date(date('Y-m-d', strtotime('-6 days'))),
                'end' => business_effective_end_date(),
                'mode' => 'day',
                'title' => 'Last 7 days',
            );
    }
}
function dashboard_chart_init_buckets($start, $end, $mode){
    $buckets = array();
    if($mode === 'month'){
        $cur = strtotime(date('Y-m-01', strtotime($start)));
        $end_ts = strtotime(date('Y-m-01', strtotime($end)));
        while($cur <= $end_ts){
            $key = date('Y-m', $cur);
            $buckets[$key] = array(
                'label' => date('M Y', $cur),
                'sales' => 0,
                'orders' => 0,
                'profit' => 0,
            );
            $cur = strtotime('+1 month', $cur);
        }
        return $buckets;
    }
    $cur = strtotime($start);
    $end_ts = strtotime($end);
    while($cur <= $end_ts){
        $key = date('Y-m-d', $cur);
        $buckets[$key] = array(
            'label' => date('M d', $cur),
            'sales' => 0,
            'orders' => 0,
            'profit' => 0,
        );
        $cur = strtotime('+1 day', $cur);
    }
    return $buckets;
}
function dashboard_profit_by_buckets($date_start, $date_end, $mode = 'day'){
    if(!admin_can_view_profit()) return array();
    global $conn;
    if(!isset($conn) || !$conn) return array();
    $cost_column = app_cost_price_column();
    if(!$cost_column) return array();
    $date_start = date('Y-m-d', strtotime($date_start));
    $date_end = date('Y-m-d', strtotime($date_end));
    $bucket_expr = $mode === 'month'
        ? "DATE_FORMAT(s.date_created, '%Y-%m')"
        : 'DATE(s.date_created)';
    if(db_table_has_column('order_list', 'cost_price')){
        $cost_select = "COALESCE(NULLIF(ol.cost_price, ''), i.`{$cost_column}`) AS cost_price";
    }else{
        $cost_select = "i.`{$cost_column}` AS cost_price";
    }
    $discount_select = db_table_has_column('orders', 'discount_total') ? 'o.discount_total' : '0 AS discount_total';
    $sql = "SELECT {$bucket_expr} AS bucket, o.id AS order_id, ol.quantity, ol.price, {$discount_select}, {$cost_select}
        FROM sales s
        INNER JOIN orders o ON o.id = s.order_id
        INNER JOIN order_list ol ON ol.order_id = o.id
        INNER JOIN inventory i ON ol.inventory_id = i.id
        WHERE DATE(s.date_created) BETWEEN '{$date_start}' AND '{$date_end}'";
    $qry = $conn->query($sql);
    if(!$qry) return array();
    $rows = array();
    while($row = $qry->fetch_assoc()){
        $rows[] = $row;
    }
    $order_subtotals = array();
    foreach($rows as $row){
        $oid = $row['order_id'];
        if(!isset($order_subtotals[$oid])) $order_subtotals[$oid] = 0;
        $order_subtotals[$oid] += (float)$row['quantity'] * (float)$row['price'];
    }
    $bucket_profits = array();
    foreach($rows as $row){
        if(!isset($row['cost_price']) || $row['cost_price'] === null || $row['cost_price'] === '' || (float)$row['cost_price'] <= 0){
            continue;
        }
        $order_subtotal = isset($order_subtotals[$row['order_id']]) ? $order_subtotals[$row['order_id']] : 0;
        $line = (float)$row['quantity'] * (float)$row['price'];
        $discount = isset($row['discount_total']) ? (float)$row['discount_total'] : 0;
        if($discount > 0 && $order_subtotal > 0){
            $line -= ($line / $order_subtotal) * $discount;
        }
        $line = max(0, $line);
        $bucket = $row['bucket'];
        if(!isset($bucket_profits[$bucket])) $bucket_profits[$bucket] = 0;
        $bucket_profits[$bucket] += $line - ((float)$row['cost_price'] * (int)$row['quantity']);
    }
    return $bucket_profits;
}
function dashboard_chart_analytics($range = '7d'){
    global $conn;
    $empty = array(
        'labels' => array(),
        'sales' => array(),
        'profit' => array(),
        'orders' => array(),
        'range_label' => '',
    );
    if(!isset($conn) || !$conn) return $empty;
    $allowed = array('7d', '30d', 'week', 'month', 'year', 'today', 'lifetime');
    if(!in_array($range, $allowed, true)) $range = '7d';
    $bounds = dashboard_chart_range_bounds($range);
    $start = $bounds['start'];
    $end = $bounds['end'];
    $mode = $bounds['mode'];
    $buckets = dashboard_chart_init_buckets($start, $end, $mode);
    if(empty($buckets)) return $empty;
    $sales_group = $mode === 'month'
        ? "DATE_FORMAT(s.date_created, '%Y-%m')"
        : 'DATE(s.date_created)';
    $sales_qry = $conn->query("SELECT {$sales_group} AS bucket, SUM(s.total_amount) AS total
        FROM sales s
        WHERE DATE(s.date_created) BETWEEN '{$start}' AND '{$end}'
        GROUP BY {$sales_group}");
    if($sales_qry){
        while($row = $sales_qry->fetch_assoc()){
            if(isset($buckets[$row['bucket']])){
                $buckets[$row['bucket']]['sales'] = (float)$row['total'];
            }
        }
    }
    $orders_group = $mode === 'month'
        ? "DATE_FORMAT(date_created, '%Y-%m')"
        : 'DATE(date_created)';
    $orders_qry = $conn->query("SELECT {$orders_group} AS bucket, COUNT(*) AS total
        FROM orders
        WHERE DATE(date_created) BETWEEN '{$start}' AND '{$end}' AND status != 4
        GROUP BY {$orders_group}");
    if($orders_qry){
        while($row = $orders_qry->fetch_assoc()){
            if(isset($buckets[$row['bucket']])){
                $buckets[$row['bucket']]['orders'] = (int)$row['total'];
            }
        }
    }
    $profit_map = dashboard_profit_by_buckets($start, $end, $mode);
    foreach($profit_map as $bucket => $profit){
        if(isset($buckets[$bucket])){
            $buckets[$bucket]['profit'] = round((float)$profit, 2);
        }
    }
    $labels = array();
    $sales = array();
    $profit = array();
    $orders = array();
    foreach($buckets as $item){
        $labels[] = $item['label'];
        $sales[] = round($item['sales'], 2);
        $profit[] = round($item['profit'], 2);
        $orders[] = (int)$item['orders'];
    }
    return array(
        'labels' => $labels,
        'sales' => $sales,
        'profit' => $profit,
        'orders' => $orders,
        'range_label' => $bounds['title'],
    );
}
function dashboard_top_products($limit = 5){
    global $conn;
    $limit = max(1, (int)$limit);
    $items = array();
    if(!isset($conn) || !$conn) return $items;
    $sql = "SELECT p.name AS product_name, SUM(ol.quantity) AS qty_sold, SUM(ol.total) AS revenue
        FROM order_list ol
        INNER JOIN orders o ON o.id = ol.order_id
        INNER JOIN inventory i ON i.id = ol.inventory_id
        INNER JOIN products p ON p.id = i.product_id
        WHERE o.status != 4".tenant_sql('o')."
        GROUP BY p.id, p.name
        ORDER BY qty_sold DESC
        LIMIT {$limit}";
    $qry = $conn->query($sql);
    if($qry){
        while($row = $qry->fetch_assoc()){
            $items[] = array(
                'product_name' => trim(stripslashes($row['product_name'])),
                'qty_sold' => (int)$row['qty_sold'],
                'revenue' => (float)$row['revenue'],
            );
        }
    }
    return $items;
}
function dashboard_recent_sale_lines($limit = 10){
    global $conn;
    $limit = max(1, (int)$limit);
    $items = array();
    if(!isset($conn) || !$conn) return $items;
    $sql = "SELECT o.date_created, o.ref_code, o.delivery_address, o.payment_method,
        p.name AS product_name, ol.quantity, ol.price,
        CONCAT(c.firstname,' ',c.lastname) AS client_name
        FROM sales s
        INNER JOIN orders o ON o.id = s.order_id
        INNER JOIN order_list ol ON ol.order_id = o.id
        INNER JOIN inventory i ON i.id = ol.inventory_id
        INNER JOIN products p ON p.id = i.product_id
        INNER JOIN clients c ON c.id = o.client_id
        WHERE o.status != 4".tenant_sql('o')."
        ORDER BY o.date_created DESC, ol.id DESC
        LIMIT {$limit}";
    $qry = $conn->query($sql);
    if($qry){
        while($row = $qry->fetch_assoc()){
            $items[] = array(
                'date_created' => $row['date_created'],
                'ref_code' => $row['ref_code'],
                'product_name' => trim(stripslashes($row['product_name'])),
                'customer' => dashboard_order_customer_name($row['delivery_address'], $row['client_name']),
                'payment' => dashboard_payment_label($row['payment_method']),
                'total' => (float)$row['quantity'] * (float)$row['price'],
            );
        }
    }
    return $items;
}
function dashboard_profit_total($date_start, $date_end){
    if(!admin_can_view_profit()) return null;
    global $conn;
    if(!isset($conn) || !$conn) return 0;
    $cost_column = app_cost_price_column();
    if(!$cost_column) return null;
    $date_start = date('Y-m-d', strtotime($date_start));
    $date_end = date('Y-m-d', strtotime($date_end));
    if(db_table_has_column('order_list', 'cost_price')){
        $cost_select = "COALESCE(NULLIF(ol.cost_price, ''), i.`{$cost_column}`) AS cost_price";
    }else{
        $cost_select = "i.`{$cost_column}` AS cost_price";
    }
    $discount_select = db_table_has_column('orders', 'discount_total') ? 'o.discount_total' : '0 AS discount_total';
    $sql = "SELECT o.id AS order_id, ol.quantity, ol.price, {$discount_select}, {$cost_select}
        FROM sales s
        INNER JOIN orders o ON o.id = s.order_id
        INNER JOIN order_list ol ON ol.order_id = o.id
        INNER JOIN inventory i ON ol.inventory_id = i.id
        WHERE DATE(s.date_created) BETWEEN '{$date_start}' AND '{$date_end}'".tenant_sql('s');
    $qry = $conn->query($sql);
    if(!$qry) return 0;
    $rows = array();
    while($row = $qry->fetch_assoc()){
        $rows[] = $row;
    }
    $order_subtotals = array();
    foreach($rows as $row){
        $oid = $row['order_id'];
        if(!isset($order_subtotals[$oid])) $order_subtotals[$oid] = 0;
        $order_subtotals[$oid] += (float)$row['quantity'] * (float)$row['price'];
    }
    $total_profit = 0;
    foreach($rows as $row){
        if(!isset($row['cost_price']) || $row['cost_price'] === null || $row['cost_price'] === '' || (float)$row['cost_price'] <= 0){
            continue;
        }
        $order_subtotal = isset($order_subtotals[$row['order_id']]) ? $order_subtotals[$row['order_id']] : 0;
        $line = (float)$row['quantity'] * (float)$row['price'];
        $discount = isset($row['discount_total']) ? (float)$row['discount_total'] : 0;
        if($discount > 0 && $order_subtotal > 0){
            $line -= ($line / $order_subtotal) * $discount;
        }
        $line = max(0, $line);
        $total_profit += $line - ((float)$row['cost_price'] * (int)$row['quantity']);
    }
    return $total_profit;
}
function dashboard_format_profit($amount){
    if(!admin_can_view_profit()) return '';
    if($amount === null) return '&mdash;';
    return format_price($amount);
}
function dashboard_payment_sales_today($method){
    global $conn;
    if(!isset($conn) || !$conn) return 0;
    $today = date('Y-m-d');
    $method_esc = $conn->real_escape_string($method);
    $qry = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM orders
        WHERE DATE(date_created) = '{$today}' AND payment_method = '{$method_esc}' AND status != 4".tenant_sql());
    if($qry && ($row = $qry->fetch_assoc())){
        return (float)$row['total'];
    }
    return 0;
}
function admin_activity_log_enabled(){
    global $conn;
    static $enabled = null;
    if($enabled !== null) return $enabled;
    $enabled = false;
    if(isset($conn) && $conn){
        $q = $conn->query("SHOW TABLES LIKE 'admin_activity_log'");
        if($q && $q->num_rows > 0) $enabled = true;
    }
    return $enabled;
}
function admin_activity_log($action, $details = '', $user_id = null, $username = null){
    global $conn;
    if(!admin_activity_log_enabled() || !isset($conn) || !$conn) return false;
    $action = preg_replace('/[^a-z0-9_]/i', '', strtolower(trim($action)));
    if($action === '') return false;
    if($user_id === null && isset($_SESSION['userdata']['id'])) $user_id = (int)$_SESSION['userdata']['id'];
    if($username === null && isset($_SESSION['userdata']['username'])) $username = $_SESSION['userdata']['username'];
    $user_id = (int)$user_id;
    $username = $conn->real_escape_string(trim((string)$username));
    $details = $conn->real_escape_string(trim((string)$details));
    $now = date('Y-m-d H:i:s');
    $bid = tenant_id();
    if($bid <= 0) return false;
    return $conn->query("INSERT INTO admin_activity_log SET business_id = '{$bid}', user_id = '{$user_id}', username = '{$username}', action = '{$action}', details = '{$details}', date_created = '{$now}'");
}
function admin_activity_action_label($action){
    $map = array(
        'login' => 'Login',
        'logout' => 'Logout',
        'product_created' => 'Product Created',
        'product_updated' => 'Product Updated',
        'product_deleted' => 'Product Deleted',
        'inventory_created' => 'Inventory Created',
        'inventory_updated' => 'Inventory Updated',
        'inventory_deleted' => 'Inventory Deleted',
        'inventory_import_completed' => 'Inventory Excel Import Completed',
        'pos_sale_completed' => 'POS Sale Completed',
        'sale_updated' => 'Sale Updated',
        'debt_payment_received' => 'Debt Payment Received',
        'debt_payment_deleted' => 'Debt Payment Deleted',
        'order_updated' => 'Order Updated',
        'settings_updated' => 'Settings Updated',
        'permissions_updated' => 'Permissions Updated',
        'expense_created' => 'Expense Created',
        'expense_updated' => 'Expense Updated',
        'expense_deleted' => 'Expense Deleted',
        'backup_created' => 'Backup Created',
        'backup_restored' => 'Backup Restored',
        'backup_deleted' => 'Backup Deleted',
        'data_clean_sales' => 'Sales Data Cleaned',
        'data_clean_purchases' => 'Purchase Data Cleaned',
        'data_clean_expenses' => 'Expenses Cleaned',
        'data_clean_notifications' => 'Notifications Cleaned',
        'data_clean_full' => 'Full Business Reset',
        'staff_created' => 'Staff User Created',
        'staff_updated' => 'Staff User Updated',
        'staff_deleted' => 'Staff User Deleted',
        'staff_password_reset' => 'Staff Password Reset',
        'staff_status_changed' => 'Staff Status Changed',
        'user_permissions_updated' => 'User Permissions Updated',
    );
    return isset($map[$action]) ? $map[$action] : ucwords(str_replace('_', ' ', $action));
}
function dashboard_recent_activities($limit = 10){
    global $conn;
    $limit = max(1, (int)$limit);
    $items = array();
    if(!admin_activity_log_enabled() || !isset($conn) || !$conn) return $items;
    $qry = $conn->query("SELECT user_id, username, action, details, date_created
        FROM admin_activity_log
        WHERE 1=1".tenant_sql()."
        ORDER BY date_created DESC, id DESC
        LIMIT {$limit}");
    if($qry){
        while($row = $qry->fetch_assoc()){
            $items[] = array(
                'date_created' => $row['date_created'],
                'username' => trim($row['username']),
                'action' => $row['action'],
                'action_label' => admin_activity_action_label($row['action']),
                'details' => trim(stripslashes($row['details'])),
            );
        }
    }
    return $items;
}
function dashboard_sales_report_url($date_start, $date_end, $payment = ''){
    $url = base_url.'admin/?page=sales&date_start='.urlencode($date_start).'&date_end='.urlencode($date_end);
    if($payment !== '') $url .= '&payment_method='.urlencode($payment);
    return $url;
}

/*
function dashboard_inventory_value(){
    global $conn;
    if(!isset($conn) || !$conn) return 0;
    $avail_sql = inventory_available_stock_sql('i');
    $sold_sub = inventory_sold_subquery_sql();
    $sql = "SELECT COALESCE(SUM(GREATEST({$avail_sql}, 0) * i.price), 0) AS total
        FROM inventory i
        INNER JOIN products p ON p.id = i.product_id
        LEFT JOIN {$sold_sub} sold ON sold.inventory_id = i.id
        WHERE p.delete_flag = 0 AND p.status = 1".tenant_sql('i');
    $qry = $conn->query($sql);
    if($qry && ($row = $qry->fetch_assoc())){
        return (float)$row['total'];
    }
    return 0;
}
    */

//Stock Value baan badalayaa

function dashboard_inventory_value(){

    global $conn;

    if(!isset($conn) || !$conn) return 0;

    $cost_col = app_cost_price_column();

    if(!$cost_col) return 0;

    $avail_sql = inventory_available_stock_sql('i');

    $sold_sub = inventory_sold_subquery_sql();

    $sql = "SELECT COALESCE(
                SUM(GREATEST({$avail_sql}, 0) * COALESCE(i.`{$cost_col}`, 0)),
                0
            ) AS total

        FROM inventory i

        INNER JOIN products p ON p.id = i.product_id

        LEFT JOIN {$sold_sub} sold ON sold.inventory_id = i.id

        WHERE p.delete_flag = 0
        AND p.status = 1".tenant_sql('i');

    $qry = $conn->query($sql);

    if($qry && ($row = $qry->fetch_assoc())){

        return (float)$row['total'];

    }

    return 0;

}




function dashboard_users_count(){
    global $conn;
    if(!isset($conn) || !$conn) return 0;
    $qry = $conn->query("SELECT COUNT(*) AS total FROM users WHERE 1=1".tenant_sql());
    if($qry && ($row = $qry->fetch_assoc())){
        return (int)$row['total'];
    }
    return 0;
}
function dashboard_orders_today_count(){
    global $conn;
    if(!isset($conn) || !$conn) return 0;
    $today = date('Y-m-d');
    $qry = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE DATE(date_created) = '{$today}' AND status != 4".tenant_sql());
    if($qry && ($row = $qry->fetch_assoc())){
        return (int)$row['total'];
    }
    return 0;
}
function dashboard_greeting_text(){
    $hour = (int)date('G');
    if($hour < 12) return 'Good Morning';
    if($hour < 17) return 'Good Afternoon';
    return 'Good Evening';
}
function dashboard_user_display_name(){
    $parts = array();
    if(isset($_SESSION['userdata']['firstname'])) $parts[] = trim($_SESSION['userdata']['firstname']);
    if(isset($_SESSION['userdata']['lastname'])) $parts[] = trim($_SESSION['userdata']['lastname']);
    $name = trim(implode(' ', $parts));
    if($name === '' && isset($_SESSION['userdata']['username'])){
        $name = trim($_SESSION['userdata']['username']);
    }
    if($name === '') return 'Administrator';
    if(!admin_is_cashier()) return $name;
    return $name;
}
function admin_permission_catalog(){
    return array(
        'dashboard_full' => array('label' => 'Dashboard (full access)', 'admin_only' => true, 'cashier_default' => 0),
        'dashboard_limited' => array('label' => 'Dashboard (limited access)', 'admin_only' => false, 'cashier_default' => 1),
        'pos' => array('label' => 'Point of Sale', 'admin_only' => false, 'cashier_default' => 1),
        'products' => array('label' => 'Products (create, edit, delete)', 'admin_only' => false, 'cashier_default' => 0),
        'inventory_view' => array('label' => 'Stock & Inventory (view only)', 'admin_only' => false, 'cashier_default' => 1),
        'inventory_manage' => array('label' => 'Stock & Inventory (create, edit, delete)', 'admin_only' => false, 'cashier_default' => 0),
        'orders_view' => array('label' => 'Orders (view only)', 'admin_only' => false, 'cashier_default' => 1),
        'orders_manage' => array('label' => 'Orders (update, payment, delete)', 'admin_only' => false, 'cashier_default' => 0),
        'clients' => array('label' => 'Customers', 'admin_only' => false, 'cashier_default' => 0),
        'debt_view' => array('label' => 'View Debts', 'admin_only' => false, 'cashier_default' => 0),
        'debt_sale' => array('label' => 'Create Debt Sale', 'admin_only' => false, 'cashier_default' => 0),
        'debt_payment' => array('label' => 'Receive Debt Payment', 'admin_only' => false, 'cashier_default' => 0),
        'debt_payment_delete' => array('label' => 'Delete Debt Payment', 'admin_only' => true, 'cashier_default' => 0),
        'debt_reports' => array('label' => 'Debt Reports', 'admin_only' => false, 'cashier_default' => 0),
        'sales_report' => array('label' => 'Sales Reports', 'admin_only' => false, 'cashier_default' => 0),
        'expenses' => array('label' => 'Operating Expenses', 'admin_only' => false, 'cashier_default' => 0),
        'profit_analytics' => array('label' => 'Profit & Loss Analytics', 'admin_only' => true, 'cashier_default' => 0),
        'backup_restore' => array('label' => 'Backup & Restore', 'admin_only' => true, 'cashier_default' => 0),
        'clean_data' => array('label' => 'Clean Data (Backup page)', 'admin_only' => true, 'cashier_default' => 0),
        'users_manage' => array('label' => 'Users / Staff Management', 'admin_only' => true, 'cashier_default' => 0),
        'brands' => array('label' => 'Brands', 'admin_only' => false, 'cashier_default' => 0),
        'categories' => array('label' => 'Categories', 'admin_only' => false, 'cashier_default' => 0),
        'settings' => array('label' => 'Store Settings', 'admin_only' => false, 'cashier_default' => 0),
        'permissions' => array('label' => 'Role Permissions', 'admin_only' => true, 'cashier_default' => 0),
        'my_account' => array('label' => 'My Account', 'admin_only' => false, 'cashier_default' => 1),
        'delete_actions' => array('label' => 'Delete records', 'admin_only' => false, 'cashier_default' => 0),
    );
}
function admin_default_cashier_permissions(){
    $perms = array();
    foreach(admin_permission_catalog() as $key => $meta){
        if(!empty($meta['admin_only'])){
            $perms[$key] = 0;
        }else{
            $perms[$key] = !empty($meta['cashier_default']) ? 1 : 0;
        }
    }
    return $perms;
}
function admin_load_cashier_permissions(){
    global $conn;
    static $cache = null;
    if($cache !== null)
        return $cache;
    $cache = admin_default_cashier_permissions();
    if(isset($conn) && $conn && tenant_id() > 0){
        $raw = tenant_setting_get('cashier_permissions', '');
        if($raw !== ''){
            $decoded = json_decode($raw, true);
            if(is_array($decoded)){
                foreach($cache as $key => $val){
                    if(array_key_exists($key, $decoded))
                        $cache[$key] = !empty($decoded[$key]) ? 1 : 0;
                }
            }
        }
    }
    foreach(admin_permission_catalog() as $key => $meta){
        if(!empty($meta['admin_only']))
            $cache[$key] = 0;
    }
    return $cache;
}
function admin_decode_user_permissions($json){
    $defaults = admin_default_cashier_permissions();
    if($json === null || $json === '') return null;
    $decoded = is_array($json) ? $json : json_decode($json, true);
    if(!is_array($decoded)) return null;
    $out = $defaults;
    foreach($defaults as $key => $val){
        if(array_key_exists($key, $decoded))
            $out[$key] = !empty($decoded[$key]) ? 1 : 0;
    }
    foreach(admin_permission_catalog() as $key => $meta){
        if(!empty($meta['admin_only']))
            $out[$key] = 0;
    }
    return $out;
}
function admin_load_user_permissions($user_id){
    global $conn;
    $user_id = (int)$user_id;
    if($user_id <= 0 || !isset($conn) || !$conn || !db_table_has_column('users', 'permissions'))
        return null;
    $q = $conn->query("SELECT permissions, type FROM users WHERE id = '{$user_id}' LIMIT 1");
    if(!$q || !$q->num_rows) return null;
    $row = $q->fetch_assoc();
    if((int)$row['type'] === 1) return null;
    return admin_decode_user_permissions($row['permissions']);
}
function admin_save_user_permissions($user_id, $data){
    global $conn;
    $user_id = (int)$user_id;
    if($user_id <= 0 || !isset($conn) || !$conn || !db_table_has_column('users', 'permissions'))
        return false;
    $row = $conn->query("SELECT type FROM users WHERE id = '{$user_id}' LIMIT 1");
    if(!$row || !$row->num_rows || (int)$row->fetch_assoc()['type'] === 1)
        return false;
    $save = admin_default_cashier_permissions();
    if(is_string($data)) $data = json_decode($data, true);
    if(!is_array($data)) $data = array();
    foreach($save as $key => $val){
        if(!empty(admin_permission_catalog()[$key]['admin_only'])) continue;
        $save[$key] = !empty($data[$key]) ? 1 : 0;
    }
    $json = $conn->real_escape_string(json_encode($save));
    return $conn->query("UPDATE users SET permissions = '{$json}' WHERE id = '{$user_id}'");
}
function admin_session_user_permissions(){
    if(!admin_is_cashier() || !isset($_SESSION['userdata']['id']))
        return null;
    if(isset($_SESSION['userdata']['permissions']) && is_array($_SESSION['userdata']['permissions']))
        return $_SESSION['userdata']['permissions'];
    $perms = admin_load_user_permissions((int)$_SESSION['userdata']['id']);
    if($perms !== null)
        $_SESSION['userdata']['permissions'] = $perms;
    return $perms;
}
function admin_save_cashier_permissions($data){
    global $conn;
    if(!isset($conn) || !$conn)
        return false;
    $save = admin_default_cashier_permissions();
    if(is_string($data))
        $data = json_decode($data, true);
    if(!is_array($data))
        $data = array();
    foreach($save as $key => $val){
        if(!empty(admin_permission_catalog()[$key]['admin_only']))
            continue;
        $save[$key] = !empty($data[$key]) ? 1 : 0;
    }
    $save['permissions'] = 0;
    $save['dashboard_full'] = 0;
    return tenant_setting_set('cashier_permissions', json_encode($save));
}
function admin_cashier_has_permission($key){
    if(!admin_is_cashier())
        return true;
    $user_perms = admin_session_user_permissions();
    if(is_array($user_perms) && array_key_exists($key, $user_perms))
        return !empty($user_perms[$key]);
    $perms = admin_load_cashier_permissions();
    return !empty($perms[$key]);
}
function admin_cashier_can($key){
    if(!admin_is_cashier())
        return true;
    return admin_cashier_has_permission($key);
}
function admin_cashier_page_permission($page){
    $map = array(
        'home' => 'dashboard_limited',
        '' => 'dashboard_limited',
        'pos' => 'pos',
        'product' => 'products',
        'product/manage_product' => 'products',
        'inventory' => 'inventory_view',
        'inventory/manage_inventory' => 'inventory_manage',
        'orders' => 'orders_view',
        'orders/view_order' => 'orders_view',
        'clients' => 'clients',
        'clients/manage_client' => 'clients',
        'clients/view_client' => 'clients',
        'debt' => 'debt_view',
        'debt/customers' => 'debt_view',
        'debt/receive_payment' => 'debt_payment',
        'debt/history' => 'debt_view',
        'debt/overdue' => 'debt_view',
        'debt/report' => 'debt_reports',
        'debt/statement' => 'debt_view',
        'sales' => 'sales_report',
        'sales/edit_sale' => 'orders_manage',
        'expenses' => 'expenses',
        'expenses/manage_expense' => 'expenses',
        'analytics' => 'profit_analytics',
        'backup' => 'backup_restore',
        'users' => 'users_manage',
        'users/manage_user' => 'users_manage',
        'subscription_expired' => 'my_account',
        'subscription' => 'my_account',
        'notifications' => 'dashboard_limited',
        'maintenance/brand' => 'brands',
        'maintenance/manage_brand' => 'brands',
        'maintenance/view_brand' => 'brands',
        'maintenance/category' => 'categories',
        'maintenance/manage_category' => 'categories',
        'maintenance/view_category' => 'categories',
        'maintenance/permissions' => 'permissions',
        'system_info' => 'settings',
        'user' => 'my_account',
    );
    if(!isset($map[$page]))
        return null;
    return $map[$page];
}
function admin_cashier_allowed_page($page){
    if(!admin_is_cashier())
        return true;
    if($page === 'subscription_expired')
        return true;
    $perm = admin_cashier_page_permission($page);
    if($perm === null)
        return false;
    if($perm === 'permissions')
        return false;
    if($perm === 'inventory_view')
        return admin_cashier_has_permission('inventory_view') || admin_cashier_has_permission('inventory_manage');
    if($perm === 'orders_view')
        return admin_cashier_has_permission('orders_view') || admin_cashier_has_permission('orders_manage');
    return admin_cashier_has_permission($perm);
}
function admin_deny_cashier_access($page){
    return admin_is_cashier() && !admin_cashier_allowed_page($page);
}
function admin_cashier_landing_candidates(){
    return array(
        array('pos', 'admin/?page=pos'),
        array('dashboard_limited', 'admin/'),
        array('my_account', 'admin/?page=user'),
        array('inventory_view', 'admin/?page=inventory'),
        array('inventory_manage', 'admin/?page=inventory'),
        array('orders_view', 'admin/?page=orders'),
        array('orders_manage', 'admin/?page=orders'),
        array('products', 'admin/?page=product'),
        array('clients', 'admin/?page=clients'),
        array('debt_view', 'admin/?page=debt'),
        array('debt_payment', 'admin/?page=debt/receive_payment'),
        array('debt_reports', 'admin/?page=debt/report'),
        array('sales_report', 'admin/?page=sales'),
        array('expenses', 'admin/?page=expenses'),
        array('brands', 'admin/?page=maintenance/brand'),
        array('categories', 'admin/?page=maintenance/category'),
        array('settings', 'admin/?page=system_info'),
    );
}
function admin_cashier_first_allowed_path(){
    if(!admin_is_cashier())
        return null;
    foreach(admin_cashier_landing_candidates() as $item){
        if(admin_cashier_has_permission($item[0]))
            return $item[1];
    }
    return null;
}
function admin_login_landing_path(){
    if(!isset($_SESSION['userdata']['login_type']) || (int)$_SESSION['userdata']['login_type'] !== 1)
        return null;

    $role = admin_user_type();
    $landings = admin_role_landing_pages();

    if(isset($landings[$role])){
        $path = $landings[$role];
        if(admin_is_cashier()){
            if(admin_cashier_has_permission('pos'))
                return 'admin/?page=pos';
            $fallback = admin_cashier_first_allowed_path();
            return $fallback !== null ? $fallback : 'admin/';
        }
        return $path;
    }

    return 'admin/';
}
function admin_cashier_has_any_access(){
    return admin_cashier_first_allowed_path() !== null;
}
function admin_cashier_denied_redirect_url(){
    $path = admin_cashier_first_allowed_path();
    if($path !== null)
        return base_url.$path;
    return base_url.'admin/login.php';
}
function admin_cashier_resolve_page_access($page){
    if(!admin_is_cashier())
        return array('status' => 'allow');
    if(admin_cashier_allowed_page($page))
        return array('status' => 'allow');
    $landing = admin_cashier_first_allowed_path();
    if($landing !== null)
        return array('status' => 'redirect', 'url' => base_url.$landing);
    return array('status' => 'deny', 'url' => base_url.'admin/login.php');
}
function admin_cashier_api_denied($action){
    if(!admin_is_cashier())
        return false;
    $rules = array(
        'save_brand' => 'brands',
        'delete_brand' => array('perm' => 'brands', 'delete' => true),
        'save_category' => 'categories',
        'delete_category' => array('perm' => 'categories', 'delete' => true),
        'save_sub_category' => 'categories',
        'delete_sub_category' => array('perm' => 'categories', 'delete' => true),
        'save_product' => 'products',
        'delete_product' => array('perm' => 'products', 'delete' => true),
        'save_inventory' => 'inventory_manage',
        'delete_inventory' => array('perm' => 'inventory_manage', 'delete' => true),
        'inventory_import_preview' => 'inventory_manage',
        'inventory_import_commit' => 'inventory_manage',
        'delete_img' => array('any' => array('settings', 'products', 'brands', 'categories'), 'delete' => true),
        'pay_order' => 'orders_manage',
        'update_order_status' => 'orders_manage',
        'delete_order' => array('perm' => 'orders_manage', 'delete' => true),
        'update_client' => 'clients',
        'delete_client' => array('perm' => 'clients', 'delete' => true),
        'save_cashier_permissions' => 'permissions',
        'save_expense' => 'expenses',
        'delete_expense' => array('perm' => 'expenses', 'delete' => true),
        'get_notifications' => null,
        'mark_notification_read' => null,
        'mark_all_notifications_read' => null,
        'delete_notification' => null,
        'create_backup' => 'backup_restore',
        'delete_backup' => array('perm' => 'backup_restore', 'delete' => true),
        'restore_backup' => 'backup_restore',
        'download_backup' => 'backup_restore',
        'clean_business_data' => array('perm' => 'clean_data', 'delete' => true),
        'save_staff_user' => 'users_manage',
        'delete_staff_user' => array('perm' => 'users_manage', 'delete' => true),
        'reset_staff_password' => 'users_manage',
        'toggle_staff_status' => 'users_manage',
        'save_user_permissions' => 'permissions',
        'profit_analytics_data' => 'profit_analytics',
        'pos_search_customers' => 'pos',
        'debt_receive_payment' => 'debt_payment',
        'debt_delete_payment' => array('perm' => 'debt_payment_delete', 'delete' => true),
        'debt_client_summary' => 'debt_view',
        'sales_get_order_edit' => 'orders_manage',
        'sales_update_order' => 'orders_manage',
        'sales_search_product' => 'orders_manage',
    );
    if(in_array($action, array('get_notifications', 'mark_notification_read', 'mark_all_notifications_read', 'delete_notification'), true))
        return false;
    if($action === 'save_cashier_permissions' || $action === 'save_user_permissions')
        return admin_is_cashier() || !admin_is_owner();
    if(!isset($rules[$action]))
        return false;
    $rule = $rules[$action];
    if(is_string($rule))
        return !admin_cashier_has_permission($rule);
    if(!empty($rule['any'])){
        $allowed = false;
        foreach($rule['any'] as $perm){
            if(admin_cashier_has_permission($perm)){
                $allowed = true;
                break;
            }
        }
        if(!$allowed)
            return true;
    }elseif(!empty($rule['perm']) && !admin_cashier_has_permission($rule['perm'])){
        return true;
    }
    if(!empty($rule['delete']) && !admin_cashier_has_permission('delete_actions'))
        return true;
    return false;
}
function expenses_table_enabled(){
    global $conn;
    static $enabled = null;
    if($enabled !== null) return $enabled;
    $enabled = false;
    if(isset($conn) && $conn){
        $q = $conn->query("SHOW TABLES LIKE 'expenses'");
        if($q && $q->num_rows > 0) $enabled = true;
    }
    return $enabled;
}
function expense_categories(){
    return array(
        'Rent', 'Salaries', 'Electricity', 'Water', 'Transport',
        'Internet', 'Marketing', 'Packaging', 'Maintenance', 'Miscellaneous'
    );
}
function expense_payment_methods(){
    return array('Cash', 'M-Pesa', 'Bank Transfer', 'Other');
}
function expenses_normalize_date($date, $fallback = null){
    if($fallback === null) $fallback = date('Y-m-d');
    $date = trim((string)$date);
    if($date === '') return $fallback;
    if(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)){
        if(checkdate((int)$m[2], (int)$m[3], (int)$m[1])) return $date;
    }
    $ts = strtotime($date);
    if($ts !== false) return date('Y-m-d', $ts);
    return $fallback;
}
function business_settings_date($field){
    global $_settings;
    if(!isset($_settings)) return '';
    $v = trim((string)$_settings->info($field));
    if($v === '') return '';
    $normalized = expenses_normalize_date($v, '');
    return $normalized !== '' ? $normalized : '';
}
function business_effective_end_date(){
    $today = date('Y-m-d');
    $closed = business_settings_date('business_closed_date');
    if($closed !== '' && $closed <= $today) return $closed;
    return $today;
}
function business_is_closed(){
    $closed = business_settings_date('business_closed_date');
    return $closed !== '' && $closed <= date('Y-m-d');
}
function business_first_transaction_date(){
    global $conn;
    if(!isset($conn) || !$conn) return date('Y-m-d');
    $dates = array();
    $q = $conn->query("SELECT MIN(DATE(date_created)) AS d FROM sales WHERE 1=1".tenant_sql());
    if($q && ($r = $q->fetch_assoc()) && !empty($r['d'])) $dates[] = $r['d'];
    if(function_exists('expenses_table_enabled') && expenses_table_enabled()){
        $q2 = $conn->query("SELECT MIN(DATE(expense_date)) AS d FROM expenses WHERE delete_flag = 0".tenant_sql());
        if($q2 && ($r2 = $q2->fetch_assoc()) && !empty($r2['d'])) $dates[] = $r2['d'];
    }
    if(empty($dates)) return date('Y-m-d');
    sort($dates);
    return $dates[0];
}
function business_start_date(){
    $configured = business_settings_date('business_start_date');
    if($configured !== '') return $configured;
    return business_first_transaction_date();
}
function business_operating_bounds(){
    return array(
        'start' => business_start_date(),
        'end' => business_effective_end_date(),
        'is_closed' => business_is_closed(),
        'closed_date' => business_settings_date('business_closed_date'),
    );
}
function report_date_presets(){
    return array(
        'today' => 'Today',
        'week' => 'This Week',
        'month' => 'This Month',
        'year' => 'This Year',
        'custom' => 'Custom Range',
        'lifetime' => 'All Time / Since Opening',
    );
}
function report_clamp_date($date, $max = null){
    $bounds = business_operating_bounds();
    $date = expenses_normalize_date($date, $bounds['start']);
    if($date < $bounds['start']) $date = $bounds['start'];
    $max = ($max !== null && $max !== '') ? $max : $bounds['end'];
    $max = expenses_normalize_date($max, $bounds['end']);
    if($date > $max) $date = $max;
    return $date;
}
function report_resolve_range($preset = '', $date_start = '', $date_end = ''){
    $preset = strtolower(trim((string)$preset));
    $bounds = business_operating_bounds();
    $operating_end = $bounds['end'];
    $operating_start = $bounds['start'];
    $today = date('Y-m-d');
    $effective_today = ($operating_end < $today) ? $operating_end : $today;
    if($preset === 'lifetime' || $preset === 'all_time' || $preset === 'alltime'){
        return array(
            'start' => $operating_start,
            'end' => $operating_end,
            'preset' => 'lifetime',
            'label' => 'All Time (since '.date('M j, Y', strtotime($operating_start)).')',
        );
    }
    if($preset === 'today'){
        $d = report_clamp_date($effective_today);
        return array('start' => $d, 'end' => $d, 'preset' => 'today', 'label' => 'Today');
    }
    if($preset === 'week'){
        $start = date('Y-m-d', strtotime('monday this week', strtotime($effective_today)));
        $start = report_clamp_date($start);
        return array('start' => $start, 'end' => report_clamp_date($effective_today), 'preset' => 'week', 'label' => 'This Week');
    }
    if($preset === 'month'){
        $start = report_clamp_date(date('Y-m-01', strtotime($effective_today)));
        return array('start' => $start, 'end' => report_clamp_date($effective_today), 'preset' => 'month', 'label' => 'This Month');
    }
    if($preset === 'year'){
        $start = report_clamp_date(date('Y-01-01', strtotime($effective_today)));
        return array('start' => $start, 'end' => report_clamp_date($effective_today), 'preset' => 'year', 'label' => 'This Year');
    }
    $default_start = date('Y-m-d', strtotime($effective_today.' -7 days'));
    $start = expenses_normalize_date($date_start, $default_start);
    $end = expenses_normalize_date($date_end, $effective_today);
    if($start > $end){
        $tmp = $start;
        $start = $end;
        $end = $tmp;
    }
    $start = report_clamp_date($start);
    $end = report_clamp_date($end);
    if($start > $end) $start = $end;
    return array(
        'start' => $start,
        'end' => $end,
        'preset' => ($preset === '' ? 'custom' : $preset),
        'label' => date('M j, Y', strtotime($start)).' – '.date('M j, Y', strtotime($end)),
    );
}
function report_range_day_count($date_start, $date_end){
    $start = strtotime($date_start);
    $end = strtotime($date_end);
    if($start === false || $end === false) return 0;
    return (int)floor(($end - $start) / 86400) + 1;
}
function business_orders_count($date_start, $date_end){
    global $conn;
    if(!isset($conn) || !$conn) return 0;
    $date_start = date('Y-m-d', strtotime($date_start));
    $date_end = date('Y-m-d', strtotime($date_end));
    $q = $conn->query("SELECT COUNT(DISTINCT s.order_id) AS total FROM sales s
        WHERE DATE(s.date_created) BETWEEN '{$date_start}' AND '{$date_end}'".tenant_sql('s'));
    if($q && ($row = $q->fetch_assoc())) return (int)$row['total'];
    return 0;
}
function business_lifetime_summary(){
    $bounds = business_operating_bounds();
    $start = $bounds['start'];
    $end = $bounds['end'];
    $revenue = profit_analytics_sales_total($start, $end);
    $cogs = profit_analytics_cost_total($start, $end);
    $gross_profit = admin_can_view_profit() ? dashboard_profit_total($start, $end) : null;
    $operating_expenses = expenses_total($start, $end);
    $net_profit = admin_can_view_profit() ? dashboard_net_profit($start, $end) : null;
    global $conn;
    $total_customers = 0;
    if(isset($conn) && $conn){
        $cq = $conn->query("SELECT COUNT(*) AS total FROM clients WHERE delete_flag = 0".tenant_sql());
        if($cq && ($cr = $cq->fetch_assoc())) $total_customers = (int)$cr['total'];
    }
    return array(
        'start' => $start,
        'end' => $end,
        'is_closed' => $bounds['is_closed'],
        'closed_date' => $bounds['closed_date'],
        'total_revenue' => $revenue,
        'total_cogs' => $cogs,
        'gross_profit' => $gross_profit,
        'operating_expenses' => $operating_expenses,
        'net_profit' => $net_profit,
        'total_orders' => business_orders_count($start, $end),
        'total_customers' => $total_customers,
        'stock_value' => dashboard_inventory_value(),
    );
}
function business_year_comparison_rows(){
    $bounds = business_operating_bounds();
    $start_year = (int)date('Y', strtotime($bounds['start']));
    $end_year = (int)date('Y', strtotime($bounds['end']));
    $rows = array();
    for($year = $start_year; $year <= $end_year; $year++){
        $ys = max($bounds['start'], $year.'-01-01');
        $ye = min($bounds['end'], $year.'-12-31');
        if($ys > $ye) continue;
        $gross = admin_can_view_profit() ? dashboard_profit_total($ys, $ye) : null;
        $rows[] = array(
            'year' => $year,
            'revenue' => profit_analytics_sales_total($ys, $ye),
            'cogs' => profit_analytics_cost_total($ys, $ye),
            'gross_profit' => $gross,
            'operating_expenses' => expenses_total($ys, $ye),
            'net_profit' => admin_can_view_profit() ? dashboard_net_profit($ys, $ye) : null,
            'orders' => business_orders_count($ys, $ye),
        );
    }
    return $rows;
}
function profit_analytics_report_rows($date_start, $date_end){
    $date_start = date('Y-m-d', strtotime($date_start));
    $date_end = date('Y-m-d', strtotime($date_end));
    if(report_range_day_count($date_start, $date_end) > 93){
        $series = profit_analytics_chart_series($date_start, $date_end, 'month');
        $rows = array();
        $keys = array();
        $cur = strtotime(date('Y-m-01', strtotime($date_start)));
        $end_ts = strtotime(date('Y-m-01', strtotime($date_end)));
        while($cur <= $end_ts){
            $keys[] = date('Y-m', $cur);
            $cur = strtotime('+1 month', $cur);
        }
        foreach($keys as $i => $key){
            $rows[] = array(
                'date' => isset($series['labels'][$i]) ? $series['labels'][$i] : $key,
                'sales' => isset($series['sales'][$i]) ? (float)$series['sales'][$i] : 0,
                'cost' => null,
                'expenses' => isset($series['expenses'][$i]) ? (float)$series['expenses'][$i] : 0,
                'profit' => isset($series['profit'][$i]) ? (float)$series['profit'][$i] : null,
                'net_profit' => isset($series['net'][$i]) ? (float)$series['net'][$i] : null,
            );
        }
        return array('mode' => 'month', 'rows' => $rows);
    }
    return array('mode' => 'day', 'rows' => profit_analytics_daily_rows($date_start, $date_end));
}
function expenses_normalize_range($date_start, $date_end){
    $start = expenses_normalize_date($date_start, date('Y-m-01'));
    $end = expenses_normalize_date($date_end, date('Y-m-d'));
    if($start > $end){
        $tmp = $start;
        $start = $end;
        $end = $tmp;
    }
    $start = report_clamp_date($start);
    $end = report_clamp_date($end);
    return array('start' => $start, 'end' => $end);
}
function expenses_where_sql($date_start, $date_end, $category = ''){
    global $conn;
    $range = expenses_normalize_range($date_start, $date_end);
    $where = "delete_flag = 0 AND DATE(expense_date) BETWEEN '{$range['start']}' AND '{$range['end']}'".tenant_sql();
    $category = trim((string)$category);
    if($category !== ''){
        $cat = $conn->real_escape_string($category);
        $where .= " AND TRIM(category) = '{$cat}'";
    }
    return $where;
}
function expenses_total($date_start, $date_end, $category = ''){
    global $conn;
    if(!expenses_table_enabled() || !isset($conn) || !$conn) return 0;
    $where = expenses_where_sql($date_start, $date_end, $category);
    $qry = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM expenses WHERE {$where}");
    if($qry && ($row = $qry->fetch_assoc())) return (float)$row['total'];
    return 0;
}
function dashboard_expenses_today(){
    return expenses_total(date('Y-m-d'), date('Y-m-d'));
}
function dashboard_expenses_month(){
    return expenses_total(date('Y-m-01'), date('Y-m-d'));
}
function dashboard_net_profit($date_start, $date_end){
    if(!admin_can_view_profit()) return null;
    $profit = dashboard_profit_total($date_start, $date_end);
    if($profit === null) return null;
    return (float)$profit - expenses_total($date_start, $date_end);
}
function dashboard_format_net_profit($amount){
    if(!admin_can_view_profit()) return '';
    if($amount === null) return '&mdash;';
    return format_price($amount);
}
function dashboard_is_net_loss($amount){
    return $amount !== null && is_numeric($amount) && (float)$amount < 0;
}
function dashboard_net_profit_label($amount, $period = ''){
    $base = dashboard_is_net_loss($amount) ? 'Net Loss' : 'Net Profit';
    if($period !== ''){
        return $base.' ('.$period.')';
    }
    return $base;
}
function dashboard_pl_formula_tooltip(){
    return 'Net Profit = Gross Profit - Operating Expenses';
}
function ash_format_money_cell($amount, $variant = 'neutral'){
    if($amount === null || $amount === '') return '&mdash;';
    if(!is_numeric($amount)) return htmlspecialchars((string)$amount);
    $num = (float)$amount;
    if($variant === 'auto' || $variant === 'profit'){
        if($num < 0) $variant = 'loss';
        elseif($num > 0) $variant = 'profit';
        else $variant = 'zero';
    }
    $allowed = array('neutral', 'revenue', 'expense', 'profit', 'loss', 'zero');
    if(!in_array($variant, $allowed, true)) $variant = 'neutral';
    return '<span class="ash-money ash-money-'.htmlspecialchars($variant).'">'.format_price($amount).'</span>';
}
function dashboard_format_net_profit_display($amount){
    if(!admin_can_view_profit()) return '';
    if($amount === null) return '&mdash;';
    $loss = dashboard_is_net_loss($amount);
    $variant = $loss ? 'loss' : 'profit';
    if((float)$amount === 0.0) $variant = 'zero';
    $class = $loss ? 'pl-amount pl-amount-loss' : 'pl-amount pl-amount-profit';
    return '<span class="'.$class.' ash-money ash-money-'.$variant.'" title="'.htmlspecialchars(dashboard_pl_formula_tooltip(), ENT_QUOTES).'">'.format_price($amount).'</span>';
}
function profit_analytics_period_bounds($period){
    $resolved = report_resolve_range($period ?: 'month', '', '');
    return array(
        'start' => $resolved['start'],
        'end' => $resolved['end'],
        'label' => $resolved['label'],
    );
}
function profit_analytics_sales_total($date_start, $date_end){
    global $conn;
    if(!isset($conn) || !$conn) return 0;
    $date_start = date('Y-m-d', strtotime($date_start));
    $date_end = date('Y-m-d', strtotime($date_end));
    $qry = $conn->query("SELECT COALESCE(SUM(s.total_amount), 0) AS total FROM sales s
        WHERE DATE(s.date_created) BETWEEN '{$date_start}' AND '{$date_end}'".tenant_sql('s'));
    if($qry && ($row = $qry->fetch_assoc())) return (float)$row['total'];
    return 0;
}
function profit_analytics_cost_total($date_start, $date_end){
    if(!admin_can_view_profit()) return null;
    global $conn;
    if(!isset($conn) || !$conn) return 0;
    $cost_column = app_cost_price_column();
    if(!$cost_column) return null;
    $date_start = date('Y-m-d', strtotime($date_start));
    $date_end = date('Y-m-d', strtotime($date_end));
    if(db_table_has_column('order_list', 'cost_price')){
        $cost_select = "COALESCE(NULLIF(ol.cost_price, ''), i.`{$cost_column}`) AS cost_price";
    }else{
        $cost_select = "i.`{$cost_column}` AS cost_price";
    }
    $sql = "SELECT ol.quantity, {$cost_select}
        FROM sales s
        INNER JOIN orders o ON o.id = s.order_id
        INNER JOIN order_list ol ON ol.order_id = o.id
        INNER JOIN inventory i ON ol.inventory_id = i.id
        WHERE DATE(s.date_created) BETWEEN '{$date_start}' AND '{$date_end}'";
    $qry = $conn->query($sql);
    if(!$qry) return 0;
    $total = 0;
    while($row = $qry->fetch_assoc()){
        if(isset($row['cost_price']) && $row['cost_price'] !== null && $row['cost_price'] !== '' && (float)$row['cost_price'] > 0){
            $total += (float)$row['cost_price'] * (int)$row['quantity'];
        }
    }
    return $total;
}
function profit_analytics_daily_rows($date_start, $date_end){
    global $conn;
    $rows = array();
    if(!isset($conn) || !$conn) return $rows;
    $date_start = date('Y-m-d', strtotime($date_start));
    $date_end = date('Y-m-d', strtotime($date_end));
    $cur = strtotime($date_start);
    $end_ts = strtotime($date_end);
    while($cur <= $end_ts){
        $d = date('Y-m-d', $cur);
        $sales = profit_analytics_sales_total($d, $d);
        $cost = profit_analytics_cost_total($d, $d);
        $expenses = expenses_total($d, $d);
        $profit = admin_can_view_profit() ? dashboard_profit_total($d, $d) : null;
        $net = ($profit !== null) ? ((float)$profit - $expenses) : null;
        $rows[] = array(
            'date' => $d,
            'sales' => $sales,
            'cost' => $cost,
            'expenses' => $expenses,
            'profit' => $profit,
            'net_profit' => $net,
        );
        $cur = strtotime('+1 day', $cur);
    }
    return $rows;
}
function profit_analytics_chart_series($date_start, $date_end, $mode = 'day'){
    $daily = profit_analytics_daily_rows($date_start, $date_end);
    if($mode === 'month'){
        $buckets = array();
        foreach($daily as $row){
            $key = date('Y-m', strtotime($row['date']));
            if(!isset($buckets[$key])){
                $buckets[$key] = array('label' => date('M Y', strtotime($row['date'].'-01')), 'sales' => 0, 'profit' => 0, 'expenses' => 0, 'net' => 0);
            }
            $buckets[$key]['sales'] += $row['sales'];
            $buckets[$key]['expenses'] += $row['expenses'];
            if($row['profit'] !== null) $buckets[$key]['profit'] += (float)$row['profit'];
            if($row['net_profit'] !== null) $buckets[$key]['net'] += (float)$row['net_profit'];
        }
        $labels = array(); $sales = array(); $profit = array(); $expenses = array(); $net = array();
        foreach($buckets as $b){
            $labels[] = $b['label'];
            $sales[] = round($b['sales'], 2);
            $profit[] = round($b['profit'], 2);
            $expenses[] = round($b['expenses'], 2);
            $net[] = round($b['net'], 2);
        }
        return compact('labels', 'sales', 'profit', 'expenses', 'net');
    }
    $labels = array(); $sales = array(); $profit = array(); $expenses = array(); $net = array();
    foreach($daily as $row){
        $labels[] = date('M d', strtotime($row['date']));
        $sales[] = round($row['sales'], 2);
        $profit[] = $row['profit'] !== null ? round((float)$row['profit'], 2) : 0;
        $expenses[] = round($row['expenses'], 2);
        $net[] = $row['net_profit'] !== null ? round((float)$row['net_profit'], 2) : 0;
    }
    return compact('labels', 'sales', 'profit', 'expenses', 'net');
}
function notifications_table_enabled(){
    global $conn;
    static $enabled = null;
    if($enabled !== null) return $enabled;
    $enabled = false;
    if(isset($conn) && $conn){
        $q = $conn->query("SHOW TABLES LIKE 'notifications'");
        if($q && $q->num_rows > 0) $enabled = true;
    }
    return $enabled;
}
function notification_type_allowed($type){
    $allowed = array('success', 'warning', 'danger', 'info');
    return in_array($type, $allowed, true) ? $type : 'info';
}
function admin_notify($type, $title, $message, $link = '', $ref_key = ''){
    global $conn;
    if(!notifications_table_enabled() || !isset($conn) || !$conn) return false;
    $bid = tenant_id();
    if($bid <= 0) return false;
    $type = notification_type_allowed($type);
    $title = $conn->real_escape_string(trim((string)$title));
    $message = $conn->real_escape_string(trim((string)$message));
    $link = $conn->real_escape_string(trim((string)$link));
    $ref_key = $conn->real_escape_string(trim((string)$ref_key));
    if($ref_key !== ''){
        $since = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $dup = $conn->query("SELECT id FROM notifications WHERE business_id = '{$bid}' AND ref_key = '{$ref_key}' AND date_created >= '{$since}' LIMIT 1");
        if($dup && $dup->num_rows > 0) return false;
    }
    $ref_sql = $ref_key !== '' ? "'{$ref_key}'" : 'NULL';
    $link_sql = $link !== '' ? "'{$link}'" : 'NULL';
    $now = date('Y-m-d H:i:s');
    return $conn->query("INSERT INTO notifications SET business_id = '{$bid}', user_id = NULL, type = '{$type}', title = '{$title}', message = '{$message}', link = {$link_sql}, ref_key = {$ref_sql}, date_created = '{$now}'");
}
function notifications_unread_count(){
    global $conn;
    if(!notifications_table_enabled() || !isset($conn) || !$conn) return 0;
    $qry = $conn->query("SELECT COUNT(*) AS total FROM notifications WHERE is_read = 0".tenant_sql());
    if($qry && ($row = $qry->fetch_assoc())) return (int)$row['total'];
    return 0;
}
function notifications_list($limit = 10, $unread_only = false){
    global $conn;
    $items = array();
    if(!notifications_table_enabled() || !isset($conn) || !$conn) return $items;
    $limit = max(1, (int)$limit);
    $where = 'WHERE 1=1'.tenant_sql();
    if($unread_only) $where .= ' AND is_read = 0';
    $qry = $conn->query("SELECT * FROM notifications {$where} ORDER BY date_created DESC, id DESC LIMIT {$limit}");
    if($qry){
        while($row = $qry->fetch_assoc()){
            $items[] = $row;
        }
    }
    return $items;
}
function notification_resolve_link(array $row){
    $link = isset($row['link']) ? trim((string)$row['link']) : '';
    $ref = isset($row['ref_key']) ? trim((string)$row['ref_key']) : '';
    $title = strtolower(stripslashes(isset($row['title']) ? (string)$row['title'] : ''));
    $admin = base_url.'admin/?page=';
    $all = base_url.'admin/?page=notifications';

    if($ref !== ''){
        if(preg_match('/^stock_out_\d+$/', $ref)) return $admin.'inventory&stock_filter=out';
        if(preg_match('/^stock_low_\d+$/', $ref)) return $admin.'inventory&stock_filter=low';
        if(preg_match('/^expiry_\d+$/', $ref)) return $admin.'inventory';
        if(preg_match('/^pending_orders_\d+$/', $ref)) return $admin.'orders&status=0';
        if(preg_match('/^sale_(\d+)$/', $ref, $m)) return $admin.'orders/view_order&id='.(int)$m[1];
        if(preg_match('/^expense_(\d+)$/', $ref, $m)) return $admin.'expenses/manage_expense&id='.(int)$m[1];
        if(preg_match('/^backup_/', $ref)) return $admin.'backup';
        if(preg_match('/^debt_overdue_(\d+)$/', $ref, $m)) return $admin.'debt/statement&client_id='.(int)$m[1];
        if(preg_match('/^debt_sale_/', $ref)) return $admin.'debt/customers';
        if(preg_match('/^debt_pay_/', $ref)) return $admin.'debt/history';
    }

    if($link !== '') return $link;

    if(strpos($title, 'out of stock') !== false) return $admin.'inventory&stock_filter=out';
    if(strpos($title, 'low stock') !== false) return $admin.'inventory&stock_filter=low';
    if(strpos($title, 'expir') !== false) return $admin.'inventory';
    if(strpos($title, 'open order') !== false || strpos($title, 'pending order') !== false) return $admin.'orders&status=0';
    if(strpos($title, 'overdue') !== false) return $admin.'debt/overdue';
    if(strpos($title, 'debt payment') !== false || (strpos($title, 'payment') !== false && strpos($title, 'debt') !== false)) return $admin.'debt/history';
    if(strpos($title, 'credit sale') !== false || strpos($title, 'debt') !== false) return $admin.'debt/customers';
    if(strpos($title, 'expense') !== false) return $admin.'expenses';
    if(strpos($title, 'sale') !== false) return $admin.'sales';
    if(strpos($title, 'backup') !== false) return $admin.'backup';
    if(strpos($title, 'customer') !== false) return $admin.'clients';

    return $all;
}
function notifications_sync_system(){
    if(!notifications_table_enabled()) return;
    global $conn;
    if(!isset($conn) || !$conn) return;
    $counts = inventory_stock_counts();
    if($counts['low'] > 0){
        admin_notify('warning', 'Low Stock Alert', format_num($counts['low']).' product variant(s) require replenishment.', base_url.'admin/?page=inventory&stock_filter=low', 'stock_low_'.$counts['low']);
    }
    if($counts['out'] > 0){
        admin_notify('danger', 'Out of Stock Alert', format_num($counts['out']).' product variant(s) are out of stock and unavailable for sale.', base_url.'admin/?page=inventory&stock_filter=out', 'stock_out_'.$counts['out']);
    }
    if(db_table_has_column('inventory', 'expiry_date')){
        $soon = date('Y-m-d', strtotime('+30 days'));
        $today = date('Y-m-d');
        $qry = $conn->query("SELECT COUNT(*) AS total FROM inventory i
            INNER JOIN products p ON p.id = i.product_id
            WHERE p.delete_flag = 0 AND i.expiry_date IS NOT NULL
            AND i.expiry_date BETWEEN '{$today}' AND '{$soon}'".tenant_sql('i'));
        if($qry && ($row = $qry->fetch_assoc()) && (int)$row['total'] > 0){
            admin_notify('warning', 'Expiry Alert', format_num($row['total']).' stock item(s) expire within 30 days.', base_url.'admin/?page=inventory', 'expiry_'.$row['total']);
        }
    }
    $pending = (int)$conn->query("SELECT COUNT(*) AS total FROM orders WHERE status = '0'".tenant_sql())->fetch_assoc()['total'];
    if($pending > 0){
        admin_notify('info', 'Open Orders', format_num($pending).' order(s) are awaiting fulfillment.', base_url.'admin/?page=orders&status=0', 'pending_orders_'.$pending);
    }
}
function backup_logs_table_enabled(){
    global $conn;
    static $enabled = null;
    if($enabled !== null) return $enabled;
    $enabled = false;
    if(isset($conn) && $conn){
        $q = $conn->query("SHOW TABLES LIKE 'backup_logs'");
        if($q && $q->num_rows > 0) $enabled = true;
    }
    return $enabled;
}
function backup_dir_path(){
    $dir = base_app.'uploads/backups/';
    if(!is_dir($dir)) @mkdir($dir, 0755, true);
    return $dir;
}
function app_ensure_upload_dirs(){
    $dirs = array(
        'uploads',
        'uploads/avatars',
        'uploads/brands',
        'uploads/backups',
        'uploads/system',
    );
    foreach($dirs as $rel){
        $path = base_app.$rel;
        if(!is_dir($path)) @mkdir($path, 0755, true);
    }
}
app_ensure_upload_dirs();
function backup_last_info(){
    global $conn;
    if(!backup_logs_table_enabled() || !isset($conn) || !$conn) return null;
    $qry = $conn->query("SELECT * FROM backup_logs WHERE status = 'success'".tenant_sql()." ORDER BY date_created DESC, id DESC LIMIT 1");
    if($qry && $qry->num_rows > 0) return $qry->fetch_assoc();
    return null;
}
function app_export_allowed($module){
    if(!isset($_SESSION['userdata']) || (int)$_SESSION['userdata']['login_type'] !== 1){
        return false;
    }
    $map = array(
        'products' => 'products',
        'inventory' => 'inventory_view',
        'inventory_template' => 'inventory_manage',
        'orders' => 'orders_view',
        'sales' => 'sales_report',
        'customers' => 'clients',
        'debt' => 'debt_reports',
        'debt_statement' => 'debt_view',
        'expenses' => 'expenses',
        'profit_loss' => 'profit_analytics',
        'brands' => 'brands',
        'categories' => 'categories',
        'backup_history' => 'backup_restore',
        'notifications' => 'dashboard_full',
    );
    if(!isset($map[$module])){
        return false;
    }
    if($module === 'profit_loss' && !admin_can_view_profit()){
        return false;
    }
    if($module === 'backup_history' && function_exists('admin_is_cashier') && admin_is_cashier()){
        return false;
    }
    if($module === 'notifications'){
        return admin_cashier_can('dashboard_full') || admin_cashier_can('dashboard_limited');
    }
    return admin_cashier_can($map[$module]);
}
function app_export_url($module, $format = 'xlsx', array $params = array()){
    $params['module'] = $module;
    $params['format'] = $format;
    return base_url . 'classes/Export.php?' . http_build_query($params);
}
function app_sanitize_export_token($value){
    $value = trim((string)$value);
    $value = preg_replace('/[^A-Za-z0-9._-]+/', '_', $value);
    return trim($value, '_');
}
function app_export_date($date = null){
    if($date === null || $date === '') return date('Y-m-d');
    $ts = strtotime($date);
    return $ts ? date('Y-m-d', $ts) : date('Y-m-d');
}
function app_export_filename($basename, $extension, $date = null){
    $ext = ltrim(strtolower((string)$extension), '.');
    $token = app_sanitize_export_token($basename);
    if($date !== null && $date !== ''){
        if(preg_match('/^\d{4}-\d{2}-\d{2}_to_\d{4}-\d{2}-\d{2}$/', $date)){
            $datePart = $date;
        }else{
            $datePart = app_export_date($date);
        }
        return 'Kalmoy_'.$token.'_'.$datePart.'.'.$ext;
    }
    return 'Kalmoy_'.$token.'.'.$ext;
}
function app_export_filename_range($basename, $extension, $date_start, $date_end){
    $start = app_export_date($date_start);
    $end = app_export_date($date_end);
    if($start === $end) return app_export_filename($basename, $extension, $start);
    return app_export_filename($basename, $extension, $start.'_to_'.$end);
}
function app_export_report_filename($report, $extension, $date_start = null, $date_end = null){
    $map = array(
        'sales' => 'Sales_Report',
        'profit' => 'Profit_Loss',
        'profit_loss' => 'Profit_Loss',
        'expense' => 'Expenses',
        'expenses' => 'Expenses',
        'inventory' => 'Inventory',
        'products' => 'Products',
        'clients' => 'Customers',
        'customers' => 'Customers',
        'orders' => 'Orders',
        'analytics' => 'Profit_Loss',
        'brands' => 'Brands',
        'categories' => 'Categories',
        'backup_history' => 'Backup_History',
        'notifications' => 'Activity_Log',
        'inventory_template' => 'Inventory_Import_Template',
    );
    $base = isset($map[$report]) ? $map[$report] : 'Report';
    if($date_start !== null && $date_end !== null){
        return app_export_filename_range($base, $extension, $date_start, $date_end);
    }
    return app_export_filename($base, $extension);
}
function app_backup_filename($datetime = null){
    if($datetime === null || $datetime === '') $ts = time();
    elseif(is_numeric($datetime)) $ts = (int)$datetime;
    else $ts = strtotime($datetime);
    if(!$ts) $ts = time();
    return 'Kalmoy_Backup_'.date('Y-m-d_H-i-s', $ts).'.sql';
}
function app_backup_download_filename($stored_filename, $date_created = null){
    $stored_filename = basename((string)$stored_filename);
    if(preg_match('/^(?:ASH|Kalmoy)_Backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $stored_filename)){
        return $stored_filename;
    }
    if($date_created !== null && $date_created !== ''){
        return app_backup_filename($date_created);
    }
    return $stored_filename;
}
function app_receipt_filename($receipt_no){
    $ref = app_sanitize_export_token($receipt_no);
    if($ref === '') $ref = 'UNKNOWN';
    return 'Kalmoy_Receipt_'.$ref.'.pdf';
}
function format_file_size($bytes){
    $bytes = (float)$bytes;
    if($bytes >= 1073741824) return round($bytes / 1073741824, 2).' GB';
    if($bytes >= 1048576) return round($bytes / 1048576, 2).' MB';
    if($bytes >= 1024) return round($bytes / 1024, 2).' KB';
    return round($bytes).' B';
}
function expense_format_id($id){
    return 'EXP-'.str_pad((int)$id, 5, '0', STR_PAD_LEFT);
}
function debt_tables_ready(){
    global $conn;
    return class_exists('CustomerDebtService') && CustomerDebtService::tables_ready($conn);
}
function debt_dashboard_stats(){
    global $conn;
    if(!debt_tables_ready()) return CustomerDebtService::dashboard_stats(null);
    return CustomerDebtService::dashboard_stats($conn);
}
function debt_can_view(){
    return !admin_is_cashier() || admin_cashier_has_permission('debt_view');
}
function debt_can_sale(){
    return !admin_is_cashier() || admin_cashier_has_permission('debt_sale');
}
function debt_can_payment(){
    return !admin_is_cashier() || admin_cashier_has_permission('debt_payment');
}
function debt_can_reports(){
    return !admin_is_cashier() || admin_cashier_has_permission('debt_reports');
}
function sales_can_edit_sale(){
    if(!admin_is_cashier()) return true;
    return admin_cashier_has_permission('orders_manage');
}
function ash_swal_set_flash(array $options){
    $_SESSION['_ash_swal_flash'] = $options;
}
function ash_swal_render_flash(){
    if(empty($_SESSION['_ash_swal_flash'])) return '';
    $json = json_encode($_SESSION['_ash_swal_flash'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    unset($_SESSION['_ash_swal_flash']);
    return '<script>$(function(){if(window.ashSwalRun){ashSwalRun('.$json.');}});</script>';
}
function ash_swal_inline_script(array $options){
    $json = json_encode($options, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    return '<script>$(function(){if(window.ashSwalRun){ashSwalRun('.$json.');}});</script>';
}
function ash_swal_access_denied_script($redirect_url){
    return ash_swal_inline_script(array(
        'icon' => 'error',
        'title' => 'Access Denied',
        'text' => 'You do not have permission to access this area.',
        'confirmButtonText' => 'OK',
        'redirect' => $redirect_url,
    ));
}
require_once __DIR__ . '/inc/seo.php';
ob_end_flush();
?>