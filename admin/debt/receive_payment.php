<?php
require_once __DIR__.'/../inc/module_ui.php';
require_once __DIR__.'/inc/helpers.php';
if(!debt_can_payment()){
    echo ash_swal_access_denied_script(base_url.'admin/?page=debt');
    return;
}
CustomerDebtService::ensure_schema($conn);
$clients = CustomerDebtService::registered_clients($conn);
$preselect = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
?>
<div class="card card-outline card-success">
    <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-money-bill-wave mr-1"></i> Receive Debt Payment</h3></div>
    <div class="card-body">
        <?php echo debt_subnav('receive_payment') ?>
        <form id="debt-payment-form">
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="debt-client">Customer <span class="text-danger">*</span></label>
                    <select id="debt-client" name="client_id" class="form-control" required>
                        <option value="">Select customer...</option>
                        <?php foreach($clients as $c): ?>
                        <option value="<?php echo (int)$c['id'] ?>" <?php echo $preselect === (int)$c['id'] ? 'selected' : '' ?>>
                            <?php echo htmlspecialchars($c['fullname'].' — '.$c['contact']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Outstanding Balance</label>
                    <div class="form-control-plaintext font-weight-bold text-danger" id="debt-outstanding">&mdash;</div>
                </div>
                <div class="form-group col-md-4">
                    <label for="debt-amount">Payment Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" class="form-control" id="debt-amount" name="amount" required>
                </div>
                <div class="form-group col-md-4">
                    <label for="debt-method">Payment Method</label>
                    <select id="debt-method" name="payment_method" class="form-control">
                        <option value="Cash">Cash</option>
                        <option value="M-Pesa">M-Pesa</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="debt-reference">Reference</label>
                    <input type="text" class="form-control" id="debt-reference" name="reference" placeholder="M-Pesa code, etc.">
                </div>
                <div class="form-group col-12">
                    <label for="debt-notes">Notes</label>
                    <textarea class="form-control" id="debt-notes" name="notes" rows="2"></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Record Payment</button>
        </form>
    </div>
</div>
<div id="debt-payment-receipt" style="display:none;">
    <div class="rc-header text-center"><div class="rc-store"><?php echo $_settings->info('name') ?></div><div class="rc-title">DEBT PAYMENT RECEIPT</div></div>
    <div class="rc-meta">
        <div class="rc-row"><span>Date:</span><span id="dpr-date"></span></div>
        <div class="rc-row"><span>Customer:</span><span id="dpr-customer"></span></div>
        <div class="rc-row"><span>Method:</span><span id="dpr-method"></span></div>
        <div class="rc-row"><span>Previous Balance:</span><span id="dpr-prev"></span></div>
        <div class="rc-row"><span>Amount Paid:</span><span id="dpr-paid"></span></div>
        <div class="rc-row"><span>Remaining Balance:</span><span id="dpr-remain"></span></div>
    </div>
</div>
<script>
function formatDebtPrice(n){
    n = parseFloat(n) || 0;
    return 'Ksh ' + n.toLocaleString('en-KE', {minimumFractionDigits:0, maximumFractionDigits:2});
}
function loadDebtBalance(clientId){
    if(!clientId){ $('#debt-outstanding').text('—'); return; }
    $.post(_base_url_+'classes/Master.php?f=debt_client_summary', {client_id: clientId}, function(resp){
        if(resp && resp.status === 'success' && resp.summary){
            $('#debt-outstanding').text(formatDebtPrice(resp.summary.outstanding));
            var bal = parseFloat(resp.summary.outstanding) || 0;
            if(bal > 0) $('#debt-amount').attr('max', bal);
        }
    }, 'json');
}
function printDebtPaymentReceipt(data){
    $('#dpr-date').text(data.date_created);
    $('#dpr-customer').text(data.customer_name);
    $('#dpr-method').text(data.payment_method);
    $('#dpr-prev').text(formatDebtPrice(data.previous_balance));
    $('#dpr-paid').text(formatDebtPrice(data.amount_paid));
    $('#dpr-remain').text(formatDebtPrice(data.remaining_balance));
    var rep = $('#debt-payment-receipt').clone().attr('id','dpr-clone').show();
    var nw = window.open('', '_blank', 'width=360,height=500');
    nw.document.write('<html><head><title>Payment Receipt</title><style>body{font-family:Courier New,monospace;font-size:12px;padding:10px}.rc-row{display:flex;justify-content:space-between;padding:3px 0}</style></head><body>'+rep.html()+'<script>window.onload=function(){window.print();setTimeout(function(){window.close();},2000);};<\/script></body></html>');
    nw.document.close();
}
$(function(){
    $('#debt-client').change(function(){ loadDebtBalance($(this).val()); });
    if($('#debt-client').val()) loadDebtBalance($('#debt-client').val());
    $('#debt-payment-form').submit(function(e){
        e.preventDefault();
        start_loader();
        $.ajax({
            url: _base_url_+'classes/Master.php?f=debt_receive_payment',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp){
                end_loader();
                if(!resp || resp.status !== 'success'){
                    alert_toast((resp && resp.msg) ? resp.msg : 'Payment failed', 'error');
                    return;
                }
                alert_toast('Payment recorded successfully', 'success');
                printDebtPaymentReceipt(resp);
                $('#debt-amount, #debt-reference, #debt-notes').val('');
                loadDebtBalance($('#debt-client').val());
            },
            error: function(){ end_loader(); alert_toast('Payment failed', 'error'); }
        });
    });
});
</script>
