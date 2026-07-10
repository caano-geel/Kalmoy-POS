<?php
require_once __DIR__.'/../inc/module_ui.php';
require_once __DIR__.'/inc/helpers.php';
CustomerDebtService::ensure_schema($conn);
$stats = debt_dashboard_stats();
$customers = CustomerDebtService::customers_with_debt($conn);
$recent_payments = array();
$q = $conn->query("SELECT dp.*, CONCAT(c.firstname,' ',c.lastname) AS fullname
    FROM debt_payments dp
    INNER JOIN clients c ON c.id = dp.client_id
    ORDER BY dp.date_created DESC LIMIT 8");
while($q && ($r = $q->fetch_assoc())) $recent_payments[] = $r;
?>
<div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h3 class="card-title mb-0"><i class="fas fa-hand-holding-usd mr-1"></i> Debt Management</h3>
        <?php if(debt_can_payment()): ?>
        <a href="<?php echo base_url ?>admin/?page=debt/receive_payment" class="btn btn-sm btn-success"><i class="fa fa-money-bill"></i> Receive Payment</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php echo debt_subnav('debt') ?>
        <?php echo debt_stat_cards($stats) ?>
        <div class="row">
            <div class="col-lg-7 mb-3">
                <h5 class="mb-2">Top Outstanding Balances</h5>
                <div class="ash-table-wrap">
                <table class="table table-sm table-hover ash-table mb-0">
                    <thead><tr><th>Customer</th><th>Phone</th><th class="text-right">Balance</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if(empty($customers)): ?>
                    <tr><td colspan="4" class="text-center text-muted">No outstanding debts</td></tr>
                    <?php else: foreach(array_slice($customers, 0, 8) as $row): ?>
                    <tr>
                        <td><a href="<?php echo base_url ?>admin/?page=debt/statement&client_id=<?php echo (int)$row['id'] ?>"><?php echo htmlspecialchars($row['fullname']) ?></a></td>
                        <td><?php echo htmlspecialchars($row['contact']) ?></td>
                        <td class="text-right font-weight-bold <?php echo $row['status_label'] === 'Overdue' ? 'text-danger' : '' ?>"><?php echo format_price($row['outstanding']) ?></td>
                        <td><?php echo debt_status_badge($row['status_label']) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
                </div>
                <a href="<?php echo base_url ?>admin/?page=debt/customers" class="btn btn-sm btn-link px-0">View all customer debts &rarr;</a>
            </div>
            <div class="col-lg-5 mb-3">
                <h5 class="mb-2">Recent Payments</h5>
                <div class="ash-table-wrap">
                <table class="table table-sm table-hover ash-table mb-0">
                    <thead><tr><th>Date</th><th>Customer</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                    <?php if(empty($recent_payments)): ?>
                    <tr><td colspan="3" class="text-center text-muted">No payments yet</td></tr>
                    <?php else: foreach($recent_payments as $row): ?>
                    <tr>
                        <td class="text-nowrap"><?php echo date('M d, H:i', strtotime($row['date_created'])) ?></td>
                        <td><?php echo htmlspecialchars($row['fullname']) ?></td>
                        <td class="text-right text-success font-weight-bold"><?php echo format_price($row['amount']) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
                </div>
                <a href="<?php echo base_url ?>admin/?page=debt/history" class="btn btn-sm btn-link px-0">View payment history &rarr;</a>
            </div>
        </div>
    </div>
</div>
