<?php
/** @var mysqli $conn */
$plans = array();
$q = $conn->query('SELECT id, name, price_monthly, trial_days FROM subscription_plans WHERE status = 1 ORDER BY sort_order, id');
if ($q) {
    while ($r = $q->fetch_assoc()) {
        $plans[] = $r;
    }
}
$csrf = tenant_csrf_token();
?>
<h4 class="mb-3">Create Business (Onboarding)</h4>
<?php if (empty($plans)): ?>
<div class="alert alert-danger">No active subscription plans found. Run <code>database/install_saas.php</code> or seed subscription plans first.</div>
<?php endif; ?>
<div class="card">
    <div class="card-body">
        <div id="create-business-msg"></div>
        <form id="create-business-frm">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf) ?>">
            <h5>Business Profile</h5>
            <div class="row">
                <div class="col-md-6 form-group"><label>Business Name *</label><input class="form-control" name="name" id="biz-name" required></div>
                <div class="col-md-6 form-group"><label>Slug *</label><input class="form-control" name="slug" id="biz-slug" placeholder="kalmoy-supermarket" required><small class="text-muted">Unique URL key (lowercase, hyphens). Must not already exist.</small></div>
                <div class="col-md-4 form-group"><label>Phone</label><input class="form-control" name="phone"></div>
                <div class="col-md-4 form-group"><label>Email</label><input class="form-control" name="email" type="email"></div>
                <div class="col-md-4 form-group"><label>Address</label><input class="form-control" name="address"></div>
            </div>
            <hr>
            <h5>Subscription</h5>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Plan</label>
                    <select class="form-control" name="plan_id" <?php echo empty($plans) ? 'disabled' : '' ?>>
                        <?php foreach ($plans as $p): ?>
                            <option value="<?php echo (int)$p['id'] ?>"><?php echo htmlspecialchars($p['name']) ?> — Ksh <?php echo number_format($p['price_monthly']) ?>/mo</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label>Start As</label>
                    <select class="form-control" name="sub_mode">
                        <option value="trial">Trial</option>
                        <option value="active">Active (paid)</option>
                    </select>
                </div>
            </div>
            <hr>
            <h5>Owner Account</h5>
            <div class="row">
                <div class="col-md-3 form-group"><label>First Name</label><input class="form-control" name="owner_firstname"></div>
                <div class="col-md-3 form-group"><label>Last Name</label><input class="form-control" name="owner_lastname"></div>
                <div class="col-md-3 form-group"><label>Username *</label><input class="form-control" name="owner_username" required><small class="text-muted">Must be unique across all businesses.</small></div>
                <div class="col-md-3 form-group"><label>Email</label><input class="form-control" name="owner_email" type="email"></div>
                <div class="col-md-4 form-group"><label>Password *</label><input class="form-control" name="owner_password" type="password" required minlength="6"></div>
            </div>
            <button type="submit" class="btn btn-primary" id="create-business-btn" <?php echo empty($plans) ? 'disabled' : '' ?>>Create Business & Owner</button>
        </form>
    </div>
</div>
<?php
$platformBusinessesUrl = PLATFORM_BASE . '?page=businesses';
$pageScripts = <<<HTML
<script>
$(function(){
    $('#biz-name').on('input', function(){
        var \$slug = $('#biz-slug');
        if (\$slug.data('edited')) return;
        var s = $(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+\$/g, '');
        \$slug.val(s);
    });
    $('#biz-slug').on('input', function(){ $(this).data('edited', 1); });

    $('#create-business-frm').submit(function(e){
        e.preventDefault();
        var \$btn = $('#create-business-btn');
        var \$msg = $('#create-business-msg');
        \$msg.empty();
        \$btn.prop('disabled', true).text('Creating...');
        postPlatform('create_business', $(this).serialize(), function(resp){
            if(resp.status === 'success'){
                \$msg.html('<div class="alert alert-success">' + (resp.msg || 'Business created.') + '</div>');
                setTimeout(function(){
                    location.href = '{$platformBusinessesUrl}';
                }, 800);
            } else {
                \$msg.html('<div class="alert alert-danger">' + (resp.msg || 'Failed to create business.') + '</div>');
                \$btn.prop('disabled', false).text('Create Business & Owner');
            }
        });
        setTimeout(function(){
            if (\$btn.prop('disabled')) {
                \$btn.prop('disabled', false).text('Create Business & Owner');
            }
        }, 15000);
    });
});
</script>
HTML;
?>
