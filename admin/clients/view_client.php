<?php
require_once __DIR__.'/../inc/module_ui.php';
require_once __DIR__.'/../debt/inc/helpers.php';
$client_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($client_id <= 0){
    echo '<script>location.replace("'.base_url.'admin/?page=clients");</script>';
    return;
}
$cq = $conn->query("SELECT *, CONCAT(firstname,' ',lastname) AS fullname FROM clients WHERE id = '{$client_id}' AND delete_flag = 0 LIMIT 1");
if(!$cq || !$cq->num_rows){
    echo '<div class="alert alert-danger">Customer not found.</div>';
    return;
}
$client = $cq->fetch_assoc();
if(strtolower(trim($client['email'])) === CustomerDebtService::WALKIN_EMAIL){
    echo '<script>$(function(){if(window.ashDebtProfileNotFound){ashDebtProfileNotFound('.json_encode(base_url.'admin/?page=clients').','.json_encode(base_url.'admin/?page=clients').');}});</script>';
    return;
}
CustomerDebtService::ensure_schema($conn);
$summary = CustomerDebtService::client_summary($conn, $client_id);
$statement = CustomerDebtService::client_statement($conn, $client_id);
$payments = array();
$q = $conn->query("SELECT * FROM debt_payments WHERE client_id = '{$client_id}' ORDER BY date_created DESC LIMIT 20");
while($q && ($r = $q->fetch_assoc())) $payments[] = $r;
$credits = array();
$q2 = $conn->query("SELECT cd.*, o.ref_code FROM customer_debts cd LEFT JOIN orders o ON o.id = cd.order_id WHERE cd.client_id = '{$client_id}' ORDER BY cd.date_created DESC LIMIT 20");
while($q2 && ($r = $q2->fetch_assoc())) $credits[] = $r;
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';
?>
<div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h3 class="card-title mb-0"><?php echo htmlspecialchars($client['fullname']) ?></h3>
        <div>
            <a href="<?php echo base_url ?>admin/?page=clients" class="btn btn-sm btn-secondary">Back to Customers</a>
            <a href="<?php echo base_url ?>admin/?page=debt/statement&client_id=<?php echo $client_id ?>" class="btn btn-sm btn-info"><i class="fa fa-file-alt"></i> Full Statement</a>
        </div>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item"><a class="nav-link <?php echo $tab === 'overview' ? 'active' : '' ?>" href="?page=clients/view_client&id=<?php echo $client_id ?>&tab=overview">Profile</a></li>
            <li class="nav-item"><a class="nav-link <?php echo $tab === 'debt' ? 'active' : '' ?>" href="?page=clients/view_client&id=<?php echo $client_id ?>&tab=debt">Debt</a></li>
        </ul>
        <?php if($tab === 'debt'): ?>
        <div class="row mb-3">
            <div class="col-md-3"><div class="border rounded p-2"><div class="small text-muted">Outstanding</div><div class="h5 text-danger mb-0"><?php echo format_price($summary['outstanding']) ?></div></div></div>
            <div class="col-md-3"><div class="border rounded p-2"><div class="small text-muted">Total Credit</div><div class="h5 mb-0"><?php echo format_price($summary['credit_given']) ?></div></div></div>
            <div class="col-md-3"><div class="border rounded p-2"><div class="small text-muted">Total Paid</div><div class="h5 text-success mb-0"><?php echo format_price($summary['total_paid']) ?></div></div></div>
            <div class="col-md-3"><div class="border rounded p-2"><div class="small text-muted">Status</div><div class="h5 mb-0"><?php echo debt_status_badge($summary['credit_status']) ?></div></div></div>
        </div>
        <?php if(debt_can_payment() && $summary['outstanding'] > 0): ?>
        <a href="<?php echo base_url ?>admin/?page=debt/receive_payment&client_id=<?php echo $client_id ?>" class="btn btn-success btn-sm mb-3"><i class="fa fa-money-bill"></i> Receive Payment</a>
        <?php endif; ?>
        <div class="row">
            <div class="col-md-6 mb-3">
                <h5>Payment History</h5>
                <table class="table table-sm ash-table"><thead><tr><th>Date</th><th>Method</th><th class="text-right">Amount</th></tr></thead><tbody>
                <?php foreach($payments as $p): ?><tr><td><?php echo date('Y-m-d', strtotime($p['date_created'])) ?></td><td><?php echo htmlspecialchars($p['payment_method']) ?></td><td class="text-right text-success"><?php echo format_price($p['amount']) ?></td></tr><?php endforeach; ?>
                <?php if(empty($payments)): ?><tr><td colspan="3" class="text-muted text-center">No payments</td></tr><?php endif; ?>
                </tbody></table>
            </div>
            <div class="col-md-6 mb-3">
                <h5>Credit History</h5>
                <table class="table table-sm ash-table"><thead><tr><th>Date</th><th>Receipt</th><th class="text-right">Amount</th><th class="text-right">Balance</th></tr></thead><tbody>
                <?php foreach($credits as $c): ?><tr><td><?php echo date('Y-m-d', strtotime($c['date_created'])) ?></td><td><?php echo htmlspecialchars($c['ref_code'] ?: '—') ?></td><td class="text-right"><?php echo format_price($c['amount']) ?></td><td class="text-right"><?php echo format_price($c['balance']) ?></td></tr><?php endforeach; ?>
                <?php if(empty($credits)): ?><tr><td colspan="4" class="text-muted text-center">No credit sales</td></tr><?php endif; ?>
                </tbody></table>
            </div>
        </div>
        <?php else: ?>
        <dl class="row mb-0">
            <dt class="col-sm-3">Email</dt><dd class="col-sm-9"><?php echo ash_email_cell($client['email']) ?></dd>
            <dt class="col-sm-3">Phone</dt><dd class="col-sm-9"><?php echo htmlspecialchars($client['contact']) ?></dd>
            <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><?php echo $client['status'] == 1 ? ash_status_badge('Active','active') : ash_status_badge('Inactive','inactive') ?></dd>
            <dt class="col-sm-3">Debt Status</dt><dd class="col-sm-9"><?php echo debt_status_badge($summary['credit_status']) ?> — <?php echo format_price($summary['outstanding']) ?> outstanding</dd>
            <dt class="col-sm-3">Registered</dt><dd class="col-sm-9"><?php echo date('Y-m-d', strtotime($client['date_created'])) ?></dd>
        </dl>
        <?php endif; ?>
    </div>
</div>
