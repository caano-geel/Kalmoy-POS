<?php
require_once __DIR__.'/../inc/module_ui.php';
require_once __DIR__.'/inc/helpers.php';
CustomerDebtService::ensure_schema($conn);
$client_id = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
if($client_id <= 0){
    echo '<div class="alert alert-warning">Select a customer to view statement.</div>';
    return;
}
$cq = $conn->query("SELECT *, CONCAT(firstname,' ',lastname) AS fullname FROM clients WHERE id = '{$client_id}' AND delete_flag = 0 LIMIT 1");
if(!$cq || !$cq->num_rows){
    echo '<div class="alert alert-danger">Customer not found.</div>';
    return;
}
$client = $cq->fetch_assoc();
$summary = CustomerDebtService::client_summary($conn, $client_id);
$statement = CustomerDebtService::client_statement($conn, $client_id);
$export_params = array('type' => 'statement', 'client_id' => $client_id);
$auto_print = isset($_GET['print']) && $_GET['print'] == '1';
?>
<div class="card card-outline card-primary <?php echo $auto_print ? 'd-none' : '' ?>">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h3 class="card-title mb-0">Statement — <?php echo htmlspecialchars($client['fullname']) ?></h3>
        <?php echo module_export_toolbar('debt_statement', $export_params) ?>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3"><strong>Outstanding:</strong> <span class="text-danger font-weight-bold"><?php echo format_price($summary['outstanding']) ?></span></div>
            <div class="col-md-3"><strong>Total Credit:</strong> <?php echo format_price($summary['credit_given']) ?></div>
            <div class="col-md-3"><strong>Total Paid:</strong> <?php echo format_price($summary['total_paid']) ?></div>
            <div class="col-md-3"><strong>Status:</strong> <?php echo debt_status_badge($summary['credit_status']) ?></div>
        </div>
        <div id="debt-statement-print">
            <div class="text-center mb-2 d-none d-print-block">
                <h4><?php echo $_settings->info('name') ?></h4>
                <h5>Customer Debt Statement</h5>
                <p><?php echo htmlspecialchars($client['fullname']) ?> — <?php echo htmlspecialchars($client['contact']) ?></p>
            </div>
            <div class="ash-table-wrap">
            <table class="table table-bordered table-sm ash-table" id="debt-statement-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($statement as $row): ?>
                    <tr>
                        <td class="text-nowrap"><?php echo date('Y-m-d H:i', strtotime($row['date'])) ?></td>
                        <td><?php echo htmlspecialchars($row['description']) ?></td>
                        <td class="text-right"><?php echo $row['debit'] > 0 ? format_price($row['debit']) : '—' ?></td>
                        <td class="text-right text-success"><?php echo $row['credit'] > 0 ? format_price($row['credit']) : '—' ?></td>
                        <td class="text-right font-weight-bold"><?php echo format_price($row['balance']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if(empty($statement)): ?>
                    <tr><td colspan="5" class="text-center text-muted">No transactions</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
        <div class="mt-2 no-print">
            <a href="<?php echo base_url ?>admin/?page=debt/customers" class="btn btn-sm btn-secondary">&larr; Back</a>
            <?php if(debt_can_payment()): ?>
            <a href="<?php echo base_url ?>admin/?page=debt/receive_payment&client_id=<?php echo $client_id ?>" class="btn btn-sm btn-success">Receive Payment</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php if($auto_print): ?>
<div id="debt-statement-print-only">
    <div class="text-center mb-2"><h4><?php echo $_settings->info('name') ?></h4><h5>Customer Debt Statement</h5>
    <p><?php echo htmlspecialchars($client['fullname']) ?> — <?php echo htmlspecialchars($client['contact']) ?></p></div>
    <table class="table table-bordered table-sm" style="width:100%;font-size:12px;">
        <thead><tr><th>Date</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead>
        <tbody>
        <?php foreach($statement as $row): ?>
        <tr>
            <td><?php echo date('Y-m-d', strtotime($row['date'])) ?></td>
            <td><?php echo htmlspecialchars($row['description']) ?></td>
            <td><?php echo $row['debit'] > 0 ? format_price($row['debit']) : '' ?></td>
            <td><?php echo $row['credit'] > 0 ? format_price($row['credit']) : '' ?></td>
            <td><?php echo format_price($row['balance']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>window.onload=function(){window.print();};</script>
<?php endif; ?>
