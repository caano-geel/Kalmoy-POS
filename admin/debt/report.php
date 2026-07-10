<?php
require_once __DIR__.'/../inc/module_ui.php';
require_once __DIR__.'/inc/helpers.php';
if(!debt_can_reports()){
    echo ash_swal_access_denied_script(base_url.'admin/?page=debt');
    return;
}
CustomerDebtService::ensure_schema($conn);
$report_range = report_resolve_range(isset($_GET['report_preset']) ? $_GET['report_preset'] : 'month');
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] : $report_range['start'];
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] : $report_range['end'];
$date_start = $conn->real_escape_string($date_start);
$date_end = $conn->real_escape_string($date_end);

$credit_sales = (float)$conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM customer_debts WHERE DATE(date_created) BETWEEN '{$date_start}' AND '{$date_end}'")->fetch_assoc()['t'];
$collections = (float)$conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM debt_payments WHERE DATE(date_created) BETWEEN '{$date_start}' AND '{$date_end}'")->fetch_assoc()['t'];
$outstanding = (float)$conn->query("SELECT COALESCE(SUM(balance),0) AS t FROM customer_debts WHERE balance > 0")->fetch_assoc()['t'];
$customers_owing = (int)$conn->query("SELECT COUNT(DISTINCT client_id) AS t FROM customer_debts WHERE balance > 0")->fetch_assoc()['t'];

$daily_credit = array();
$q1 = $conn->query("SELECT DATE(date_created) AS d, SUM(amount) AS total FROM customer_debts
    WHERE DATE(date_created) BETWEEN '{$date_start}' AND '{$date_end}' GROUP BY DATE(date_created) ORDER BY d DESC");
while($q1 && ($r = $q1->fetch_assoc())) $daily_credit[] = $r;

$daily_collections = array();
$q2 = $conn->query("SELECT DATE(date_created) AS d, SUM(amount) AS total FROM debt_payments
    WHERE DATE(date_created) BETWEEN '{$date_start}' AND '{$date_end}' GROUP BY DATE(date_created) ORDER BY d DESC");
while($q2 && ($r = $q2->fetch_assoc())) $daily_collections[] = $r;

$export_params = array('type' => 'report', 'date_start' => $date_start, 'date_end' => $date_end);
?>
<div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h3 class="card-title mb-0">Debt Report</h3>
        <?php echo module_export_buttons_mod('debt', $export_params) ?>
    </div>
    <div class="card-body">
        <?php echo debt_subnav('report') ?>
        <form class="ash-filter-compact mb-3 no-print">
            <input type="hidden" name="page" value="debt/report">
            <div class="row align-items-end">
                <div class="form-group col-6 col-md-2 mb-1">
                    <label>From</label>
                    <input type="date" name="date_start" class="form-control form-control-sm" value="<?php echo htmlspecialchars($date_start) ?>">
                </div>
                <div class="form-group col-6 col-md-2 mb-1">
                    <label>To</label>
                    <input type="date" name="date_end" class="form-control form-control-sm" value="<?php echo htmlspecialchars($date_end) ?>">
                </div>
                <div class="form-group col-6 col-md-2 mb-1">
                    <button class="btn btn-sm btn-primary">Apply</button>
                </div>
            </div>
        </form>
        <div class="row ash-kpi-strip mb-3">
            <div class="col-6 col-md-3 ash-kpi-item"><div class="ash-kpi-label">Credit Sales (Period)</div><div class="ash-kpi-value"><?php echo format_price($credit_sales) ?></div></div>
            <div class="col-6 col-md-3 ash-kpi-item"><div class="ash-kpi-label">Collections (Period)</div><div class="ash-kpi-value text-success"><?php echo format_price($collections) ?></div></div>
            <div class="col-6 col-md-3 ash-kpi-item"><div class="ash-kpi-label">Outstanding Now</div><div class="ash-kpi-value text-danger"><?php echo format_price($outstanding) ?></div></div>
            <div class="col-6 col-md-3 ash-kpi-item"><div class="ash-kpi-label">Customers Owing</div><div class="ash-kpi-value"><?php echo format_num($customers_owing) ?></div></div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <h5>Daily Credit Sales</h5>
                <table class="table table-sm ash-table"><thead><tr><th>Date</th><th class="text-right">Amount</th></tr></thead><tbody>
                <?php foreach($daily_credit as $r): ?><tr><td><?php echo $r['d'] ?></td><td class="text-right"><?php echo format_price($r['total']) ?></td></tr><?php endforeach; ?>
                <?php if(empty($daily_credit)): ?><tr><td colspan="2" class="text-muted text-center">No credit sales in period</td></tr><?php endif; ?>
                </tbody></table>
            </div>
            <div class="col-md-6 mb-3">
                <h5>Daily Collections</h5>
                <table class="table table-sm ash-table"><thead><tr><th>Date</th><th class="text-right">Amount</th></tr></thead><tbody>
                <?php foreach($daily_collections as $r): ?><tr><td><?php echo $r['d'] ?></td><td class="text-right"><?php echo format_price($r['total']) ?></td></tr><?php endforeach; ?>
                <?php if(empty($daily_collections)): ?><tr><td colspan="2" class="text-muted text-center">No collections in period</td></tr><?php endif; ?>
                </tbody></table>
            </div>
        </div>
    </div>
</div>
