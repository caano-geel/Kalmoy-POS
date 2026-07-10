<?php
require_once __DIR__ . '/AshSpreadsheetExport.php';
require_once __DIR__ . '/AshPdfExport.php';

class ModuleExportService {
    private $conn;
    private $settings;

    public function __construct($conn, $settings = null){
        $this->conn = $conn;
        $this->settings = $settings;
    }

    public function handle($module, $format, array $params = array()){
        $module = strtolower(trim((string)$module));
        $format = strtolower(trim((string)$format));
        if(!app_export_allowed($module)){
            $this->deny('Access denied.');
        }
        if(!in_array($format, array('xlsx', 'pdf', 'print'), true)){
            $this->deny('Invalid export format.');
        }
        $method = 'export' . str_replace(' ', '', ucwords(str_replace('_', ' ', $module)));
        if(!method_exists($this, $method)){
            $this->deny('Unknown export module.');
        }
        $this->$method($format, $params);
    }

    private function deny($msg){
        while(ob_get_level() > 0) ob_end_clean();
        header('HTTP/1.1 403 Forbidden');
        exit($msg);
    }

    private function subtitleRange($start, $end){
        if($start && $end && $start !== $end){
            return $start . ' to ' . $end;
        }
        return $start ?: date('Y-m-d');
    }

    private function dispatch($filename, $title, array $headers, array $rows, $format, array $xlsxOpts = array(), $subtitle = ''){
        if($format === 'xlsx'){
            AshSpreadsheetExport::send($filename, $headers, $rows, array_merge(array('sheet_title' => $title), $xlsxOpts));
        }
        $html = AshPdfExport::tableHtml($title, $subtitle, $headers, $rows);
        if($format === 'pdf'){
            AshPdfExport::send($filename, $title, $html);
        }
        AshPdfExport::printPage($title, $html);
    }

    public function exportProducts($format, array $params = array()){
        $headers = array('Product Name', 'Barcode', 'Brand', 'Category', 'Status', 'Description');
        $rows = array();
        $sql = "SELECT p.name, p.barcode, b.name AS brand, c.category, p.status, p.specs
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.delete_flag = 0
            ORDER BY p.name ASC";
        $q = $this->conn->query($sql);
        while($q && ($r = $q->fetch_assoc())){
            $rows[] = array(
                stripslashes($r['name']),
                stripslashes((string)$r['barcode']),
                stripslashes((string)$r['brand']),
                stripslashes((string)$r['category']),
                ((int)$r['status'] === 1) ? 'Active' : 'Inactive',
                trim(strip_tags(stripslashes(html_entity_decode($r['specs'])))),
            );
        }
        $this->dispatch(
            app_export_filename('Products', 'xlsx'),
            'Products',
            $headers,
            $rows,
            $format,
            array('text_cols' => array(1), 'sheet_title' => 'Products')
        );
    }

    public function exportInventory($format, array $params = array()){
        $threshold = inventory_low_stock_threshold();
        $avail = inventory_available_stock_sql('i');
        $sold = inventory_sold_subquery_sql();
        $headers = array('Product Name', 'Barcode', 'Brand', 'Category', 'Variant', 'Retail Price', 'Unit Cost', 'Quantity', 'Low Stock Alert', 'Status');
        $rows = array();
        $sql = "SELECT p.name, p.barcode, COALESCE(b.name,'') AS brand, COALESCE(c.category,'') AS category, i.variant,
            i.price, i.cost_price, {$avail} AS qty, p.status
            FROM inventory i
            INNER JOIN products p ON p.id = i.product_id
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN {$sold} sold ON sold.inventory_id = i.id
            WHERE p.delete_flag = 0
            ORDER BY p.name ASC, i.variant ASC";
        $q = $this->conn->query($sql);
        while($q && ($r = $q->fetch_assoc())){
            $qty = max(0, (float)$r['qty']);
            $rows[] = array(
                stripslashes($r['name']),
                stripslashes((string)$r['barcode']),
                stripslashes($r['brand']),
                stripslashes($r['category']),
                stripslashes($r['variant']),
                round((float)$r['price'], 2),
                round((float)$r['cost_price'], 2),
                (int)round($qty),
                (int)$threshold,
                ((int)$r['status'] === 1) ? 'Active' : 'Inactive',
            );
        }
        $this->dispatch(
            app_export_filename('Inventory', 'xlsx'),
            'Stock & Inventory',
            $headers,
            $rows,
            $format,
            array('text_cols' => array(1), 'money_cols' => array(5, 6), 'int_cols' => array(7, 8), 'sheet_title' => 'Inventory')
        );
    }

    public function exportOrders($format, array $params = array()){
        $status = isset($params['status']) ? trim($params['status']) : '';
        $where = '';
        if($status !== '' && in_array($status, array('0','1','2','3','4'), true)){
            $where = " WHERE o.status = '" . (int)$status . "' ";
        }
        $headers = array('Date', 'Receipt', 'Customer', 'Amount', 'Paid', 'Status', 'Payment Method');
        $rows = array();
        $sql = "SELECT o.*, CONCAT(c.firstname,' ',c.lastname) AS client
            FROM orders o
            INNER JOIN clients c ON c.id = o.client_id
            {$where}
            ORDER BY UNIX_TIMESTAMP(o.date_created) DESC";
        $q = $this->conn->query($sql);
        $statusMap = array('0' => 'Open', '1' => 'Packed', '2' => 'Out for Delivery', '3' => 'Delivered', '4' => 'Cancelled');
        while($q && ($r = $q->fetch_assoc())){
            $rows[] = array(
                date('Y-m-d H:i', strtotime($r['date_created'])),
                stripslashes($r['ref_code']),
                stripslashes($r['client']),
                round((float)$r['amount'], 2),
                ((int)$r['paid'] === 1) ? 'Yes' : 'No',
                isset($statusMap[(string)$r['status']]) ? $statusMap[(string)$r['status']] : $r['status'],
                stripslashes((string)$r['payment_method']),
            );
        }
        $this->dispatch(
            app_export_filename('Orders', 'xlsx'),
            'Orders',
            $headers,
            $rows,
            $format,
            array('date_cols' => array(0), 'money_cols' => array(3), 'sheet_title' => 'Orders')
        );
    }

    public function exportCustomers($format, array $params = array()){
        $headers = array('Customer Name', 'Email', 'Phone', 'Status', 'Date Created');
        $rows = array();
        $q = $this->conn->query("SELECT *, CONCAT(firstname,' ',lastname) AS fullname FROM clients WHERE delete_flag = 0 ORDER BY fullname ASC");
        while($q && ($r = $q->fetch_assoc())){
            $rows[] = array(
                stripslashes($r['fullname']),
                stripslashes((string)$r['email']),
                stripslashes((string)($r['contact'] ?? '')),
                ((int)$r['status'] === 1) ? 'Active' : 'Inactive',
                date('Y-m-d', strtotime($r['date_created'])),
            );
        }
        $this->dispatch(app_export_filename('Customers', 'xlsx'), 'Customers', $headers, $rows, $format, array('date_cols' => array(4)));
    }

    public function exportBrands($format, array $params = array()){
        $counts = function_exists('ash_brand_product_counts') ? ash_brand_product_counts() : array();
        $headers = array('Brand', 'Products', 'Status', 'Description');
        $rows = array();
        $q = $this->conn->query("SELECT * FROM brands WHERE delete_flag = 0 ORDER BY name ASC");
        while($q && ($r = $q->fetch_assoc())){
            $rows[] = array(
                stripslashes($r['name']),
                isset($counts[(int)$r['id']]) ? (int)$counts[(int)$r['id']] : 0,
                ((int)$r['status'] === 1) ? 'Active' : 'Inactive',
                trim(strip_tags(stripslashes(html_entity_decode($r['description'])))),
            );
        }
        $this->dispatch(app_export_filename('Brands', 'xlsx'), 'Brands', $headers, $rows, $format, array('int_cols' => array(1)));
    }

    public function exportCategories($format, array $params = array()){
        $counts = function_exists('ash_category_product_counts') ? ash_category_product_counts() : array();
        $headers = array('Category', 'Products', 'Status', 'Description');
        $rows = array();
        $q = $this->conn->query("SELECT * FROM categories WHERE delete_flag = 0 ORDER BY category ASC");
        while($q && ($r = $q->fetch_assoc())){
            $rows[] = array(
                stripslashes($r['category']),
                isset($counts[(int)$r['id']]) ? (int)$counts[(int)$r['id']] : 0,
                ((int)$r['status'] === 1) ? 'Active' : 'Inactive',
                trim(strip_tags(stripslashes(html_entity_decode($r['description'])))),
            );
        }
        $this->dispatch(app_export_filename('Categories', 'xlsx'), 'Categories', $headers, $rows, $format, array('int_cols' => array(1)));
    }

    public function exportBackupHistory($format, array $params = array()){
        if(!function_exists('backup_logs_table_enabled') || !backup_logs_table_enabled()){
            $this->deny('Backup history is not available.');
        }
        $headers = array('#', 'File Name', 'File Size', 'Date Created', 'Created By', 'Status', 'Message');
        $rows = array();
        $i = 1;
        $q = $this->conn->query("SELECT * FROM backup_logs ORDER BY date_created DESC, id DESC");
        while($q && ($r = $q->fetch_assoc())){
            $rows[] = array(
                $i++,
                $r['filename'],
                format_file_size($r['file_size']),
                date('Y-m-d H:i', strtotime($r['date_created'])),
                $r['created_by_name'] !== '' ? $r['created_by_name'] : 'System',
                $r['status'],
                stripslashes((string)$r['message']),
            );
        }
        $this->dispatch(app_export_filename('Backup_History', 'xlsx'), 'Backup History', $headers, $rows, $format, array('date_cols' => array(3), 'int_cols' => array(0)));
    }

    public function exportNotifications($format, array $params = array()){
        $headers = array('Date', 'Type', 'Title', 'Message', 'Status');
        $rows = array();
        $list = notifications_list(500, false);
        foreach($list as $n){
            $rows[] = array(
                date('Y-m-d H:i', strtotime($n['date_created'])),
                ucfirst($n['type']),
                stripslashes($n['title']),
                stripslashes($n['message']),
                ((int)$n['is_read'] === 1) ? 'Read' : 'New',
            );
        }
        $this->dispatch(app_export_filename('Activity_Log', 'xlsx'), 'Activity Log', $headers, $rows, $format, array('date_cols' => array(0)));
    }

    public function exportExpenses($format, array $params = array()){
        $range = report_resolve_range(
            isset($params['report_preset']) ? $params['report_preset'] : 'month',
            isset($params['date_start']) ? $params['date_start'] : '',
            isset($params['date_end']) ? $params['date_end'] : ''
        );
        $category = isset($params['category']) ? trim($params['category']) : '';
        $headers = array('Expense ID', 'Date', 'Category', 'Description', 'Payment', 'Amount', 'Created By');
        $rows = array();
        if(function_exists('expenses_table_enabled') && expenses_table_enabled()){
            $where = expenses_where_sql($range['start'], $range['end'], $category);
            $q = $this->conn->query("SELECT * FROM expenses WHERE {$where} ORDER BY expense_date DESC, id DESC");
            while($q && ($r = $q->fetch_assoc())){
                $rows[] = array(
                    expense_format_id($r['id']),
                    date('Y-m-d', strtotime($r['expense_date'])),
                    stripslashes($r['category']),
                    stripslashes($r['description']),
                    stripslashes((string)$r['payment_method']),
                    round((float)$r['amount'], 2),
                    stripslashes((string)$r['created_by_name']),
                );
            }
        }
        $filename = app_export_filename_range('Expenses', 'xlsx', $range['start'], $range['end']);
        $this->dispatch($filename, 'Expenses', $headers, $rows, $format, array('money_cols' => array(5), 'date_cols' => array(1)), $this->subtitleRange($range['start'], $range['end']));
    }

    public function exportSales($format, array $params = array()){
        $range = report_resolve_range(
            isset($params['report_preset']) ? $params['report_preset'] : 'week',
            isset($params['date_start']) ? $params['date_start'] : '',
            isset($params['date_end']) ? $params['date_end'] : ''
        );
        $payment = isset($params['payment_method']) ? trim($params['payment_method']) : '';
        $show_profit = admin_can_view_profit();
        $data = $this->fetchSalesRows($range['start'], $range['end'], $payment, $show_profit);
        $headers = array('#', 'Date', 'Receipt No.', 'Product', 'Customer', 'Payment Method', 'Unit Price', 'Qty');
        if($show_profit) $headers[] = 'Gross Profit';
        $headers[] = 'Line Total';
        $rows = array();
        $i = 1;
        foreach($data['rows'] as $row){
            $line = array(
                $i++,
                date('Y-m-d H:i', strtotime($row['order_date'])),
                $row['ref_code'],
                $row['product_name'],
                $row['customer_name'],
                $row['payment_label'],
                round((float)$row['price'], 2),
                (int)$row['quantity'],
            );
            if($show_profit){
                $line[] = $row['profit_calculable'] ? round((float)$row['line_profit'], 2) : '';
            }
            $line[] = round((float)$row['line_total'], 2);
            $rows[] = $line;
        }
        $money = array(6, count($headers) - 1);
        if($show_profit) $money[] = 8;
        $filename = app_export_filename_range('Sales_Report', 'xlsx', $range['start'], $range['end']);
        $this->dispatch($filename, 'Sales Report', $headers, $rows, $format, array(
            'date_cols' => array(1),
            'money_cols' => $money,
            'int_cols' => array(7),
            'sheet_title' => 'Sales Report',
        ), $this->subtitleRange($range['start'], $range['end']));
    }

    public function exportProfitLoss($format, array $params = array()){
        if(!admin_can_view_profit()){
            $this->deny('Access denied.');
        }
        $range = report_resolve_range(
            isset($params['report_preset']) ? $params['report_preset'] : 'month',
            isset($params['date_start']) ? $params['date_start'] : '',
            isset($params['date_end']) ? $params['date_end'] : ''
        );
        $report = profit_analytics_report_rows($range['start'], $range['end']);
        $headers = array('Period', 'Revenue', 'COGS', 'Gross Profit', 'Expenses', 'Net Profit');
        $rows = array();
        foreach($report['rows'] as $r){
            $rows[] = array(
                $r['date'],
                round((float)$r['sales'], 2),
                $r['cost'] === null ? '' : round((float)$r['cost'], 2),
                $r['profit'] === null ? '' : round((float)$r['profit'], 2),
                round((float)$r['expenses'], 2),
                $r['net_profit'] === null ? '' : round((float)$r['net_profit'], 2),
            );
        }
        $filename = app_export_filename_range('Profit_Loss', 'xlsx', $range['start'], $range['end']);
        $this->dispatch($filename, 'Profit & Loss', $headers, $rows, $format, array(
            'money_cols' => array(1, 2, 3, 4, 5),
            'sheet_title' => 'Profit Loss',
        ), $this->subtitleRange($range['start'], $range['end']));
    }

    public function exportInventoryTemplate($format, array $params = array()){
        if($format !== 'xlsx'){
            $this->deny('Template is available as Excel only.');
        }
        require_once __DIR__ . '/ModuleExportService.php';
        $svc = new ModuleExportService($this->conn);
        $svc->exportInventoryTemplate('xlsx');
    }

    private function fetchSalesRows($date_start, $date_end, $payment_filter, $show_profit){
        require_once __DIR__ . '/SalesReportExportData.php';
        return SalesReportExportData::rows($this->conn, $date_start, $date_end, $payment_filter, $show_profit);
    }

    public function exportDebt($format, array $params = array()){
        $type = isset($params['type']) ? $params['type'] : 'customers';
        if($type === 'payments'){
            $headers = array('Date', 'Customer', 'Phone', 'Method', 'Reference', 'Amount', 'Received By');
            $rows = array();
            $q = $this->conn->query("SELECT dp.*, CONCAT(c.firstname,' ',c.lastname) AS fullname, c.contact
                FROM debt_payments dp INNER JOIN clients c ON c.id = dp.client_id ORDER BY dp.date_created DESC");
            while($q && ($r = $q->fetch_assoc())){
                $rows[] = array(
                    $r['date_created'], $r['fullname'], $r['contact'], $r['payment_method'],
                    $r['reference'], round((float)$r['amount'], 2), $r['created_by_name'],
                );
            }
            $this->dispatch(app_export_filename('Debt_Payments', $format), 'Debt Payments', $headers, $rows, $format, array('money_cols' => array(5)));
            return;
        }
        if($type === 'report'){
            $start = isset($params['date_start']) ? $params['date_start'] : date('Y-m-01');
            $end = isset($params['date_end']) ? $params['date_end'] : date('Y-m-d');
            $start_esc = $this->conn->real_escape_string($start);
            $end_esc = $this->conn->real_escape_string($end);
            $headers = array('Date', 'Credit Sales', 'Collections');
            $dates = array();
            $q1 = $this->conn->query("SELECT DATE(date_created) AS d, SUM(amount) AS t FROM customer_debts WHERE DATE(date_created) BETWEEN '{$start_esc}' AND '{$end_esc}' GROUP BY DATE(date_created)");
            while($q1 && ($r = $q1->fetch_assoc())) $dates[$r['d']] = array('credit' => (float)$r['t'], 'pay' => 0);
            $q2 = $this->conn->query("SELECT DATE(date_created) AS d, SUM(amount) AS t FROM debt_payments WHERE DATE(date_created) BETWEEN '{$start_esc}' AND '{$end_esc}' GROUP BY DATE(date_created)");
            while($q2 && ($r = $q2->fetch_assoc())){
                if(!isset($dates[$r['d']])) $dates[$r['d']] = array('credit' => 0, 'pay' => 0);
                $dates[$r['d']]['pay'] = (float)$r['t'];
            }
            krsort($dates);
            $rows = array();
            foreach($dates as $d => $v){
                $rows[] = array($d, round($v['credit'], 2), round($v['pay'], 2));
            }
            $this->dispatch(app_export_filename_range('Debt_Report', $format, $start, $end), 'Debt Report', $headers, $rows, $format, array('money_cols' => array(1, 2)), $this->subtitleRange($start, $end));
            return;
        }
        $headers = array('Customer', 'Phone', 'Outstanding', 'Last Purchase', 'Last Payment', 'Status');
        $rows = array();
        foreach(CustomerDebtService::customers_with_debt($this->conn) as $r){
            $rows[] = array(
                $r['fullname'], $r['contact'], round((float)$r['outstanding'], 2),
                $r['last_purchase'] ? date('Y-m-d', strtotime($r['last_purchase'])) : '',
                $r['last_payment'] ? date('Y-m-d', strtotime($r['last_payment'])) : '',
                $r['status_label'],
            );
        }
        $this->dispatch(app_export_filename('Outstanding_Debts', $format), 'Outstanding Debts', $headers, $rows, $format, array('money_cols' => array(2)));
    }

    public function exportDebtStatement($format, array $params = array()){
        $client_id = isset($params['client_id']) ? (int)$params['client_id'] : 0;
        if($client_id <= 0) $this->deny('Customer required.');
        $cq = $this->conn->query("SELECT CONCAT(firstname,' ',lastname) AS fullname, contact FROM clients WHERE id = '{$client_id}' LIMIT 1");
        if(!$cq || !$cq->num_rows) $this->deny('Customer not found.');
        $client = $cq->fetch_assoc();
        $statement = CustomerDebtService::client_statement($this->conn, $client_id);
        $headers = array('Date', 'Description', 'Debit', 'Credit', 'Balance');
        $rows = array();
        foreach($statement as $row){
            $rows[] = array(
                $row['date'], $row['description'],
                $row['debit'] > 0 ? round($row['debit'], 2) : '',
                $row['credit'] > 0 ? round($row['credit'], 2) : '',
                round($row['balance'], 2),
            );
        }
        $safe = preg_replace('/[^A-Za-z0-9_]+/', '_', $client['fullname']);
        $filename = app_export_filename('Statement_'.$safe, $format);
        $this->dispatch($filename, 'Customer Statement — '.$client['fullname'], $headers, $rows, $format, array('money_cols' => array(2, 3, 4)), $client['contact']);
    }
}
