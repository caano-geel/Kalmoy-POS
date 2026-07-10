<?php
if(!function_exists('debt_status_badge')){
    function debt_status_badge($label){
        $map = array(
            'Clear' => 'success',
            'Owing' => 'warning',
            'Overdue' => 'danger',
            'Due Today' => 'info',
            'Current' => 'secondary',
        );
        $variant = isset($map[$label]) ? $map[$label] : 'secondary';
        return ash_status_badge($label, $variant);
    }
}
function debt_stat_cards(array $stats){
    $url_customers = base_url.'admin/?page=debt/customers';
    $url_history = base_url.'admin/?page=debt/history';
    $largest = isset($stats['largest_debtor']) ? $stats['largest_debtor'] : array('name' => '&mdash;', 'amount' => 0);
    $largest_link = !empty($largest['client_id']) ? base_url.'admin/?page=debt/statement&client_id='.(int)$largest['client_id'] : $url_customers;
    ob_start();
    ?>
    <div class="row ash-kpi-strip mb-3">
        <div class="col-6 col-md-4 col-lg ash-kpi-item">
            <div class="ash-kpi-label">Total Outstanding</div>
            <div class="ash-kpi-value text-danger"><?php echo format_price($stats['outstanding']) ?></div>
        </div>
        <div class="col-6 col-md-4 col-lg ash-kpi-item">
            <div class="ash-kpi-label">Credit Sales Today</div>
            <div class="ash-kpi-value"><?php echo format_price($stats['credit_today']) ?></div>
        </div>
        <div class="col-6 col-md-4 col-lg ash-kpi-item">
            <div class="ash-kpi-label">Payments Today</div>
            <div class="ash-kpi-value text-success"><?php echo format_price($stats['payments_today']) ?></div>
        </div>
        <div class="col-6 col-md-4 col-lg ash-kpi-item">
            <div class="ash-kpi-label">Customers Owing</div>
            <div class="ash-kpi-value"><a href="<?php echo $url_customers ?>"><?php echo format_num($stats['customers_owing']) ?></a></div>
        </div>
        <div class="col-6 col-md-4 col-lg ash-kpi-item">
            <div class="ash-kpi-label">Largest Debtor</div>
            <div class="ash-kpi-value" style="font-size:.95rem;">
                <a href="<?php echo htmlspecialchars($largest_link) ?>"><?php echo htmlspecialchars($largest['name']) ?></a>
                <div class="small text-muted"><?php echo format_price($largest['amount']) ?></div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
function debt_subnav($active){
    $items = array(
        'debt' => array('Debt Dashboard', 'debt'),
        'customers' => array('Customer Debts', 'debt/customers'),
        'receive_payment' => array('Receive Payment', 'debt/receive_payment', 'debt_payment'),
        'history' => array('Debt History', 'debt/history'),
        'overdue' => array('Overdue Debts', 'debt/overdue'),
        'report' => array('Debt Report', 'debt/report', 'debt_reports'),
    );
    $html = '<div class="btn-group btn-group-sm flex-wrap mb-3 debt-subnav">';
    foreach($items as $key => $item){
        $perm = isset($item[2]) ? $item[2] : 'debt_view';
        if(admin_is_cashier() && !admin_cashier_has_permission($perm)) continue;
        $cls = ($active === $key) ? 'btn-primary' : 'btn-outline-secondary';
        $html .= '<a href="'.base_url.'admin/?page='.$item[1].'" class="btn '.$cls.'">'.htmlspecialchars($item[0]).'</a>';
    }
    $html .= '</div>';
    return $html;
}
