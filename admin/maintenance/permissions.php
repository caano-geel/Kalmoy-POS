<?php
require_once __DIR__.'/../inc/module_ui.php';
if(!admin_is_owner()){
    echo ash_swal_access_denied_script(base_url.'admin/');
    return;
}
users_ensure_schema();

$catalog = admin_permission_catalog();
$cashier_perms = admin_load_cashier_permissions();
$groups = ash_permission_groups();
$staff_users = array();
$uq = $conn->query("SELECT id, firstname, lastname, username, type FROM users WHERE type = 2 ORDER BY firstname, lastname");
while($uq && ($ur = $uq->fetch_assoc())){
    $staff_users[] = $ur;
}
$selected_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$user_perms = null;
if($selected_user_id > 0){
    $user_perms = admin_load_user_permissions($selected_user_id);
    if($user_perms === null){
        $user_perms = admin_load_cashier_permissions();
    }
}
echo module_page_styles();
?>
<div class="mod-page">
    <div class="card mod-header">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
            <div class="mb-2 mb-md-0">
                <h4><i class="fas fa-user-shield mr-2"></i> Role Permissions</h4>
                <p class="mod-subtitle">Choose what each role can see and do in your shop</p>
            </div>
            <a href="<?php echo base_url ?>admin/?page=users" class="btn mod-btn-action btn-light text-primary">
                <i class="fas fa-arrow-left mr-1"></i> Back to Users / Staff
            </a>
        </div>
    </div>

    <div class="ash-perm-intro mb-4">
        <div class="ash-perm-intro-title"><i class="fas fa-info-circle mr-1"></i> How access works</div>
        <p class="mb-2">Use this page to control which parts of the system your team can open. You do not need technical knowledge — just tick the features you want cashiers to use.</p>
        <ul class="ash-perm-intro-list mb-0">
            <li><strong>Admin / Owner</strong> <span class="text-muted">= full control</span> — you can manage everything. This cannot be changed.</li>
            <li><strong>Cashier / Shopkeeper</strong> <span class="text-muted">= limited POS access</span> — set the default menus for all cashiers.</li>
            <li><strong>Per-User Permissions</strong> <span class="text-muted">= special permission override for one staff member</span> — give one person extra (or fewer) rights without changing everyone else.</li>
        </ul>
    </div>

    <div id="perm-msg"></div>
    <div class="row">
        <div class="col-12 col-lg-6 mb-4">
            <div class="card card-outline card-secondary h-100">
                <div class="card-header py-2">
                    <h5 class="card-title mb-0">Admin / Owner</h5>
                    <p class="ash-perm-role-label mb-0">Full control</p>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2">The shop owner and administrators always have access to every feature. These options are shown for reference only.</p>
                        <div class="ash-perm-grid">
                            <?php foreach($groups as $group_name => $keys): ?>
                            <div class="ash-perm-card">
                                <div class="ash-perm-card-head"><h6><?php echo htmlspecialchars($group_name) ?></h6></div>
                                <div class="ash-perm-card-body">
                                    <?php foreach($keys as $key): if(!isset($catalog[$key])) continue; ?>
                                    <div class="custom-control custom-checkbox mb-1">
                                        <input type="checkbox" class="custom-control-input" id="perm-admin-<?php echo $key ?>" checked disabled>
                                        <label class="custom-control-label" for="perm-admin-<?php echo $key ?>"><?php echo htmlspecialchars($catalog[$key]['label']) ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6 mb-4">
                <div class="card card-outline card-info h-100">
                    <div class="card-header py-2">
                        <h5 class="card-title mb-0">Cashier / Shopkeeper</h5>
                        <p class="ash-perm-role-label mb-0">Limited POS access</p>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-2">Tick the menus and actions your cashiers may use. Changes apply to all staff with the Cashier role (unless a person has their own override below).</p>
                        <div class="ash-perm-toolbar">
                            <input type="text" class="form-control form-control-sm ash-perm-search" id="perm-search" placeholder="Search permissions...">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="perm-expand-all">Expand</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="perm-collapse-all">Collapse</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="perm-select-all">Select All</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="perm-deselect-all">Deselect All</button>
                        </div>
                        <form id="cashier-permissions-form">
                            <div class="ash-perm-grid" id="cashier-perm-grid">
                                <?php foreach($groups as $group_name => $keys): ?>
                                <div class="ash-perm-card perm-group-card" data-group="<?php echo htmlspecialchars(strtolower($group_name)) ?>">
                                    <div class="ash-perm-card-head perm-toggle">
                                        <h6><?php echo htmlspecialchars($group_name) ?></h6>
                                        <i class="fas fa-chevron-down small text-muted"></i>
                                    </div>
                                    <div class="ash-perm-card-body">
                                        <?php foreach($keys as $key): if(!isset($catalog[$key])) continue; ?>
                                        <?php $locked = !empty($catalog[$key]['admin_only']); ?>
                                        <div class="custom-control custom-checkbox mb-1 perm-item" data-label="<?php echo htmlspecialchars(strtolower($catalog[$key]['label'])) ?>">
                                            <input type="checkbox"
                                                class="custom-control-input cashier-perm"
                                                name="permissions[<?php echo $key ?>]"
                                                id="perm-cashier-<?php echo $key ?>"
                                                value="1"
                                                data-perm="<?php echo $key ?>"
                                                <?php echo !empty($cashier_perms[$key]) ? 'checked' : '' ?>
                                                <?php echo $locked ? 'disabled' : '' ?>>
                                            <label class="custom-control-label" for="perm-cashier-<?php echo $key ?>">
                                                <?php echo htmlspecialchars($catalog[$key]['label']) ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer py-2">
                        <button type="button" class="btn btn-sm btn-primary" id="save-cashier-permissions">
                            <i class="fas fa-save"></i> Save Permissions
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="mod-section mb-0">
            <div class="mod-section-header mod-sh-filter">
                <span><i class="fas fa-user"></i> Per-User Permissions</span>
            </div>
            <div class="mod-section-body">
                <p class="ash-perm-role-label mb-2">Special permission override for one staff member</p>
                <p class="text-muted small mb-3">Pick a cashier below to give them different access from the default. Useful when one trusted staff member needs extra rights, or when you want to restrict a single account.</p>
                <?php if(empty($staff_users)): ?>
                <p class="text-muted mb-0">No staff users yet. <a href="<?php echo base_url ?>admin/?page=users">Create a staff account</a> first.</p>
                <?php else: ?>
                <form method="get" action="" class="form-inline mb-3">
                    <input type="hidden" name="page" value="maintenance/permissions">
                    <label class="mr-2 mb-2 font-weight-bold">Choose staff member</label>
                    <select name="user_id" class="form-control form-control-sm mr-2 mb-2" onchange="this.form.submit()">
                        <option value="">— Select user —</option>
                        <?php foreach($staff_users as $su): ?>
                        <option value="<?php echo (int)$su['id'] ?>" <?php echo $selected_user_id === (int)$su['id'] ? 'selected' : '' ?>>
                            <?php echo htmlspecialchars(trim($su['firstname'].' '.$su['lastname']).' ('.$su['username'].')') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <?php if($selected_user_id > 0 && $user_perms !== null): ?>
                <form id="user-permissions-form">
                    <input type="hidden" name="user_id" value="<?php echo $selected_user_id ?>">
                    <div class="ash-perm-grid">
                        <?php foreach($groups as $group_name => $keys): ?>
                        <div class="ash-perm-card">
                            <div class="ash-perm-card-head"><h6><?php echo htmlspecialchars($group_name) ?></h6></div>
                            <div class="ash-perm-card-body">
                                <?php foreach($keys as $key): if(!isset($catalog[$key])) continue; $locked = !empty($catalog[$key]['admin_only']); ?>
                                <div class="custom-control custom-checkbox mb-1">
                                    <input type="checkbox" class="custom-control-input user-perm" name="permissions[<?php echo $key ?>]" id="user-perm-<?php echo $key ?>" value="1"
                                        <?php echo !empty($user_perms[$key]) ? 'checked' : '' ?> <?php echo $locked ? 'disabled' : '' ?>>
                                    <label class="custom-control-label" for="user-perm-<?php echo $key ?>"><?php echo htmlspecialchars($catalog[$key]['label']) ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary mt-2" id="save-user-permissions"><i class="fas fa-save"></i> Save User Permissions</button>
                </form>
                <?php elseif($selected_user_id > 0): ?>
                <p class="text-muted mb-0">That user was not found, or they are an administrator (they already have full control).</p>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
</div>
<script>
$(function(){
    $('.perm-toggle').click(function(){
        $(this).siblings('.ash-perm-card-body').slideToggle(150);
        $(this).find('.fa-chevron-down, .fa-chevron-up').toggleClass('fa-chevron-down fa-chevron-up');
    });
    $('#perm-expand-all').click(function(){
        $('#cashier-perm-grid .ash-perm-card-body').slideDown(100);
    });
    $('#perm-collapse-all').click(function(){
        $('#cashier-perm-grid .ash-perm-card-body').slideUp(100);
    });
    $('#perm-select-all').click(function(){
        $('#cashier-permissions-form .cashier-perm:not(:disabled)').prop('checked', true);
    });
    $('#perm-deselect-all').click(function(){
        $('#cashier-permissions-form .cashier-perm:not(:disabled)').prop('checked', false);
    });
    $('#perm-search').on('input', function(){
        var q = $(this).val().toLowerCase().trim();
        $('#cashier-perm-grid .perm-group-card').each(function(){
            var $card = $(this);
            var visible = 0;
            $card.find('.perm-item').each(function(){
                var match = !q || $(this).data('label').indexOf(q) !== -1 || $card.data('group').indexOf(q) !== -1;
                $(this).toggle(match);
                if(match) visible++;
            });
            $card.toggle(visible > 0 || !q);
            if(q && visible > 0) $card.find('.ash-perm-card-body').show();
        });
    });
    $('#save-cashier-permissions').click(function(){
        var perms = {};
        $('#cashier-permissions-form .cashier-perm').each(function(){
            perms[$(this).data('perm')] = $(this).is(':checked') ? 1 : 0;
        });
        start_loader();
        $.ajax({
            url: _base_url_ + 'classes/Master.php?f=save_cashier_permissions',
            method: 'POST',
            data: { permissions: JSON.stringify(perms) },
            dataType: 'json',
            error: function(){
                alert_toast('An error occurred while saving permissions.', 'error');
                end_loader();
            },
            success: function(resp){
                end_loader();
                if(resp && resp.status === 'success'){
                    alert_toast(resp.msg || 'Permissions saved.', 'success');
                }else{
                    alert_toast((resp && resp.msg) ? resp.msg : 'Unable to save permissions.', 'error');
                }
            }
        });
    });
    $('#save-user-permissions').click(function(){
        var perms = {};
        $('#user-permissions-form .user-perm').each(function(){
            var id = $(this).attr('id').replace('user-perm-','');
            perms[id] = $(this).is(':checked') ? 1 : 0;
        });
        start_loader();
        $.ajax({
            url: _base_url_ + 'classes/Master.php?f=save_user_permissions',
            method: 'POST',
            data: {
                user_id: $('#user-permissions-form input[name="user_id"]').val(),
                permissions: JSON.stringify(perms)
            },
            dataType: 'json',
            success: function(resp){
                end_loader();
                if(resp && resp.status === 'success') alert_toast(resp.msg,'success');
                else alert_toast((resp&&resp.msg)||'Save failed','error');
            },
            error: function(){ end_loader(); alert_toast('Save failed.','error'); }
        });
    });
});
</script>
