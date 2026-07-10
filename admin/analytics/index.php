<?php
require_once __DIR__.'/../inc/module_ui.php';
if(!admin_can_view_profit()){
    echo '<div class="alert alert-danger">Access denied. Profit &amp; Loss is available to administrators only.</div>';
    return;
}
$report_preset = isset($_GET['report_preset']) ? $_GET['report_preset'] : (isset($_GET['period']) ? $_GET['period'] : 'month');
$report_range = report_resolve_range(
    $report_preset,
    isset($_GET['date_start']) ? $_GET['date_start'] : '',
    isset($_GET['date_end']) ? $_GET['date_end'] : ''
);
$date_start = $report_range['start'];
$date_end = $report_range['end'];
$analytics_export_pdf = app_export_report_filename('analytics', 'pdf', $date_start, $date_end);
$profit_export_xlsx = app_export_report_filename('profit', 'xlsx', $date_start, $date_end);
$report_data = profit_analytics_report_rows($date_start, $date_end);
$rows = $report_data['rows'];
$report_mode = $report_data['mode'];
$chart_daily = profit_analytics_chart_series($date_start, $date_end, ($report_mode === 'month' ? 'month' : 'day'));
$chart_monthly = profit_analytics_chart_series($date_start, $date_end, 'month');
$lifetime_summary = business_lifetime_summary();
$year_comparison = business_year_comparison_rows();
$summary_today = dashboard_net_profit(date('Y-m-d'), date('Y-m-d'));
$summary_week = dashboard_net_profit(report_resolve_range('week')['start'], report_resolve_range('week')['end']);
$summary_month = dashboard_net_profit(report_resolve_range('month')['start'], report_resolve_range('month')['end']);
$summary_year = dashboard_net_profit(report_resolve_range('year')['start'], report_resolve_range('year')['end']);
$summary_lifetime = $lifetime_summary['net_profit'];
$totals = array('sales' => 0, 'cost' => 0, 'expenses' => 0, 'profit' => 0, 'net' => 0);
foreach($rows as $r){
    $totals['sales'] += $r['sales'];
    if($r['cost'] !== null) $totals['cost'] += (float)$r['cost'];
    $totals['expenses'] += $r['expenses'];
    if($r['profit'] !== null) $totals['profit'] += (float)$r['profit'];
    if($r['net_profit'] !== null) $totals['net'] += (float)$r['net_profit'];
}
echo module_page_styles();
?>
<?php if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif; ?>
<div class="mod-page" id="profit-analytics-printable">
    <div class="card mod-header mod-header-analytics">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
            <div class="mb-2 mb-md-0">
                <h4><i class="fas fa-chart-line mr-2"></i>Profit &amp; Loss</h4>
                <p class="mod-subtitle">Revenue, gross profit, operating expenses, and net profit analysis</p>
            </div>
            <?php echo module_export_buttons_mod('profit_loss', array(
                'report_preset' => $report_preset,
                'date_start' => $date_start,
                'date_end' => $date_end,
            )); ?>
        </div>
    </div>

    <div class="mod-section">
        <div class="mod-section-header mod-sh-analytics"><i class="fas fa-coins"></i> P&amp;L Summary</div>
        <div class="mod-section-body">
            <p class="small text-muted mb-2 pl-formula-note">
                <i class="fas fa-info-circle text-primary mr-1" data-toggle="tooltip" title="<?php echo htmlspecialchars(dashboard_pl_formula_tooltip()) ?>"></i>
                <?php echo htmlspecialchars(dashboard_pl_formula_tooltip()) ?>. Negative results are labelled <strong class="text-danger">Net Loss</strong>.
            </p>
            <div class="row">
                <?php
                echo module_mini_stat_pl($summary_today, 'fa-sun', 'bg-success', 'Today');
                echo module_mini_stat_pl($summary_week, 'fa-calendar-week', 'bg-teal', 'Week');
                echo module_mini_stat_pl($summary_month, 'fa-calendar-alt', 'bg-primary', 'MTD');
                echo module_mini_stat_pl($summary_year, 'fa-chart-area', 'bg-indigo', 'YTD');
                echo module_mini_stat_pl($summary_lifetime, 'fa-history', 'bg-secondary', 'All Time');
                ?>
            </div>
        </div>
    </div>

    <div class="mod-section">
        <div class="mod-section-header mod-sh-analytics"><i class="fas fa-history"></i> Business Lifetime Summary</div>
        <div class="mod-section-body">
            <p class="small text-muted mb-2">Since <?php echo date('M j, Y', strtotime($lifetime_summary['start'])) ?> through <?php echo date('M j, Y', strtotime($lifetime_summary['end'])) ?>.</p>
            <?php echo module_lifetime_summary_cards($lifetime_summary); ?>
        </div>
    </div>

    <div class="mod-section">
        <div class="mod-section-header mod-sh-filter"><i class="fas fa-filter"></i> Date Range</div>
        <div class="mod-section-body">
            <?php echo module_report_date_filter('analytics', $report_range); ?>
        </div>
    </div>

    <?php if(count($year_comparison) > 1): ?>
    <div class="mod-section">
        <div class="mod-section-header mod-sh-analytics"><i class="fas fa-balance-scale"></i> Year-over-Year Comparison</div>
        <div class="mod-section-body p-0">
            <div class="ash-table-wrap mod-table-wrap">
                <table class="table table-hover ash-table mod-table mb-0">
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th class="text-right ash-col-money">Revenue</th>
                            <th class="text-right ash-col-money">COGS</th>
                            <th class="text-right ash-col-money">Gross Profit</th>
                            <th class="text-right ash-col-money">Expenses</th>
                            <th class="text-right ash-col-money">Net Profit</th>
                            <th class="text-right">Orders</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($year_comparison as $yr): ?>
                        <tr>
                            <td class="font-weight-bold"><?php echo (int)$yr['year'] ?></td>
                            <td class="text-right"><?php echo ash_format_money_cell($yr['revenue'], 'revenue') ?></td>
                            <td class="text-right"><?php echo $yr['cogs'] === null ? '&mdash;' : ash_format_money_cell($yr['cogs'], 'expense') ?></td>
                            <td class="text-right"><?php echo $yr['gross_profit'] === null ? '&mdash;' : ash_format_money_cell($yr['gross_profit'], 'auto') ?></td>
                            <td class="text-right"><?php echo ash_format_money_cell($yr['operating_expenses'], 'expense') ?></td>
                            <td class="text-right"><?php echo $yr['net_profit'] === null ? '&mdash;' : dashboard_format_net_profit_display($yr['net_profit']) ?></td>
                            <td class="text-right"><?php echo format_num($yr['orders']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="mod-section">
        <div class="mod-section-header mod-sh-analytics"><i class="fas fa-chart-bar"></i> Performance Charts</div>
        <div class="mod-section-body">
            <div class="row">
                <div class="col-12 col-lg-6 mb-3 mb-lg-0">
                    <div class="mod-chart-panel">
                        <div class="mod-chart-head"><i class="fas fa-chart-line text-success mr-1"></i> Daily Net Profit Trend</div>
                        <div class="mod-chart-body ash-chart-wrap"><canvas id="paDailyChart"></canvas></div>
                    </div>
                </div>
                <div class="col-12 col-lg-6 mb-3 mb-lg-0">
                    <div class="mod-chart-panel">
                        <div class="mod-chart-head"><i class="fas fa-chart-line text-primary mr-1"></i> Monthly Net Profit Trend</div>
                        <div class="mod-chart-body ash-chart-wrap"><canvas id="paMonthlyChart"></canvas></div>
                    </div>
                </div>
                <div class="col-12 col-lg-6 mt-lg-3">
                    <div class="mod-chart-panel">
                        <div class="mod-chart-head"><i class="fas fa-balance-scale text-warning mr-1"></i> Gross Profit vs Expenses</div>
                        <div class="mod-chart-body ash-chart-wrap"><canvas id="paVsExpChart"></canvas></div>
                    </div>
                </div>
                <div class="col-12 col-lg-6 mt-lg-3">
                    <div class="mod-chart-panel">
                        <div class="mod-chart-head"><i class="fas fa-chart-area text-info mr-1"></i> Revenue vs Gross Profit</div>
                        <div class="mod-chart-body ash-chart-wrap"><canvas id="paSalesChart"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mod-section">
        <div class="mod-section-header mod-sh-analytics"><i class="fas fa-table"></i> <?php echo $report_mode === 'month' ? 'Monthly' : 'Daily'; ?> P&amp;L Statement</div>
        <div class="mod-section-body p-0">
            <div class="ash-table-wrap mod-table-wrap">
                <table class="table table-hover ash-table ash-table-pl mod-table" id="profit-analytics-table">
                    <thead>
                        <tr>
                            <th><?php echo $report_mode === 'month' ? 'Month' : 'Date'; ?></th>
                            <th class="text-right ash-col-money">Revenue</th>
                            <th class="text-right ash-col-money">Cost of Goods</th>
                            <th class="text-right ash-col-money">Operating Expenses</th>
                            <th class="text-right ash-col-money">Gross Profit</th>
                            <th class="text-right ash-col-money">Net Profit / Loss</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($rows) > 0): foreach($rows as $r): ?>
                        <tr>
                            <td class="text-nowrap"><?php echo $report_mode === 'month' ? htmlspecialchars($r['date']) : date('Y-m-d', strtotime($r['date'])) ?></td>
                            <td class="text-right"><?php echo ash_format_money_cell($r['sales'], 'revenue') ?></td>
                            <td class="text-right"><?php echo $r['cost'] === null ? '&mdash;' : ash_format_money_cell($r['cost'], 'expense') ?></td>
                            <td class="text-right"><?php echo ash_format_money_cell($r['expenses'], 'expense') ?></td>
                            <td class="text-right"><?php echo $r['profit'] === null ? '&mdash;' : ash_format_money_cell($r['profit'], 'auto') ?></td>
                            <td class="text-right"><?php echo $r['net_profit'] === null ? '&mdash;' : dashboard_format_net_profit_display($r['net_profit']) ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr class="ash-table-empty"><td colspan="6">No data for the selected period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if(count($rows) > 0): ?>
                    <tfoot>
                        <tr class="ash-pl-summary-row">
                            <th>Period Totals</th>
                            <th class="text-right"><?php echo ash_format_money_cell($totals['sales'], 'revenue') ?></th>
                            <th class="text-right"><?php echo ash_format_money_cell($totals['cost'], 'expense') ?></th>
                            <th class="text-right"><?php echo ash_format_money_cell($totals['expenses'], 'expense') ?></th>
                            <th class="text-right"><?php echo ash_format_money_cell($totals['profit'], 'auto') ?></th>
                            <th class="text-right"><?php echo dashboard_format_net_profit_display($totals['net']) ?></th>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
            <?php if(count($rows) > 0): ?>
            <div class="ash-kpi-strip mx-0 border-top border-left-0 border-right-0 rounded-0" style="box-shadow:none;">
                <div class="ash-kpi-item"><div class="ash-kpi-label">Total Revenue</div><div class="ash-kpi-value"><?php echo ash_format_money_cell($totals['sales'], 'revenue') ?></div></div>
                <div class="ash-kpi-item"><div class="ash-kpi-label">Total Expenses</div><div class="ash-kpi-value"><?php echo ash_format_money_cell($totals['expenses'], 'expense') ?></div></div>
                <div class="ash-kpi-item"><div class="ash-kpi-label">Gross Profit</div><div class="ash-kpi-value"><?php echo ash_format_money_cell($totals['profit'], 'auto') ?></div></div>
                <div class="ash-kpi-item"><div class="ash-kpi-label">Net Profit</div><div class="ash-kpi-value"><?php echo dashboard_format_net_profit_display($totals['net']) ?></div></div>
            </div>
            <?php endif; ?>
            <p class="small text-muted mb-0 px-3 py-2 border-top">
                <i class="fas fa-info-circle mr-1"></i>
                Gross Profit = Revenue &minus; Cost of Goods Sold &middot;
                <span title="<?php echo htmlspecialchars(dashboard_pl_formula_tooltip()) ?>"><?php echo htmlspecialchars(dashboard_pl_formula_tooltip()) ?></span>
            </p>
        </div>
    </div>
</div>
<script>
$(function(){
    if(typeof Chart === 'undefined') return;
    $('[data-toggle="tooltip"]').tooltip();
    var daily = <?php echo json_encode($chart_daily) ?>;
    var monthly = <?php echo json_encode($chart_monthly) ?>;
    var chartOpts = {
        responsive: true,
        maintainAspectRatio: false,
        legend: { labels: { boxWidth: 10, fontSize: 10, padding: 8 } },
        tooltips: { mode: 'index', intersect: false },
        scales: {
            yAxes: [{ ticks: { beginAtZero: true, fontSize: 9, maxTicksLimit: 6, callback: function(v){ return 'Ksh '+Number(v).toLocaleString('en-KE'); } }, gridLines: { color: 'rgba(0,0,0,.04)' } }],
            xAxes: [{ ticks: { fontSize: 9, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 }, gridLines: { display: false } }]
        }
    };
    function lineChart(id, labels, datasets){
        new Chart(document.getElementById(id).getContext('2d'), {
            type: 'line',
            data: { labels: labels, datasets: datasets },
            options: chartOpts
        });
    }
    lineChart('paDailyChart', daily.labels, [
        {label:'Net Profit', data:daily.net, borderColor:'#16a34a', borderWidth:2, fill:false, lineTension:.3, pointRadius:2, pointHoverRadius:3}
    ]);
    lineChart('paMonthlyChart', monthly.labels, [
        {label:'Net Profit', data:monthly.net, borderColor:'#2563eb', borderWidth:2, fill:false, lineTension:.3, pointRadius:2, pointHoverRadius:3}
    ]);
    lineChart('paVsExpChart', daily.labels, [
        {label:'Gross Profit', data:daily.profit, borderColor:'#16a34a', borderWidth:2, fill:false, lineTension:.3, pointRadius:2},
        {label:'Operating Expenses', data:daily.expenses, borderColor:'#dc2626', borderWidth:2, fill:false, lineTension:.3, pointRadius:2}
    ]);
    lineChart('paSalesChart', daily.labels, [
        {label:'Revenue', data:daily.sales, borderColor:'#0891b2', borderWidth:2, fill:false, lineTension:.3, pointRadius:2},
        {label:'Gross Profit', data:daily.profit, borderColor:'#7c3aed', borderWidth:2, fill:false, lineTension:.3, pointRadius:2}
    ]);
});
</script>
