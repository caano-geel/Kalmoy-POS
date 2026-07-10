<?php
require_once __DIR__.'/../inc/module_ui.php';
require_once __DIR__.'/inc/helpers.php';
CustomerDebtService::ensure_schema($conn);
$payments = array();
$q = $conn->query("SELECT dp.*, CONCAT(c.firstname,' ',c.lastname) AS fullname, c.contact
    FROM debt_payments dp
    INNER JOIN clients c ON c.id = dp.client_id
    ORDER BY dp.date_created DESC");
while($q && ($r = $q->fetch_assoc())) $payments[] = $r;
$can_delete = !admin_is_cashier() || admin_cashier_has_permission('debt_payment_delete');
?>
<div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h3 class="card-title mb-0">Debt Payment History</h3>
        <?php echo module_export_toolbar('debt', array('type' => 'payments')) ?>
    </div>
    <div class="card-body">
        <?php echo debt_subnav('history') ?>
        <div class="ash-table-wrap">
        <table class="table table-hover ash-table" id="debt-history-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th class="text-right">Amount</th>
                    <th>Received By</th>
                    <?php if($can_delete): ?><th class="text-center">Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach($payments as $row): ?>
                <tr>
                    <td class="text-nowrap"><?php echo date('Y-m-d H:i', strtotime($row['date_created'])) ?></td>
                    <td><a href="<?php echo base_url ?>admin/?page=debt/statement&client_id=<?php echo (int)$row['client_id'] ?>"><?php echo htmlspecialchars($row['fullname']) ?></a></td>
                    <td><?php echo htmlspecialchars($row['contact']) ?></td>
                    <td><?php echo htmlspecialchars($row['payment_method']) ?></td>
                    <td><?php echo htmlspecialchars($row['reference'] ?: '—') ?></td>
                    <td class="text-right text-success font-weight-bold"><?php echo format_price($row['amount']) ?></td>
                    <td><?php echo htmlspecialchars($row['created_by_name'] ?: '—') ?></td>
                    <?php if($can_delete): ?>
                    <td class="text-center">
                        <button type="button" class="btn btn-flat btn-sm btn-danger delete-debt-payment" data-id="<?php echo (int)$row['id'] ?>"><i class="fa fa-trash"></i></button>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($payments)): ?>
                <tr><td colspan="<?php echo $can_delete ? 8 : 7 ?>" class="text-center text-muted py-4">No payments recorded</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php if($can_delete): ?>
<script>
$(function(){
    $('.delete-debt-payment').click(function(){
        var id = $(this).data('id');
        _conf('Delete this payment? Outstanding balances will be restored.','deleteDebtPayment',[id]);
    });
    window.deleteDebtPayment = function(id){
        $('#confirm_modal').modal('hide');
        start_loader();
        $.post(_base_url_+'classes/Master.php?f=debt_delete_payment', {id:id}, function(resp){
            end_loader();
            if(resp && resp.status === 'success'){
                alert_toast('Payment deleted','success');
                location.reload();
            } else {
                alert_toast((resp && resp.msg) ? resp.msg : 'Delete failed','error');
            }
        }, 'json');
    };
});
</script>
<?php endif; ?>
