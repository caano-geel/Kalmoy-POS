<?php
require_once __DIR__ . '/inc/module_ui.php';
if (!admin_is_owner()) {
    echo ash_swal_access_denied_script(base_url . 'admin/');
    return;
}
$bid = tenant_id();
$current = SubscriptionService::effective($conn, $bid);
$plans = array();
$q = $conn->query('SELECT id, name, description, price_monthly, price_yearly, trial_days FROM subscription_plans WHERE status = 1 ORDER BY sort_order, id');
while ($q && ($row = $q->fetch_assoc())) $plans[] = $row;
$payments = array();
$stmt = $conn->prepare('SELECT amount, currency, billing_cycle, status, mpesa_receipt, payment_date FROM subscription_payments WHERE business_id = ? ORDER BY id DESC LIMIT 25');
$stmt->bind_param('i', $bid);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $payments[] = $row;
$csrf = tenant_csrf_token();
echo module_page_styles();
?>
<div class="mod-page">
    <div class="card mod-header"><div class="card-body"><h4><i class="fas fa-credit-card mr-2"></i> Subscription & Billing</h4><p class="mod-subtitle">Manage your Kalmoy POS access and M-PESA subscription payments.</p></div></div>
    <div class="mod-section"><div class="mod-section-body">
        <h5>Current subscription</h5>
        <?php if (!empty($current['subscription'])): $sub = $current['subscription']; ?>
            <p class="mb-1"><strong><?php echo htmlspecialchars($sub['plan_name'] ?? 'Plan') ?></strong> · <?php echo htmlspecialchars($current['status']) ?></p>
            <p class="text-muted">Ends: <?php echo htmlspecialchars($sub['current_period_end'] ?? $sub['trial_ends_at'] ?? 'Not set') ?></p>
        <?php else: ?><p class="text-muted">No subscription found.</p><?php endif; ?>
    </div></div>
    <div class="row">
    <?php foreach ($plans as $plan): ?>
        <div class="col-md-6 col-lg-4"><div class="mod-section h-100"><div class="mod-section-body">
            <h5><?php echo htmlspecialchars($plan['name']) ?></h5><p class="text-muted small"><?php echo htmlspecialchars($plan['description'] ?? '') ?></p>
            <label>Billing cycle</label><select class="form-control billing-cycle mb-2"><option value="monthly">Monthly: KES <?php echo number_format($plan['price_monthly'], 2) ?></option><option value="yearly">Annual: KES <?php echo number_format($plan['price_yearly'], 2) ?></option></select>
            <input class="form-control billing-phone mb-2" placeholder="M-PESA number (07... or 254...)" inputmode="tel">
            <button type="button" class="btn btn-success btn-block start-payment" data-plan="<?php echo (int)$plan['id'] ?>"><i class="fas fa-mobile-alt mr-1"></i> Pay with M-PESA</button>
        </div></div></div>
    <?php endforeach; ?>
    </div>
    <div class="mod-section"><div class="mod-section-body"><h5>Payment history</h5><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Amount</th><th>Cycle</th><th>Status</th><th>Receipt</th></tr></thead><tbody>
    <?php foreach ($payments as $payment): ?><tr><td><?php echo htmlspecialchars($payment['payment_date']) ?></td><td>KES <?php echo number_format($payment['amount'], 2) ?></td><td><?php echo htmlspecialchars($payment['billing_cycle']) ?></td><td><?php echo htmlspecialchars($payment['status']) ?></td><td><?php echo htmlspecialchars($payment['mpesa_receipt'] ?? '') ?></td></tr><?php endforeach; ?>
    <?php if (!$payments): ?><tr><td colspan="5" class="text-muted">No payments recorded.</td></tr><?php endif; ?></tbody></table></div></div></div>
</div>
<script>
$(function(){
    $('.start-payment').on('click', function(){
        var button = $(this), card = button.closest('.mod-section');
        var phone = card.find('.billing-phone').val(), cycle = card.find('.billing-cycle').val();
        button.prop('disabled', true).text('Sending STK Push...');
        $.post('<?php echo base_url ?>classes/Billing.php?f=start_mpesa', {csrf:'<?php echo htmlspecialchars($csrf, ENT_QUOTES) ?>', plan_id:button.data('plan'), billing_cycle:cycle, phone:phone}, function(response){
            if (!response || response.status !== 'success') { alert_toast((response && (response.msg || response.message)) || 'Payment could not be started.', 'error'); button.prop('disabled', false).html('<i class="fas fa-mobile-alt mr-1"></i> Pay with M-PESA'); return; }
            button.text('Waiting for confirmation...');
            var attempts = 0, timer = setInterval(function(){
                $.getJSON('<?php echo base_url ?>classes/Billing.php?f=status&id='+encodeURIComponent(response.payment_id), function(result){
                    var payment = result && result.payment;
                    if (!payment || payment.status === 'pending') return;
                    clearInterval(timer); button.prop('disabled', false);
                    if (payment.status === 'paid') { alert_toast('Payment successful. Your subscription is active.', 'success'); location.reload(); }
                    else { alert_toast('Payment was not completed.', 'error'); button.html('<i class="fas fa-mobile-alt mr-1"></i> Try again'); }
                });
                attempts++; if (attempts >= 30) { clearInterval(timer); button.prop('disabled', false).html('<i class="fas fa-mobile-alt mr-1"></i> Check history or retry'); alert_toast('We could not confirm the payment yet.', 'warning'); }
            }, 5000);
        }, 'json').fail(function(){ button.prop('disabled', false).html('<i class="fas fa-mobile-alt mr-1"></i> Pay with M-PESA'); alert_toast('Payment service is unavailable.', 'error'); });
    });
});
</script>