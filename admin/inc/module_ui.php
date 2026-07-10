<?php
/**
 * Shared UI styles and helpers for admin module pages (matches dashboard).
 */
function module_mini_stat($label, $value, $icon, $icon_bg){
    return '<div class="col-6 col-lg-3 mb-2 ash-stat-col">'
        .'<div class="mod-mini-stat h-100">'
        .'<div class="mod-mini-icon '.$icon_bg.'"><i class="fas '.$icon.'"></i></div>'
        .'<div class="mod-mini-body"><div class="mod-mini-label">'.htmlspecialchars($label).'</div>'
        .'<div class="mod-mini-value">'.$value.'</div></div></div></div>';
}
function module_mini_stat_pl($amount, $icon, $icon_bg, $period_label){
    $label = dashboard_net_profit_label($amount, $period_label);
    $value = dashboard_format_net_profit_display($amount);
    $loss = dashboard_is_net_loss($amount);
    $icon_class = $loss ? 'bg-danger' : $icon_bg;
    $stat_class = $loss ? 'mod-mini-stat h-100 pl-stat-loss' : 'mod-mini-stat h-100 pl-stat-profit';
    $tip = htmlspecialchars(dashboard_pl_formula_tooltip(), ENT_QUOTES);
    return '<div class="col-6 col-lg-3 mb-2 ash-stat-col">'
        .'<div class="'.$stat_class.'" title="'.$tip.'" data-toggle="tooltip" data-placement="top">'
        .'<div class="mod-mini-icon '.$icon_class.'"><i class="fas '.$icon.'"></i></div>'
        .'<div class="mod-mini-body"><div class="mod-mini-label">'.htmlspecialchars($label).'</div>'
        .'<div class="mod-mini-value">'.$value.'</div></div></div></div>';
}
function module_report_date_filter($page, $report_range, $extra_fields = array()){
    $preset = isset($report_range['preset']) ? $report_range['preset'] : 'custom';
    $date_start = $report_range['start'];
    $date_end = $report_range['end'];
    $label = isset($report_range['label']) ? $report_range['label'] : '';
    $bounds = business_operating_bounds();
    $html = '<div class="mod-report-presets d-flex flex-wrap align-items-center mb-2">';
    foreach(report_date_presets() as $key => $text){
        if($key === 'custom') continue;
        $active = ($preset === $key) ? ' active' : '';
        $html .= '<a href="?page='.urlencode($page).'&report_preset='.urlencode($key).'" class="btn btn-sm btn-outline-secondary mod-preset-btn'.$active.'">'.htmlspecialchars($text).'</a>';
    }
    $html .= '</div>';
    if($label !== ''){
        $html .= '<p class="small text-muted mb-2"><i class="far fa-calendar-alt mr-1"></i> Showing: <strong>'.htmlspecialchars($label).'</strong>';
        if($bounds['is_closed']){
            $html .= ' &middot; <span class="text-warning">Business closed '.htmlspecialchars(date('M j, Y', strtotime($bounds['closed_date']))).'</span>';
        }
        $html .= '</p>';
    }
    $html .= '<form method="get" class="mod-filter-card mb-0">';
    $html .= '<input type="hidden" name="page" value="'.htmlspecialchars($page).'">';
    $html .= '<input type="hidden" name="report_preset" value="custom">';
    foreach($extra_fields as $fk => $fv){
        $html .= '<input type="hidden" name="'.htmlspecialchars($fk).'" value="'.htmlspecialchars($fv).'">';
    }
    $html .= '<div class="row align-items-end">';
    $html .= '<div class="col-12 col-md-4 form-group mb-2 mb-md-0"><label>Date From</label>';
    $html .= '<input type="date" name="date_start" class="form-control form-control-sm" value="'.htmlspecialchars($date_start).'" min="'.htmlspecialchars($bounds['start']).'" max="'.htmlspecialchars($bounds['end']).'"></div>';
    $html .= '<div class="col-12 col-md-4 form-group mb-2 mb-md-0"><label>Date To</label>';
    $html .= '<input type="date" name="date_end" class="form-control form-control-sm" value="'.htmlspecialchars($date_end).'" min="'.htmlspecialchars($bounds['start']).'" max="'.htmlspecialchars($bounds['end']).'"></div>';
    $html .= '<div class="col-12 col-md-4 form-group mb-0"><button type="submit" class="btn btn-sm btn-success btn-block" style="border-radius:8px;font-weight:600;"><i class="fas fa-search mr-1"></i> Apply Custom Range</button></div>';
    $html .= '</div></form>';
    return $html;
}
function module_lifetime_summary_cards($summary){
    $items = array(
        array('Total Revenue', format_price($summary['total_revenue']), 'fa-coins', 'bg-success'),
        array('Total COGS', $summary['total_cogs'] === null ? '&mdash;' : format_price($summary['total_cogs']), 'fa-boxes', 'bg-secondary'),
        array('Gross Profit', $summary['gross_profit'] === null ? '&mdash;' : format_price($summary['gross_profit']), 'fa-chart-line', 'bg-teal'),
        array('Operating Expenses', format_price($summary['operating_expenses']), 'fa-file-invoice-dollar', 'bg-danger'),
        array('Net Profit', $summary['net_profit'] === null ? '&mdash;' : dashboard_format_net_profit_display($summary['net_profit']), 'fa-hand-holding-usd', dashboard_is_net_loss($summary['net_profit']) ? 'bg-danger' : 'bg-primary'),
        array('Total Orders', format_num($summary['total_orders']), 'fa-receipt', 'bg-indigo'),
        array('Total Customers', format_num($summary['total_customers']), 'fa-users', 'bg-pink'),
        array('Stock Value', format_price($summary['stock_value']), 'fa-tags', 'bg-warning'),
    );
    $html = '<div class="row">';
    foreach($items as $item){
        $html .= module_mini_stat($item[0], $item[1], $item[2], $item[3]);
    }
    $html .= '</div>';
    return $html;
}
function ash_table_id_badge($id){
    return '<span class="ash-badge ash-badge-id">'.htmlspecialchars($id).'</span>';
}
function ash_expense_category_badge($category){
    $cat = trim((string)$category);
    $map = array(
        'Rent' => 'ash-badge-cat-rent',
        'Salaries' => 'ash-badge-cat-salaries',
        'Utilities' => 'ash-badge-cat-utilities',
        'Transport' => 'ash-badge-cat-transport',
        'Supplies' => 'ash-badge-cat-supplies',
        'Marketing' => 'ash-badge-cat-marketing',
        'Maintenance' => 'ash-badge-cat-maintenance',
        'Bank Charges' => 'ash-badge-cat-bank',
        'Packaging' => 'ash-badge-cat-packaging',
        'Insurance' => 'ash-badge-cat-insurance',
    );
    $cls = isset($map[$cat]) ? $map[$cat] : 'ash-badge-cat-default';
    return '<span class="ash-badge ash-badge-cat '.$cls.'">'.htmlspecialchars($cat).'</span>';
}
function ash_table_actions_open(){
    return '<div class="ash-table-actions">';
}
function ash_table_actions_close(){
    return '</div>';
}
function ash_status_badge($label, $variant = 'active'){
    $map = array(
        'active' => 'ash-status-active',
        'inactive' => 'ash-status-inactive',
        'open' => 'ash-status-open',
        'packed' => 'ash-status-packed',
        'delivery' => 'ash-status-delivery',
        'delivered' => 'ash-status-delivered',
        'cancelled' => 'ash-status-cancelled',
        'paid_yes' => 'ash-status-paid-yes',
        'paid_no' => 'ash-status-paid-no',
        'stock_in' => 'ash-stock-in',
        'stock_low' => 'ash-stock-low',
        'stock_out' => 'ash-stock-out',
    );
    $cls = isset($map[$variant]) ? $map[$variant] : 'ash-status-open';
    return '<span class="ash-status-badge badge '.$cls.'">'.htmlspecialchars($label).'</span>';
}
function ash_icon_btn($type, $title, $extra_class = '', $attrs = array()){
    $types = array('view'=>'ash-icon-btn-view fa-eye', 'edit'=>'ash-icon-btn-edit fa-edit', 'delete'=>'ash-icon-btn-delete fa-trash',
        'pay'=>'ash-icon-btn-pay fa-money-bill-wave', 'restore'=>'ash-icon-btn-restore fa-undo', 'download'=>'ash-icon-btn-download fa-download');
    $parts = isset($types[$type]) ? explode(' ', $types[$type]) : array('ash-icon-btn-view', 'fa-eye');
    $attr_html = '';
    foreach($attrs as $k => $v){
        $attr_html .= ' '.htmlspecialchars($k).'="'.htmlspecialchars($v).'"';
    }
    return '<button type="button" class="ash-icon-btn '.$parts[0].' '.$extra_class.'" title="'.htmlspecialchars($title).'" data-toggle="tooltip" data-placement="top"'.$attr_html.'><i class="fas '.$parts[1].'"></i></button>';
}
function ash_icon_link($href, $type, $title, $extra_class = ''){
    $types = array('view'=>'ash-icon-btn-view fa-eye', 'edit'=>'ash-icon-btn-edit fa-edit', 'download'=>'ash-icon-btn-download fa-download');
    $parts = isset($types[$type]) ? explode(' ', $types[$type]) : array('ash-icon-btn-view', 'fa-eye');
    return '<a href="'.htmlspecialchars($href).'" class="ash-icon-btn '.$parts[0].' '.$extra_class.'" title="'.htmlspecialchars($title).'" data-toggle="tooltip" data-placement="top"><i class="fas '.$parts[1].'"></i></a>';
}
function ash_product_cell($name, $barcode = '', $brand = '', $image = ''){
    $name = trim((string)$name);
    $barcode = trim((string)$barcode);
    $brand = trim((string)$brand);
    $initial = $name !== '' ? strtoupper(mb_substr($name, 0, 1)) : '?';
    $thumb = '';
    if($image !== ''){
        $thumb = '<img src="'.validate_image($image).'" alt="" class="ash-product-thumb">';
    }else{
        $thumb = '<span class="ash-product-thumb-placeholder"><i class="fas fa-box-open"></i></span>';
    }
    $sub = '';
    if($barcode !== '') $sub .= '<span><code>'.htmlspecialchars($barcode).'</code></span>';
    if($brand !== '') $sub .= ($sub ? ' · ' : '').'<span>'.htmlspecialchars($brand).'</span>';
    if($sub === '') $sub = '<span>&mdash;</span>';
    return '<div class="ash-product-cell">'.$thumb
        .'<div class="ash-product-meta"><div class="ash-product-name">'.htmlspecialchars($name).'</div>'
        .'<div class="ash-product-sub">'.$sub.'</div></div></div>';
}
function ash_inventory_product_cell($name, $barcode = '', $brand = '', $variant = ''){
    $name = trim((string)$name);
    $brand = trim((string)$brand);
    $variant = trim((string)$variant);
    $thumb = '<span class="ash-product-thumb-placeholder"><i class="fas fa-box-open"></i></span>';
    $sub = array();
    if($barcode !== '') $sub[] = '<code>'.htmlspecialchars($barcode).'</code>';
    if($brand !== '') $sub[] = htmlspecialchars($brand);
    if($variant !== '') $sub[] = htmlspecialchars($variant);
    $sub_html = count($sub) ? implode(' · ', $sub) : '&mdash;';
    return '<div class="ash-product-cell">'.$thumb
        .'<div class="ash-product-meta"><div class="ash-product-name">'.htmlspecialchars($name).'</div>'
        .'<div class="ash-product-sub">'.$sub_html.'</div></div></div>';
}
function ash_truncate_tooltip($text, $max_lines = 2){
    $text = trim((string)$text);
    if($text === '') return '&mdash;';
    $esc = htmlspecialchars($text);
    return '<span class="ash-text-truncate-2" title="'.htmlspecialchars($text, ENT_QUOTES).'" data-toggle="tooltip" data-placement="top">'.$esc.'</span>';
}
function ash_email_cell($email){
    $email = trim((string)$email);
    if($email === '') return '&mdash;';
    return '<span class="ash-email-short" title="'.htmlspecialchars($email, ENT_QUOTES).'" data-toggle="tooltip">'.htmlspecialchars($email).'</span>';
}
function ash_brand_product_counts(){
    global $conn;
    static $cache = null;
    if($cache !== null) return $cache;
    $cache = array();
    if(isset($conn) && $conn){
        $qry = $conn->query("SELECT brand_id, COUNT(*) AS cnt FROM products WHERE delete_flag = 0 GROUP BY brand_id");
        if($qry){ while($r = $qry->fetch_assoc()) $cache[(int)$r['brand_id']] = (int)$r['cnt']; }
    }
    return $cache;
}
function ash_category_product_counts(){
    global $conn;
    static $cache = null;
    if($cache !== null) return $cache;
    $cache = array();
    if(isset($conn) && $conn){
        $qry = $conn->query("SELECT category_id, COUNT(*) AS cnt FROM products WHERE delete_flag = 0 GROUP BY category_id");
        if($qry){ while($r = $qry->fetch_assoc()) $cache[(int)$r['category_id']] = (int)$r['cnt']; }
    }
    return $cache;
}
function ash_permission_groups(){
    return array(
        'Dashboard' => array('dashboard_full', 'dashboard_limited'),
        'Point of Sale' => array('pos'),
        'Products' => array('products'),
        'Inventory' => array('inventory_view', 'inventory_manage'),
        'Orders' => array('orders_view', 'orders_manage'),
        'Customers' => array('clients'),
        'Debt / Credit' => array('debt_view', 'debt_sale', 'debt_payment', 'debt_payment_delete', 'debt_reports'),
        'Sales & Reports' => array('sales_report', 'profit_analytics'),
        'Expenses' => array('expenses'),
        'Catalog' => array('brands', 'categories'),
        'System' => array('backup_restore', 'clean_data', 'settings', 'permissions', 'users_manage', 'my_account', 'delete_actions'),
    );
}
function module_page_styles(){
    static $printed = false;
    if($printed) return '';
    $printed = true;
    return '<style>
    .mod-page { margin: -.25rem 0 0; }
    .mod-header {
        border: none; border-radius: 10px;
        background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
        color: #fff; box-shadow: 0 2px 10px rgba(37,99,235,.22);
        margin-bottom: 20px; overflow: hidden;
    }
    .mod-header.mod-header-expenses { background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); box-shadow: 0 2px 10px rgba(220,38,38,.2); }
    .mod-header.mod-header-analytics { background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%); box-shadow: 0 2px 10px rgba(22,163,74,.2); }
    .mod-header.mod-header-backup { background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%); box-shadow: 0 2px 10px rgba(8,145,178,.2); }
    .mod-header .card-body { padding: 1rem 1.25rem; }
    .mod-header h4 { font-weight: 700; margin: 0 0 .2rem; font-size: 1.15rem; }
    .mod-header .mod-subtitle { opacity: .9; font-size: .82rem; margin: 0; }
    .mod-btn-action {
        border: none; border-radius: 8px; font-weight: 600; font-size: .82rem;
        padding: .45rem 1rem; box-shadow: 0 2px 6px rgba(0,0,0,.12);
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .mod-btn-action:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.18); }
    .mod-btn-primary { background: #fff; color: #1e3a5f; }
    .mod-btn-success { background: #fff; color: #16a34a; }
    .mod-btn-export {
        border-radius: 8px; font-size: .78rem; font-weight: 600; padding: .35rem .75rem;
        border: 1px solid rgba(0,0,0,.1); background: #fff;
        transition: background .15s ease, transform .15s ease;
    }
    .mod-btn-export:hover { background: #f8f9fa; transform: translateY(-1px); }
    .mod-section {
        border: 1px solid rgba(0,0,0,.07); border-radius: 10px;
        box-shadow: 0 1px 4px rgba(0,0,0,.05); margin-bottom: 20px;
        overflow: hidden; background: #fff;
    }
    .mod-section-header {
        padding: 10px 18px; font-size: .72rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .05em; color: #fff;
        background: linear-gradient(135deg, #495057 0%, #6c757d 100%);
    }
    .mod-section-header.mod-sh-expenses { background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); }
    .mod-section-header.mod-sh-analytics { background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%); }
    .mod-section-header.mod-sh-backup { background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%); }
    .mod-section-header.mod-sh-filter { background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%); }
    .mod-section-header i { margin-right: .4rem; font-size: .75rem; }
    .mod-section-body { padding: .85rem; background: #fff; }
    .mod-mini-stat {
        background: #fff; border: 1px solid rgba(0,0,0,.06); border-radius: 8px;
        padding: .7rem .8rem; display: flex; align-items: center; min-height: 76px;
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
        transition: box-shadow .15s ease, transform .15s ease;
    }
    .mod-mini-stat:hover { box-shadow: 0 4px 12px rgba(0,0,0,.08); transform: translateY(-2px); }
    .mod-mini-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: .85rem; color: #fff; flex-shrink: 0; margin-right: .65rem;
    }
    .mod-mini-body { flex: 1; min-width: 0; }
    .mod-mini-label {
        font-size: .68rem; text-transform: uppercase; letter-spacing: .03em;
        color: #6c757d; line-height: 1.1; margin-bottom: .2rem;
    }
    .mod-mini-value { font-size: 1.02rem; font-weight: 700; color: #212529; line-height: 1.2; }
    .pl-amount-profit { color: #2d8a4e; }
    .pl-amount-loss { color: #c45c5c; }
    .pl-stat-loss { border-color: rgba(220, 38, 38, 0.22); background: #fff8f8; }
    .pl-stat-loss .mod-mini-label { color: #b91c1c; }
    .pl-stat-profit .pl-amount-profit { color: #15803d; }
    .mod-report-presets { gap: .35rem; }
    .mod-report-presets .mod-preset-btn { border-radius: 999px; font-size: .74rem; font-weight: 600; padding: .25rem .7rem; }
    .mod-report-presets .mod-preset-btn.active { background: #2563eb; border-color: #2563eb; color: #fff; }
    .mod-filter-card {
        background: #fafbfc; border: 1px solid rgba(0,0,0,.06);
        border-radius: 8px; padding: .85rem;
    }
    .mod-filter-card label { font-size: .72rem; text-transform: uppercase; letter-spacing: .03em; color: #6c757d; font-weight: 600; margin-bottom: .25rem; }
    .mod-action-btn {
        width: 30px; height: 30px; padding: 0; border-radius: 6px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .78rem; transition: transform .12s ease, box-shadow .12s ease;
    }
    .mod-action-btn:hover { transform: translateY(-1px); box-shadow: 0 2px 6px rgba(0,0,0,.12); }
    .mod-chart-panel {
        border: 1px solid rgba(0,0,0,.07); border-radius: 10px;
        box-shadow: 0 1px 4px rgba(0,0,0,.05); height: 100%;
        display: flex; flex-direction: column; overflow: hidden;
    }
    .mod-chart-panel .mod-chart-head {
        padding: .55rem .85rem; font-weight: 600; font-size: .85rem;
        border-bottom: 1px solid rgba(0,0,0,.06); background: #fff;
    }
    .mod-chart-panel .mod-chart-body { padding: .5rem .65rem .65rem; flex: 1; min-height: 200px; max-height: 220px; position: relative; }
    .mod-chart-panel canvas { max-height: 200px !important; }
    .mod-warning-card {
        border: 1px solid rgba(245,158,11,.35); border-radius: 10px;
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        padding: .85rem 1rem; margin-bottom: 20px;
        box-shadow: 0 1px 4px rgba(245,158,11,.15);
    }
    .mod-warning-card .mod-warning-title { font-weight: 700; color: #92400e; font-size: .9rem; margin-bottom: .25rem; }
    .mod-warning-card p { margin: 0; font-size: .82rem; color: #78350f; }
    </style>';
}
function module_export_toolbar($module, array $params = array(), $extra_class = 'ash-export-group'){
    if(!function_exists('app_export_allowed') || !app_export_allowed($module)){
        return '';
    }
    $xlsx = app_export_url($module, 'xlsx', $params);
    $pdf = app_export_url($module, 'pdf', $params);
    $print = app_export_url($module, 'print', $params);
    return '<div class="' . htmlspecialchars($extra_class) . '">'
        . '<a href="' . htmlspecialchars($print) . '" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener"><i class="fas fa-print"></i> Print</a>'
        . '<a href="' . htmlspecialchars($pdf) . '" class="btn btn-outline-danger btn-sm" target="_blank" rel="noopener"><i class="fas fa-file-pdf"></i> PDF</a>'
        . '<a href="' . htmlspecialchars($xlsx) . '" class="btn btn-outline-success btn-sm" target="_blank" rel="noopener"><i class="fas fa-file-excel"></i> Excel</a>'
        . '</div>';
}
function module_export_buttons_mod($module, array $params = array()){
    if(!function_exists('app_export_allowed') || !app_export_allowed($module)){
        return '';
    }
    $xlsx = app_export_url($module, 'xlsx', $params);
    $pdf = app_export_url($module, 'pdf', $params);
    $print = app_export_url($module, 'print', $params);
    return '<div class="btn-group btn-group-sm">'
        . '<a href="' . htmlspecialchars($print) . '" class="btn mod-btn-export" target="_blank" rel="noopener"><i class="fas fa-print text-secondary"></i> Print</a>'
        . '<a href="' . htmlspecialchars($pdf) . '" class="btn mod-btn-export" target="_blank" rel="noopener"><i class="fas fa-file-pdf text-danger"></i> PDF</a>'
        . '<a href="' . htmlspecialchars($xlsx) . '" class="btn mod-btn-export" target="_blank" rel="noopener"><i class="fas fa-file-excel text-success"></i> Excel</a>'
        . '</div>';
}
