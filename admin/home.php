<?php
require_once __DIR__.'/inc/module_ui.php';
$is_admin_dashboard = !admin_is_cashier();
$today = date('Y-m-d');
if($is_admin_dashboard) notifications_sync_system();

$total_stocks = 0;
$inv_row = $conn->query("SELECT SUM(quantity) AS total FROM inventory WHERE 1=1".tenant_sql())->fetch_assoc();
$sold_row = $conn->query("SELECT SUM(quantity) AS total FROM order_list WHERE order_id IN (SELECT order_id FROM sales WHERE 1=1".tenant_sql().")".tenant_sql())->fetch_assoc();
$total_stocks = (float)($inv_row['total'] ?? 0) - (float)($sold_row['total'] ?? 0);

$pending_orders = (int)$conn->query("SELECT COUNT(*) AS total FROM orders WHERE status = '0'".tenant_sql())->fetch_assoc()['total'];
$orders_today = dashboard_orders_today_count();
$inventory_value = dashboard_inventory_value();
$users_count = $is_admin_dashboard ? dashboard_users_count() : 0;

$sales_today = 0;
$registered_clients = 0;
$stock_alert_counts = array('low' => 0, 'out' => 0);
$profit_today = null;
$cash_today = 0;
$mpesa_today = 0;
$expenses_today = 0;
$expenses_month = 0;
$net_profit_today = null;
$unread_notifications = 0;
$last_backup_label = '&mdash;';
$dash_insight_charts = array('profit' => array(), 'expenses' => array());

if($is_admin_dashboard){
    $sales_today = (float)$conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM orders WHERE DATE(date_created) = '{$today}' AND status != 4".tenant_sql())->fetch_assoc()['total'];
    $sales_mtd = profit_analytics_sales_total(report_resolve_range('month')['start'], report_resolve_range('month')['end']);
    $sales_ytd = profit_analytics_sales_total(report_resolve_range('year')['start'], report_resolve_range('year')['end']);
    $sales_alltime = profit_analytics_sales_total(business_operating_bounds()['start'], business_operating_bounds()['end']);
    $registered_clients = (int)$conn->query("SELECT COUNT(*) AS total FROM clients WHERE delete_flag = 0".tenant_sql())->fetch_assoc()['total'];
    $stock_alert_counts = inventory_stock_counts();
    $profit_today = dashboard_profit_total($today, $today);
    $profit_mtd = dashboard_profit_total(report_resolve_range('month')['start'], report_resolve_range('month')['end']);
    $profit_ytd = dashboard_profit_total(report_resolve_range('year')['start'], report_resolve_range('year')['end']);
    $profit_alltime = dashboard_profit_total(business_operating_bounds()['start'], business_operating_bounds()['end']);
    $cash_today = dashboard_payment_sales_today('Cash');
    $mpesa_today = dashboard_payment_sales_today('M-Pesa');
    $debt_sales_today = dashboard_payment_sales_today('Debt');
    $debt_stats = debt_tables_ready() ? debt_dashboard_stats() : array('outstanding' => 0, 'payments_today' => 0, 'customers_owing' => 0, 'largest_debtor' => array('name' => '&mdash;', 'amount' => 0));
    $expenses_today = dashboard_expenses_today();
    $expenses_month = dashboard_expenses_month();
    $net_profit_today = dashboard_net_profit($today, $today);
    $net_profit_mtd = dashboard_net_profit(report_resolve_range('month')['start'], report_resolve_range('month')['end']);
    $net_profit_ytd = dashboard_net_profit(report_resolve_range('year')['start'], report_resolve_range('year')['end']);
    $net_profit_alltime = dashboard_net_profit(business_operating_bounds()['start'], business_operating_bounds()['end']);
    $unread_notifications = notifications_unread_count();
    $last_backup = backup_last_info();
    if($last_backup) $last_backup_label = date('M d, Y H:i', strtotime($last_backup['date_created']));
    $dash_insight_charts = profit_analytics_chart_series(date('Y-m-d', strtotime('-29 days')), $today, 'day');
}

$sales_trend = dashboard_chart_analytics('7d');
$show_chart_profit = admin_can_view_profit();
$top_products = dashboard_top_products(5);
$recent_sales = dashboard_recent_sale_lines(5);
$recent_activities = $is_admin_dashboard ? dashboard_recent_activities(10) : array();
$dash_notifications = $is_admin_dashboard ? notifications_list(8) : array();

$url_sales_today = dashboard_sales_report_url($today, $today);
$url_cash_today = dashboard_sales_report_url($today, $today, 'Cash');
$url_mpesa_today = dashboard_sales_report_url($today, $today, 'M-Pesa');
$url_inventory = base_url.'admin/?page=inventory';
$url_inventory_low = base_url.'admin/?page=inventory&stock_filter=low';
$url_inventory_out = base_url.'admin/?page=inventory&stock_filter=out';
$url_orders_pending = base_url.'admin/?page=orders&status=0';
$url_orders_today = dashboard_sales_report_url($today, $today);
$url_clients = base_url.'admin/?page=clients';
$url_users = base_url.'admin/?page=maintenance/permissions';
$url_sales_all = base_url.'admin/?page=sales';
$url_expenses = base_url.'admin/?page=expenses';
$url_sales_mtd = dashboard_sales_report_url(report_resolve_range('month')['start'], report_resolve_range('month')['end']);
$url_sales_ytd = dashboard_sales_report_url(report_resolve_range('year')['start'], report_resolve_range('year')['end']);
$url_sales_lifetime = dashboard_sales_report_url(business_operating_bounds()['start'], business_operating_bounds()['end']);
$url_analytics = base_url.'admin/?page=analytics';
$url_analytics_lifetime = base_url.'admin/?page=analytics&report_preset=lifetime';
$url_analytics_mtd = base_url.'admin/?page=analytics&report_preset=month';
$url_analytics_ytd = base_url.'admin/?page=analytics&report_preset=year';
$url_backup = base_url.'admin/?page=backup';
$url_debt = base_url.'admin/?page=debt';
$url_debt_customers = base_url.'admin/?page=debt/customers';
$url_debt_payment = base_url.'admin/?page=debt/receive_payment';

$welcome_summary = $is_admin_dashboard
    ? format_price($sales_today).' revenue today &middot; '.format_num($pending_orders).' open orders &middot; '.format_num($stock_alert_counts['low']).' low-stock items'
    : format_num($pending_orders).' open orders &middot; '.format_num($total_stocks).' units on hand';

function dash_mini_stat($label, $value, $icon, $icon_bg, $href = ''){
    $inner = '<div class="dash-mini-stat h-100'.($href !== '' ? ' dash-mini-clickable' : '').'">'
        .'<div class="dash-mini-icon '.$icon_bg.'"><i class="fas '.$icon.'"></i></div>'
        .'<div class="dash-mini-body"><div class="dash-mini-label">'.htmlspecialchars($label).'</div>'
        .'<div class="dash-mini-value">'.$value.'</div></div></div>';
    if($href !== ''){
        return '<a href="'.htmlspecialchars($href).'" class="dash-mini-link d-block h-100">'.$inner.'</a>';
    }
    return $inner;
}
?>
<style>
    .dash-page { margin: -0.25rem 0 0; }
    .dash-welcome {
        border: none;
        border-radius: .5rem;
        background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
        color: #fff;
        box-shadow: 0 2px 8px rgba(37, 99, 235, .25);
        margin-bottom: .75rem;
    }
    .dash-welcome .card-body { padding: .9rem 1.1rem; }
    .dash-welcome h5 { font-weight: 700; margin-bottom: .15rem; font-size: 1.05rem; }
    .dash-welcome .dash-date { opacity: .9; font-size: .82rem; }
    .dash-welcome .dash-summary { opacity: .92; font-size: .8rem; margin-top: .35rem; }
    .dash-section {
        border: 1px solid rgba(0,0,0,.07);
        border-radius: 10px;
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
        margin-bottom: 20px;
        overflow: hidden;
        background: #fff;
    }
    .dash-section-header {
        width: 100%;
        padding: 12px 20px;
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #fff;
        border: none;
        border-radius: 10px 10px 0 0;
        box-shadow: 0 2px 6px rgba(0,0,0,.12);
    }
    .dash-section-header.dash-section-financial {
        background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
    }
    .dash-section-header.dash-section-inventory {
        background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
    }
    .dash-section-header.dash-section-operations {
        background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%);
    }
    .dash-section-header.dash-section-insights {
        background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    }
    .dash-section-header i {
        font-size: .82rem;
        color: #fff;
        opacity: 1;
        margin-right: .5rem !important;
    }
    .dash-section-body { padding: .75rem; background: #fff; }
    .dash-section-body > .row { margin-left: -.25rem; margin-right: -.25rem; }
    .dash-section-body > .row > [class*="col"] { padding-left: .25rem; padding-right: .25rem; margin-bottom: .5rem; }
    .dash-mini-stat {
        background: #fff;
        border: 1px solid rgba(0,0,0,.06);
        border-radius: .4rem;
        padding: .65rem .7rem;
        display: flex;
        align-items: center;
        min-height: 72px;
        transition: box-shadow .15s ease, transform .15s ease, border-color .15s ease;
    }
    .dash-mini-clickable:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,.1);
        transform: translateY(-2px);
        border-color: rgba(0,0,0,.1);
    }
    .dash-mini-link { color: inherit; text-decoration: none; }
    .dash-mini-link:hover { color: inherit; text-decoration: none; }
    .dash-mini-icon {
        width: 30px; height: 30px; border-radius: .35rem;
        display: flex; align-items: center; justify-content: center;
        font-size: .82rem; color: #fff; flex-shrink: 0; margin-right: .55rem;
    }
    .dash-mini-label {
        font-size: .68rem; text-transform: uppercase; letter-spacing: .03em;
        color: #6c757d; line-height: 1.1; margin-bottom: .15rem;
    }
    .dash-mini-value { font-size: 1rem; font-weight: 700; color: #212529; line-height: 1.2; }
    .dash-panel {
        border: 1px solid rgba(0,0,0,.07);
        border-radius: .5rem;
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .dash-panel .card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,.06);
        padding: .6rem .85rem;
        font-weight: 600;
        font-size: .9rem;
    }
    .dash-panel .card-body { padding: .75rem .85rem; flex: 1 1 auto; }
    .dash-chart-row { margin-bottom: .75rem; }
    .dash-chart-col { flex: 0 0 100%; max-width: 100%; padding: 0 .35rem; margin-bottom: .5rem; }
    @media (min-width: 992px) {
        .dash-chart-col.chart-main { flex: 0 0 70%; max-width: 70%; margin-bottom: 0; }
        .dash-chart-col.chart-side { flex: 0 0 30%; max-width: 30%; margin-bottom: 0; }
    }
    .top-product-item {
        display: flex; align-items: flex-start;
        padding: .45rem 0; border-bottom: 1px solid #f1f3f5;
        font-size: .84rem;
    }
    .top-product-item:last-child { border-bottom: none; padding-bottom: 0; }
    .top-product-rank { width: 1.4rem; font-weight: 700; color: #6c757d; flex-shrink: 0; }
    .top-product-name { flex: 1; font-weight: 600; color: #343a40; padding-right: .4rem; word-break: break-word; }
    .top-product-meta { text-align: right; white-space: nowrap; font-size: .78rem; }
    .top-product-meta .qty { font-weight: 700; color: #495057; }
    .top-product-meta .rev { color: #6c757d; font-size: .72rem; }
    #salesTrendChart { max-height: 260px; }
    .dash-chart-filters .btn { font-size: .72rem; padding: .2rem .55rem; }
    .dash-chart-filters .btn.active {
        background-color: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }
    .dash-chart-range-label { font-size: .72rem; }
    .dash-recent-table th {
        font-size: .72rem; text-transform: uppercase; letter-spacing: .03em;
        color: #6c757d; border-top: none; white-space: nowrap; padding: .45rem .65rem;
    }
    .dash-recent-table td { vertical-align: middle; font-size: .82rem; padding: .45rem .65rem; }
    .activity-item {
        padding: .45rem 0; border-bottom: 1px solid #f1f3f5; font-size: .82rem;
    }
    .activity-item:last-child { border-bottom: none; padding-bottom: 0; }
    .activity-item .activity-meta { font-size: .72rem; color: #6c757d; }
    .activity-item .activity-action { font-weight: 600; color: #343a40; }
    .activity-item .activity-details { color: #495057; word-break: break-word; font-size: .78rem; }
    .dash-activity-table th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: #f8f9fa;
    }
    .dash-bottom-row > [class*="col"] { margin-bottom: .75rem; }
</style>

<div class="dash-page">
    <div class="card dash-welcome">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h5><?php echo dashboard_greeting_text() ?>, <?php echo htmlspecialchars(dashboard_user_display_name()) ?></h5>
                <div class="dash-date"><i class="far fa-calendar-alt mr-1"></i><?php echo date('l, F j, Y') ?></div>
                <div class="dash-summary"><?php echo $welcome_summary ?></div>
            </div>
            <div class="text-right d-none d-md-block">
                <div class="font-weight-bold" style="font-size:1.1rem;"><?php echo htmlspecialchars($_settings->info('short_name')) ?></div>
                <small style="opacity:.85;">Business Dashboard</small>
            </div>
        </div>
    </div>

    <?php if($is_admin_dashboard): ?>
    <div class="dash-section">
        <div class="dash-section-header dash-section-financial"><i class="fas fa-coins"></i> Financial Overview — Today</div>
        <div class="dash-section-body">
            <div class="row dash-stat-grid">
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Sales Today', format_price($sales_today), 'fa-shopping-cart', 'bg-success', $url_sales_today) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Gross Profit Today', dashboard_format_profit($profit_today), 'fa-chart-line', 'bg-teal', $url_sales_today) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Cash Revenue', format_price($cash_today), 'fa-money-bill-wave', 'bg-olive', $url_cash_today) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('M-Pesa Revenue', format_price($mpesa_today), 'fa-mobile-alt', 'bg-orange', $url_mpesa_today) ?></div>
                <?php if(debt_can_view() && debt_tables_ready()): ?>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Outstanding Debt', format_price($debt_stats['outstanding']), 'fa-hand-holding-usd', 'bg-danger', $url_debt_customers) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Debt Collected Today', format_price($debt_stats['payments_today']), 'fa-coins', 'bg-success', $url_debt_payment) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Credit Sales Today', format_price($debt_sales_today), 'fa-credit-card', 'bg-warning', dashboard_sales_report_url($today, $today, 'Debt')) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Customers Owing', format_num($debt_stats['customers_owing']), 'fa-user-clock', 'bg-secondary', $url_debt_customers) ?></div>
                <?php endif; ?>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Expenses Today', format_price($expenses_today), 'fa-file-invoice-dollar', 'bg-danger', $url_expenses) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Expenses (MTD)', format_price($expenses_month), 'fa-calendar-minus', 'bg-secondary', $url_expenses) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat(dashboard_net_profit_label($net_profit_today, 'Today'), dashboard_format_net_profit_display($net_profit_today), 'fa-hand-holding-usd', dashboard_is_net_loss($net_profit_today) ? 'bg-danger' : 'bg-teal', $url_analytics) ?></div>
            </div>
        </div>
    </div>
    <div class="dash-section">
        <div class="dash-section-header dash-section-financial"><i class="fas fa-chart-area"></i> Lifetime Performance</div>
        <div class="dash-section-body">
            <div class="row dash-stat-grid">
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Revenue (MTD)', format_price($sales_mtd), 'fa-calendar-alt', 'bg-primary', $url_sales_mtd) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Revenue (YTD)', format_price($sales_ytd), 'fa-chart-bar', 'bg-indigo', $url_sales_ytd) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Revenue (All Time)', format_price($sales_alltime), 'fa-history', 'bg-success', $url_sales_lifetime) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Gross Profit (MTD)', dashboard_format_profit($profit_mtd), 'fa-chart-line', 'bg-teal', $url_analytics_mtd) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Gross Profit (YTD)', dashboard_format_profit($profit_ytd), 'fa-chart-area', 'bg-purple', $url_analytics_ytd) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Gross Profit (All Time)', dashboard_format_profit($profit_alltime), 'fa-layer-group', 'bg-maroon', $url_analytics_lifetime) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat(dashboard_net_profit_label($net_profit_mtd, 'MTD'), dashboard_format_net_profit_display($net_profit_mtd), 'fa-balance-scale', dashboard_is_net_loss($net_profit_mtd) ? 'bg-danger' : 'bg-teal', $url_analytics_mtd) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat(dashboard_net_profit_label($net_profit_alltime, 'All Time'), dashboard_format_net_profit_display($net_profit_alltime), 'fa-landmark', dashboard_is_net_loss($net_profit_alltime) ? 'bg-danger' : 'bg-secondary', $url_analytics_lifetime) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="dash-section">
        <div class="dash-section-header dash-section-inventory"><i class="fas fa-boxes"></i> Inventory</div>
        <div class="dash-section-body">
            <div class="row dash-stat-grid">
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Stock on Hand', format_num($total_stocks), 'fa-cubes', 'bg-maroon', $url_inventory) ?></div>
                <?php if($is_admin_dashboard): ?>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Low Stock Items', format_num($stock_alert_counts['low']), 'fa-exclamation-triangle', 'bg-warning', $url_inventory_low) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Out of Stock', format_num($stock_alert_counts['out']), 'fa-times-circle', 'bg-danger', $url_inventory_out) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Stock Value (Retail)', format_price($inventory_value), 'fa-tags', 'bg-primary', $url_inventory) ?></div>
                <?php else: ?>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Stock Value (Retail)', format_price($inventory_value), 'fa-tags', 'bg-primary', $url_inventory) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col">
                    <a href="<?php echo base_url ?>admin/?page=pos" class="dash-mini-link d-block h-100">
                        <div class="dash-mini-stat h-100 dash-mini-clickable">
                            <div class="dash-mini-icon bg-primary"><i class="fas fa-cash-register"></i></div>
                            <div class="dash-mini-body"><div class="dash-mini-label">Quick Action</div><div class="dash-mini-value" style="font-size:.9rem;">Open Point of Sale</div></div>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="dash-section">
        <div class="dash-section-header dash-section-operations"><i class="fas fa-cogs"></i> Store Operations</div>
        <div class="dash-section-body">
            <div class="row dash-stat-grid">
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Open Orders', format_num($pending_orders), 'fa-clock', 'bg-purple', $url_orders_pending) ?></div>
                <?php if($is_admin_dashboard): ?>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Total Customers', format_num($registered_clients), 'fa-users', 'bg-pink', $url_clients) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Staff Accounts', format_num($users_count), 'fa-user-shield', 'bg-indigo', $url_users) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Transactions Today', format_num($orders_today), 'fa-receipt', 'bg-secondary', $url_orders_today) ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('New Alerts', format_num($unread_notifications), 'fa-bell', 'bg-warning', '') ?></div>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Last Backup', $last_backup_label, 'fa-database', 'bg-info', $url_backup) ?></div>
                <?php else: ?>
                <div class="col-6 col-lg-3 ash-stat-col"><?php echo dash_mini_stat('Transactions Today', format_num($orders_today), 'fa-receipt', 'bg-secondary', $url_orders_today) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if($is_admin_dashboard): ?>
    <div class="dash-section">
        <div class="dash-section-header dash-section-insights"><i class="fas fa-chart-pie"></i> Business Insights</div>
        <div class="dash-section-body">
            <div class="row dash-stat-grid dash-insight-grid">
                <div class="col-6 col-lg-3 ash-insight-col mb-2">
                    <div class="card dash-panel mb-0 h-100">
                        <div class="card-header py-2"><i class="fas fa-chart-line mr-1 text-success"></i> Gross Profit (30 Days)</div>
                        <div class="card-body"><canvas id="dashProfitMini" height="100"></canvas></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3 ash-insight-col mb-2">
                    <div class="card dash-panel mb-0 h-100">
                        <div class="card-header py-2"><i class="fas fa-chart-area mr-1 text-danger"></i> Operating Expenses (30 Days)</div>
                        <div class="card-body"><canvas id="dashExpensesMini" height="100"></canvas></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3 ash-insight-col mb-2">
                    <div class="card dash-panel mb-0 h-100">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-bell mr-1 text-warning"></i> Notifications</span>
                            <span class="badge badge-warning"><?php echo format_num($unread_notifications) ?></span>
                        </div>
                        <div class="card-body p-2" style="max-height:160px;overflow-y:auto;">
                            <?php if(count($dash_notifications) > 0): foreach($dash_notifications as $dn): ?>
                            <div class="small py-1 border-bottom">
                                <span class="badge badge-<?php echo htmlspecialchars($dn['type']) ?>"><?php echo htmlspecialchars(ucfirst($dn['type'])) ?></span>
                                <strong><?php echo htmlspecialchars(stripslashes($dn['title'])) ?></strong>
                                <div class="text-muted"><?php echo date('M d, H:i', strtotime($dn['date_created'])) ?></div>
                            </div>
                            <?php endforeach; else: ?>
                            <p class="text-muted small text-center mb-0 py-2">No notifications.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3 ash-insight-col mb-2">
                    <div class="card dash-panel mb-0 h-100">
                        <div class="card-header py-2"><i class="fas fa-database mr-1 text-info"></i> Backup Status</div>
                        <div class="card-body d-flex flex-column justify-content-center">
                            <p class="mb-2 small text-muted">Last successful database backup</p>
                            <p class="font-weight-bold mb-3"><?php echo $last_backup_label ?></p>
                            <a href="<?php echo $url_backup ?>" class="btn btn-sm btn-outline-primary btn-block">Manage Backups</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="d-flex flex-wrap dash-chart-row">
        <div class="dash-chart-col chart-main">
            <div class="card dash-panel mb-0">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <span><i class="fas fa-chart-bar mr-1 text-primary"></i> Revenue Trend</span>
                    <div class="d-flex flex-wrap align-items-center mt-1 mt-md-0">
                        <div class="btn-group btn-group-sm dash-chart-filters mr-2" role="group" aria-label="Chart range">
                            <button type="button" class="btn btn-outline-secondary active" data-range="7d">7 Days</button>
                            <button type="button" class="btn btn-outline-secondary" data-range="30d">30 Days</button>
                            <button type="button" class="btn btn-outline-secondary" data-range="month">This Month</button>
                            <button type="button" class="btn btn-outline-secondary" data-range="year">This Year</button>
                            <button type="button" class="btn btn-outline-secondary" data-range="lifetime">All Time</button>
                        </div>
                        <small class="text-muted font-weight-normal dash-chart-range-label"><?php echo htmlspecialchars($sales_trend['range_label']) ?></small>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="salesTrendChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="dash-chart-col chart-side">
            <div class="card dash-panel mb-0">
                <div class="card-header"><i class="fas fa-trophy mr-1 text-warning"></i> Top Products by Revenue</div>
                <div class="card-body">
                    <?php if(count($top_products) > 0): ?>
                    <?php $rank = 1; foreach($top_products as $product): ?>
                    <div class="top-product-item">
                        <div class="top-product-rank"><?php echo $rank++ ?>.</div>
                        <div class="top-product-name"><?php echo htmlspecialchars($product['product_name']) ?></div>
                        <div class="top-product-meta">
                            <div class="qty"><?php echo format_num($product['qty_sold']) ?> sold</div>
                            <div class="rev"><?php echo format_price($product['revenue']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <p class="text-muted mb-0 text-center py-2 small">No sales data yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row dash-bottom-row">
        <?php if($is_admin_dashboard): ?>
        <div class="col-lg-6">
            <div class="card dash-panel mb-0">
                <div class="card-header"><i class="fas fa-history mr-1 text-secondary"></i> Activity Log</div>
                <div class="card-body p-0" style="max-height:320px;overflow-y:auto;">
                    <?php if(count($recent_activities) > 0): ?>
                    <div class="ash-table-wrap">
                        <table class="table table-hover table-sm mb-0 ash-table dash-compact-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Activity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_activities as $activity): ?>
                                <?php
                                    $detail_tip = $activity['details'] !== '' ? $activity['details'] : '';
                                    $activity_label = $activity['action_label'].($detail_tip !== '' ? ' — '.$detail_tip : '');
                                ?>
                                <tr>
                                    <td class="text-nowrap"><?php echo date('H:i', strtotime($activity['date_created'])) ?></td>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($activity['username'] !== '' ? $activity['username'] : 'System') ?></td>
                                    <td>
                                        <span class="ash-text-truncate-2" style="max-width:100%;-webkit-line-clamp:1;" title="<?php echo htmlspecialchars($activity_label, ENT_QUOTES) ?>" data-toggle="tooltip"><?php echo htmlspecialchars($activity['action_label']) ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted mb-0 text-center py-3 small">No activity recorded yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="col-lg-<?php echo $is_admin_dashboard ? '6' : '12' ?>">
            <div class="card dash-panel mb-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-receipt mr-1 text-info"></i> Recent Transactions</span>
                    <?php if(admin_cashier_can('sales_report')): ?>
                    <a href="<?php echo $url_sales_all ?>" class="btn btn-sm btn-outline-primary py-0">View All</a>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="ash-table-wrap">
                        <table class="table table-hover table-sm mb-0 ash-table dash-compact-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Receipt</th>
                                    <th class="text-right">Amount</th>
                                    <th>Payment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($recent_sales) > 0): ?>
                                <?php foreach($recent_sales as $sale): ?>
                                <tr>
                                    <td class="text-nowrap"><?php echo date('H:i', strtotime($sale['date_created'])) ?></td>
                                    <td class="text-nowrap"><?php echo ash_table_id_badge($sale['ref_code']) ?></td>
                                    <td class="text-right"><?php echo ash_format_money_cell($sale['total'], 'revenue') ?></td>
                                    <td><?php echo htmlspecialchars($sale['payment']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3 small">No recent sales found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function(){
    $('[data-toggle="tooltip"]').tooltip();
    var ctx = document.getElementById('salesTrendChart');
    if(!ctx || typeof Chart === 'undefined') return;
    var showProfit = <?php echo $show_chart_profit ? 'true' : 'false' ?>;
    var chartUrl = _base_url_ + 'classes/Master.php?f=dashboard_chart_data';
    var trendChart = null;

    function buildDatasets(data){
        var datasets = [{
            type: 'bar',
            label: 'Revenue (Ksh)',
            data: data.sales || [],
            backgroundColor: 'rgba(37, 99, 235, 0.65)',
            borderColor: 'rgba(37, 99, 235, 1)',
            borderWidth: 1,
            maxBarThickness: 36,
            yAxisID: 'y-amount'
        }];
        if(showProfit){
            datasets.push({
                type: 'line',
                label: 'Gross Profit (Ksh)',
                data: data.profit || [],
                backgroundColor: 'rgba(16, 185, 129, 0.15)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 2,
                fill: false,
                pointRadius: 3,
                pointHoverRadius: 4,
                lineTension: 0.2,
                yAxisID: 'y-amount'
            });
        }
        datasets.push({
            type: 'line',
            label: 'Transactions',
            data: data.orders || [],
            backgroundColor: 'rgba(245, 158, 11, 0.15)',
            borderColor: 'rgba(245, 158, 11, 1)',
            borderWidth: 2,
            fill: false,
            pointRadius: 3,
            pointHoverRadius: 4,
            lineTension: 0.2,
            yAxisID: 'y-orders'
        });
        return datasets;
    }

    function chartOptions(){
        return {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                display: true,
                labels: { boxWidth: 12, fontSize: 11, padding: 12 }
            },
            tooltips: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(item, chartData){
                        var label = chartData.datasets[item.datasetIndex].label || '';
                        var value = Number(item.yLabel || 0);
                        if(label.indexOf('Transactions') !== -1) return ' ' + label + ': ' + value.toLocaleString('en-KE');
                        return ' ' + label + ': Ksh ' + value.toLocaleString('en-KE');
                    }
                }
            },
            scales: {
                yAxes: [{
                    id: 'y-amount',
                    position: 'left',
                    ticks: {
                        beginAtZero: true,
                        fontSize: 10,
                        callback: function(v){ return 'Ksh ' + Number(v).toLocaleString('en-KE'); }
                    },
                    gridLines: { color: 'rgba(0,0,0,.04)' }
                }, {
                    id: 'y-orders',
                    position: 'right',
                    ticks: {
                        beginAtZero: true,
                        fontSize: 10,
                        stepSize: 1,
                        callback: function(v){ return Number(v).toLocaleString('en-KE'); }
                    },
                    gridLines: { display: false }
                }],
                xAxes: [{
                    ticks: { fontSize: 10, maxRotation: 0, autoSkip: true, maxTicksLimit: 12 },
                    gridLines: { display: false }
                }]
            }
        };
    }

    function renderChart(data){
        var chartData = {
            labels: data.labels || [],
            datasets: buildDatasets(data)
        };
        if(trendChart){
            trendChart.data = chartData;
            trendChart.update();
            return;
        }
        trendChart = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: chartData,
            options: chartOptions()
        });
    }

    renderChart(<?php echo json_encode(array(
        'labels' => $sales_trend['labels'],
        'sales' => $sales_trend['sales'],
        'profit' => $sales_trend['profit'],
        'orders' => $sales_trend['orders'],
    )) ?>);

    $('.dash-chart-filters button').on('click', function(){
        var $btn = $(this);
        var range = $btn.data('range');
        if(!range || $btn.hasClass('active')) return;
        $('.dash-chart-filters button').removeClass('active');
        $btn.addClass('active');
        $.getJSON(chartUrl, { range: range })
            .done(function(resp){
                if(!resp || resp.status !== 'success' || !resp.data) return;
                renderChart(resp.data);
                if(resp.data.range_label){
                    $('.dash-chart-range-label').text(resp.data.range_label);
                }
            });
    });

    <?php if($is_admin_dashboard): ?>
    var insightData = <?php echo json_encode($dash_insight_charts) ?>;
    if(insightData.labels && typeof Chart !== 'undefined'){
        function insightTickCallback(value, index){
            return (index % 5 === 0 || index === insightData.labels.length - 1) ? value : '';
        }
        var miniOpts = {
            responsive: true,
            maintainAspectRatio: false,
            legend: { display: false },
            tooltips: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(item){
                        return ' Ksh ' + Number(item.yLabel || 0).toLocaleString('en-KE');
                    }
                }
            },
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        fontSize: 9,
                        maxTicksLimit: 5,
                        callback: function(v){ return 'Ksh ' + Number(v).toLocaleString('en-KE'); }
                    },
                    gridLines: { color: 'rgba(0,0,0,.04)' }
                }],
                xAxes: [{
                    ticks: {
                        fontSize: 8,
                        maxRotation: 0,
                        autoSkip: false,
                        callback: insightTickCallback
                    },
                    gridLines: { display: false }
                }]
            }
        };
        var pCtx = document.getElementById('dashProfitMini');
        if(pCtx) new Chart(pCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: insightData.labels,
                datasets: [{
                    data: insightData.net,
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22, 163, 74, 0.08)',
                    fill: true,
                    lineTension: 0.35,
                    pointRadius: 0,
                    pointHoverRadius: 3,
                    borderWidth: 2
                }]
            },
            options: miniOpts
        });
        var eCtx = document.getElementById('dashExpensesMini');
        if(eCtx) new Chart(eCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: insightData.labels,
                datasets: [{
                    data: insightData.expenses,
                    borderColor: 'rgba(220, 38, 38, 1)',
                    backgroundColor: 'rgba(220, 38, 38, 0.12)',
                    fill: true,
                    lineTension: 0.35,
                    pointRadius: 0,
                    pointHoverRadius: 3,
                    borderWidth: 2
                }]
            },
            options: miniOpts
        });
    }
    <?php endif; ?>
});
</script>
