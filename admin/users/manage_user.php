<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../inc/module_ui.php';
users_ensure_schema();
if(!admin_is_owner()){
    echo '<div class="alert alert-danger mb-0">Access denied.</div>';
    return;
}
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$row = array(
    'id' => 0,
    'firstname' => '',
    'lastname' => '',
    'username' => '',
    'email' => '',
    'phone' => '',
    'type' => 2,
    'status' => 1,
);
$user_perms = admin_default_cashier_permissions();
if($id > 0){
    $q = $conn->query("SELECT * FROM users WHERE id = '{$id}' LIMIT 1");
    if($q && $q->num_rows) $row = $q->fetch_assoc();
    $decoded = admin_load_user_permissions($id);
    if($decoded !== null) $user_perms = $decoded;
}
$roles = admin_role_definitions();
$catalog = admin_permission_catalog();
$groups = ash_permission_groups();
$is_edit = $id > 0;
?>
<form id="staff-user-form">
    <input type="hidden" name="id" value="<?php echo (int)$row['id'] ?>">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>First Name <span class="text-danger">*</span></label>
                <input type="text" name="firstname" class="form-control" required value="<?php echo htmlspecialchars($row['firstname']) ?>">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Last Name <span class="text-danger">*</span></label>
                <input type="text" name="lastname" class="form-control" required value="<?php echo htmlspecialchars($row['lastname']) ?>">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Username <span class="text-danger">*</span></label>
                <input type="text" name="username" class="form-control" required autocomplete="off" value="<?php echo htmlspecialchars($row['username']) ?>">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Role <span class="text-danger">*</span></label>
                <select name="type" class="form-control" id="staff-role-select">
                    <?php foreach($roles as $type_id => $meta): ?>
                    <option value="<?php echo (int)$type_id ?>" <?php echo (int)$row['type'] === (int)$type_id ? 'selected' : '' ?>><?php echo htmlspecialchars($meta['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Email <span class="text-muted small">(optional)</span></label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($row['email'] ?? '') ?>">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Phone <span class="text-muted small">(optional)</span></label>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($row['phone'] ?? '') ?>">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label><?php echo $is_edit ? 'New Password (leave blank to keep)' : 'Password' ?> <?php if(!$is_edit): ?><span class="text-danger">*</span><?php endif; ?></label>
                <input type="password" name="password" class="form-control" autocomplete="new-password" <?php echo $is_edit ? '' : 'required' ?> minlength="4">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="1" <?php echo (!users_table_has_status() || (int)($row['status'] ?? 1) === 1) ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?php echo (users_table_has_status() && (int)($row['status'] ?? 1) === 0) ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
    </div>
    <div id="staff-permissions-wrap" class="<?php echo (int)$row['type'] === 2 ? '' : 'd-none' ?>">
        <hr>
        <h6 class="font-weight-bold mb-2"><i class="fas fa-user-shield mr-1"></i> User Permissions</h6>
        <p class="small text-muted">Overrides default staff role permissions for this user. Admin/Owner accounts always have full access.</p>
        <div class="row">
            <?php foreach($groups as $group_name => $keys): ?>
            <div class="col-md-6 mb-3">
                <div class="border rounded p-2">
                    <div class="small font-weight-bold text-uppercase text-muted mb-2"><?php echo htmlspecialchars($group_name) ?></div>
                    <?php foreach($keys as $key): if(!isset($catalog[$key])) continue; $locked = !empty($catalog[$key]['admin_only']); ?>
                    <div class="custom-control custom-checkbox mb-1">
                        <input type="checkbox" class="custom-control-input staff-perm" name="permissions[<?php echo $key ?>]" id="staff-perm-<?php echo $key ?>" value="1"
                            <?php echo !empty($user_perms[$key]) ? 'checked' : '' ?> <?php echo $locked ? 'disabled' : '' ?>>
                        <label class="custom-control-label small" for="staff-perm-<?php echo $key ?>"><?php echo htmlspecialchars($catalog[$key]['label']) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</form>
<script>
$(function(){
    function togglePermPanel(){
        var isStaff = parseInt($('#staff-role-select').val(), 10) === 2;
        $('#staff-permissions-wrap').toggleClass('d-none', !isStaff);
    }
    $('#staff-role-select').change(togglePermPanel);
    $('#uni_modal #submit').off('click').on('click', function(e){
        e.preventDefault();
        var $f = $('#staff-user-form');
        if(!$f[0].checkValidity()){ $f[0].reportValidity(); return; }
        start_loader();
        $.ajax({
            url: _base_url_ + 'classes/Master.php?f=save_staff_user',
            method: 'POST',
            data: $f.serialize(),
            dataType: 'json',
            success: function(resp){
                end_loader();
                if(resp && resp.status === 'success'){
                    alert_toast(resp.msg || 'Saved.','success');
                    $('#uni_modal').modal('hide');
                    setTimeout(function(){ location.reload(); }, 800);
                }else{
                    alert_toast((resp && resp.msg) ? resp.msg : 'Save failed.','error');
                }
            },
            error: function(){
                end_loader();
                alert_toast('An error occurred.','error');
            }
        });
    });
});
</script>
