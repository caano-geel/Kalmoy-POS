<?php
/** @var mysqli $conn */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo '<div class="alert alert-danger">Invalid business.</div>';
    return;
}
$b = $conn->query("SELECT b.*, u.username AS owner_username, u.email AS owner_email, u.firstname, u.lastname
    FROM businesses b LEFT JOIN users u ON u.id = b.owner_user_id WHERE b.id = {$id} LIMIT 1")->fetch_assoc();
if (!$b) {
    echo '<div class="alert alert-danger">Business not found.</div>';
    return;
}
$sub = $conn->query("SELECT s.*, sp.name AS plan_name FROM subscriptions s LEFT JOIN subscription_plans sp ON sp.id = s.plan_id WHERE s.business_id = {$id} ORDER BY s.id DESC LIMIT 1")->fetch_assoc();
$plans = array();
$q = $conn->query('SELECT id, name FROM subscription_plans WHERE status = 1 ORDER BY sort_order, id');
if ($q) {
    while ($r = $q->fetch_assoc()) {
        $plans[] = $r;
    }
}
$csrf = tenant_csrf_token();
?>
<h4 class="mb-3">Manage: <?php echo htmlspecialchars($b['name']) ?></h4>
<div class="row">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header">Business Information</div>
            <div class="card-body">
                <form id="update-business-frm">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="id" value="<?php echo $id ?>">
                    <div class="form-group"><label>Name</label><input class="form-control" name="name" value="<?php echo htmlspecialchars($b['name']) ?>"></div>
                    <div class="form-group"><label>Phone</label><input class="form-control" name="phone" value="<?php echo htmlspecialchars($b['phone'] ?? '') ?>"></div>
                    <div class="form-group"><label>Email</label><input class="form-control" name="email" value="<?php echo htmlspecialchars($b['email'] ?? '') ?>"></div>
                    <div class="form-group"><label>Address</label><textarea class="form-control" name="address"><?php echo htmlspecialchars($b['address'] ?? '') ?></textarea></div>
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status">
                            <?php foreach (array('active','trial','suspended','inactive','cancelled') as $st): ?>
                                <option value="<?php echo $st ?>" <?php echo ($b['status'] === $st ? 'selected' : '') ?>><?php echo ucfirst($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary btn-sm" type="submit">Save Business</button>
                    <a class="btn btn-outline-secondary btn-sm" href="<?php echo base_url ?>admin/login.php" target="_blank" rel="noopener">Open Retailer Login</a>
                </form>
                <hr>
                <button type="button" class="btn btn-danger btn-sm" id="delete-business-btn">Delete Business Permanently</button>
                <p class="text-muted small mt-2 mb-0">Removes this business and all tenant data (products, orders, settings, users).</p>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header">Subscription</div>
            <div class="card-body">
                <?php if ($sub):
                    $subDisp = platform_subscription_display($sub);
                ?>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($sub['status']) ?></p>
                    <p><strong>Plan:</strong> <?php echo htmlspecialchars($sub['plan_name'] ?? '') ?></p>
                    <p><strong><?php echo htmlspecialchars($subDisp['end_label']) ?>:</strong> <?php echo htmlspecialchars($subDisp['end_value']) ?></p>
                <?php else: ?>
                    <p class="text-muted">No subscription record.</p>
                <?php endif; ?>
                <form id="extend-sub-frm" class="mb-3">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="business_id" value="<?php echo $id ?>">
                    <div class="form-row">
                        <div class="col"><input type="number" class="form-control" name="days" value="30" min="1"></div>
                        <div class="col-auto"><button class="btn btn-success btn-sm" type="submit">Extend Days</button></div>
                    </div>
                </form>
                <form id="assign-plan-frm">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="business_id" value="<?php echo $id ?>">
                    <div class="form-group">
                        <label>Change Plan</label>
                        <select class="form-control" name="plan_id">
                            <?php foreach ($plans as $p): ?>
                                <option value="<?php echo (int)$p['id'] ?>"><?php echo htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <select class="form-control mb-2" name="mode"><option value="active">Active</option><option value="trial">Trial</option></select>
                    <button class="btn btn-outline-primary btn-sm" type="submit">Assign Plan</button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Owner: <?php echo htmlspecialchars(trim(($b['firstname'] ?? '') . ' ' . ($b['lastname'] ?? ''))) ?> (<?php echo htmlspecialchars($b['owner_username'] ?? '') ?>)</div>
            <div class="card-body">
                <form id="reset-owner-frm">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="business_id" value="<?php echo $id ?>">
                    <div class="form-group"><label>New Password</label><input type="password" class="form-control" name="new_password" minlength="6" required></div>
                    <button class="btn btn-warning btn-sm" type="submit">Reset Owner Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$platformBusinessesUrl = PLATFORM_BASE . '?page=businesses';
$pageScripts = <<<HTML
<script>
$(function(){
    $('#update-business-frm').submit(function(e){ e.preventDefault(); postPlatform('update_business', $(this).serialize(), function(r){ alert(r.msg); if(r.status==='success') location.reload(); }); });
    $('#extend-sub-frm').submit(function(e){ e.preventDefault(); postPlatform('extend_subscription', $(this).serialize(), function(r){ alert(r.msg); if(r.status==='success') location.reload(); }); });
    $('#assign-plan-frm').submit(function(e){ e.preventDefault(); postPlatform('assign_plan', $(this).serialize(), function(r){ alert(r.msg); if(r.status==='success') location.reload(); }); });
    $('#reset-owner-frm').submit(function(e){ e.preventDefault(); postPlatform('reset_owner_password', $(this).serialize(), function(r){ alert(r.msg); }); });
    $('#delete-business-btn').click(function(){
        if(!confirm('Delete this business and ALL its data permanently? This cannot be undone.')) return;
        postPlatform('delete_business', { csrf: '{$csrf}', id: {$id} }, function(r){
            alert(r.msg || 'Done');
            if(r.status === 'success') location.href = '{$platformBusinessesUrl}';
        });
    });
});
</script>
HTML;
?>
