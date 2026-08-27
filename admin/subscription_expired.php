<?php
require_once __DIR__ . '/inc/module_ui.php';
$sub = tenant_subscription_status();
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-outline card-warning">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-exclamation-triangle mr-2"></i>Subscription Required</h3></div>
            <div class="card-body text-center py-5">
                <h4 class="mb-3"><?php echo htmlspecialchars($sub['message'] ?? 'Your subscription is not active.'); ?></h4>
                <p class="text-muted mb-4">Your business data is preserved. Normal POS operations are blocked until your subscription is renewed.</p>
                <p class="mb-4">Use the subscription page to renew securely with M-PESA, or contact <strong>Kalmoy Tech Solutions</strong>.</p>
                <a href="<?php echo base_url ?>admin/?page=subscription" class="btn btn-success mr-2"><i class="fas fa-credit-card"></i> Renew Subscription</a>
                <a href="<?php echo base_url ?>classes/Login.php?f=logout" class="btn btn-secondary mr-2"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</div>
