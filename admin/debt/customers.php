<?php
require_once __DIR__.'/../inc/module_ui.php';
require_once __DIR__.'/inc/helpers.php';
CustomerDebtService::ensure_schema($conn);
$customers = CustomerDebtService::customers_with_debt($conn);
$export_params = array();
?>
<div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h3 class="card-title mb-0">Customer Debts</h3>
        <?php echo module_export_toolbar('debt', $export_params) ?>
    </div>
    <div class="card-body">
        <?php echo debt_subnav('customers') ?>
        <div class="ash-table-wrap">
        <table class="table table-hover ash-table" id="debt-customers-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th class="text-right">Outstanding</th>
                    <th>Last Purchase</th>
                    <th>Last Payment</th>
                    <th class="text-center">Status</th>
                    <th class="text-center ash-col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($customers as $row): ?>
                <tr>
                    <td class="font-weight-bold"><?php echo htmlspecialchars($row['fullname']) ?></td>
                    <td><?php echo htmlspecialchars($row['contact']) ?></td>
                    <td class="text-right font-weight-bold <?php echo $row['status_label'] === 'Overdue' ? 'text-danger' : '' ?>"><?php echo format_price($row['outstanding']) ?></td>
                    <td><?php echo $row['last_purchase'] ? date('Y-m-d', strtotime($row['last_purchase'])) : '&mdash;' ?></td>
                    <td><?php echo $row['last_payment'] ? date('Y-m-d', strtotime($row['last_payment'])) : '&mdash;' ?></td>
                    <td class="text-center"><?php echo debt_status_badge($row['status_label']) ?></td>
                    <td class="text-center">
                        <div class="ash-table-actions">
                            <a href="<?php echo base_url ?>admin/?page=debt/statement&client_id=<?php echo (int)$row['id'] ?>" class="btn btn-flat btn-sm btn-default" title="View Statement"><i class="fa fa-file-alt"></i></a>
                            <?php if(debt_can_payment()): ?>
                            <a href="<?php echo base_url ?>admin/?page=debt/receive_payment&client_id=<?php echo (int)$row['id'] ?>" class="btn btn-flat btn-sm btn-success" title="Receive Payment"><i class="fa fa-money-bill"></i></a>
                            <?php endif; ?>
                            <a href="<?php echo base_url ?>admin/?page=debt/statement&client_id=<?php echo (int)$row['id'] ?>&print=1" class="btn btn-flat btn-sm btn-info" target="_blank" title="Print Statement"><i class="fa fa-print"></i></a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($customers)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No customers with outstanding debt</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<script>
$(function(){
    if($('#debt-customers-table tbody tr').length > 1){
        $('#debt-customers-table').dataTable({ order:[[2,'desc']], columnDefs:[{orderable:false,targets:[6]}] });
    }
});
</script>
