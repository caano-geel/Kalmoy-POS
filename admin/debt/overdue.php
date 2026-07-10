<?php
require_once __DIR__.'/../inc/module_ui.php';
require_once __DIR__.'/inc/helpers.php';
CustomerDebtService::ensure_schema($conn);
CustomerDebtService::refresh_debt_statuses($conn);
$rows = array();
$email = $conn->real_escape_string(CustomerDebtService::WALKIN_EMAIL);
$sql = "SELECT c.id, CONCAT(c.firstname,' ',c.lastname) AS fullname, c.contact,
    cd.amount, cd.balance, cd.due_date, cd.status, cd.date_created, o.ref_code
    FROM customer_debts cd
    INNER JOIN clients c ON c.id = cd.client_id
    LEFT JOIN orders o ON o.id = cd.order_id
    WHERE cd.balance > 0 AND c.email != '{$email}'
    ORDER BY cd.due_date IS NULL, cd.due_date ASC, cd.date_created ASC";
$q = $conn->query($sql);
while($q && ($r = $q->fetch_assoc())){
    $label = 'Current';
    if($r['status'] === 'overdue') $label = 'Overdue';
    elseif($r['status'] === 'due_today') $label = 'Due Today';
    $r['status_label'] = $label;
    $rows[] = $r;
}
?>
<div class="card card-outline card-danger">
    <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-exclamation-triangle mr-1"></i> Overdue &amp; Due Debts</h3></div>
    <div class="card-body">
        <?php echo debt_subnav('overdue') ?>
        <div class="ash-table-wrap">
        <table class="table table-hover ash-table" id="debt-overdue-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Receipt</th>
                    <th class="text-right">Balance</th>
                    <th>Due Date</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($rows as $row): ?>
                <tr class="<?php echo $row['status_label'] === 'Overdue' ? 'table-danger' : ($row['status_label'] === 'Due Today' ? 'table-warning' : '') ?>">
                    <td class="font-weight-bold"><?php echo htmlspecialchars($row['fullname']) ?></td>
                    <td><?php echo htmlspecialchars($row['contact']) ?></td>
                    <td><?php echo htmlspecialchars($row['ref_code'] ?: '—') ?></td>
                    <td class="text-right font-weight-bold <?php echo $row['status_label'] === 'Overdue' ? 'text-danger' : '' ?>"><?php echo format_price($row['balance']) ?></td>
                    <td><?php echo $row['due_date'] ? date('Y-m-d', strtotime($row['due_date'])) : '—' ?></td>
                    <td class="text-center"><?php echo debt_status_badge($row['status_label']) ?></td>
                    <td class="text-center">
                        <?php if(debt_can_payment()): ?>
                        <a href="<?php echo base_url ?>admin/?page=debt/receive_payment&client_id=<?php echo (int)$row['id'] ?>" class="btn btn-sm btn-success"><i class="fa fa-money-bill"></i></a>
                        <?php endif; ?>
                        <a href="<?php echo base_url ?>admin/?page=debt/statement&client_id=<?php echo (int)$row['id'] ?>" class="btn btn-sm btn-default"><i class="fa fa-file-alt"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($rows)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No outstanding debts with due dates</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
