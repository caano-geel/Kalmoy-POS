<?php
require_once __DIR__.'/../inc/module_ui.php';
users_ensure_schema();
if(!admin_is_owner()){
    echo ash_swal_access_denied_script(base_url.'admin/');
    return;
}
echo module_page_styles();
if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif; ?>
<div class="mod-page">
    <div class="card mod-header">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
            <div class="mb-2 mb-md-0">
                <h4><i class="fas fa-user-friends mr-2"></i> Users / Staff</h4>
                <p class="mod-subtitle">Create accounts, assign roles, and control who can access your shop</p>
            </div>
            <div class="d-flex flex-wrap">
                <a href="<?php echo base_url ?>admin/?page=maintenance/permissions" class="btn mod-btn-action btn-light text-primary mr-2 mb-2">
                    <i class="fas fa-user-shield mr-1"></i> Role Permissions
                </a>
                <button type="button" class="btn mod-btn-action mod-btn-primary mb-2" id="create-user-btn">
                    <i class="fas fa-plus mr-1"></i> Create User
                </button>
            </div>
        </div>
    </div>
    <div class="ash-perm-intro mb-3">
        <div class="ash-perm-intro-title"><i class="fas fa-info-circle mr-1"></i> Roles at a glance</div>
        <ul class="ash-perm-intro-list mb-0">
            <li><strong>Admin / Owner</strong> <span class="text-muted">= full control</span> — manage products, reports, settings, backups, and staff.</li>
            <li><strong>Cashier / Shopkeeper</strong> <span class="text-muted">= limited POS access</span> — mainly sell at the till; other menus depend on <a href="<?php echo base_url ?>admin/?page=maintenance/permissions">Role Permissions</a>.</li>
            <li><strong>Per-User Permissions</strong> <span class="text-muted">= special permission override for one staff member</span> — set when editing a user or on the Role Permissions page.</li>
        </ul>
    </div>
    <div class="mod-section">
        <div class="mod-section-body p-0">
            <div class="ash-table-wrap mod-table-wrap">
                <table class="table table-hover ash-table mod-table" id="staff-list">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Contact</th>
                            <th>Role</th>
                            <th class="text-center">Status</th>
                            <th>Created</th>
                            <th class="text-center ash-col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $roles = admin_role_definitions();
                        $qry = $conn->query("SELECT * FROM users WHERE 1=1".tenant_sql()." ORDER BY date_added DESC, id DESC");
                        if($qry):
                            while($row = $qry->fetch_assoc()):
                                $fullname = trim($row['firstname'].' '.$row['lastname']);
                                $role_label = isset($roles[(int)$row['type']]) ? $roles[(int)$row['type']]['label'] : 'Staff';
                                $active = !users_table_has_status() || (int)$row['status'] === 1;
                                $email = isset($row['email']) ? trim($row['email']) : '';
                                $phone = isset($row['phone']) ? trim($row['phone']) : '';
                        ?>
                        <tr>
                            <td><div class="ash-text-bold"><?php echo htmlspecialchars($fullname) ?></div></td>
                            <td><?php echo htmlspecialchars($row['username']) ?></td>
                            <td class="small">
                                <?php if($email !== ''): ?><div><?php echo htmlspecialchars($email) ?></div><?php endif; ?>
                                <?php if($phone !== ''): ?><div class="text-muted"><?php echo htmlspecialchars($phone) ?></div><?php endif; ?>
                                <?php if($email === '' && $phone === ''): ?><span class="text-muted">&mdash;</span><?php endif; ?>
                            </td>
                            <td><span class="ash-badge ash-badge-id"><?php echo htmlspecialchars($role_label) ?></span></td>
                            <td class="text-center"><?php echo $active ? ash_status_badge('Active', 'active') : ash_status_badge('Inactive', 'inactive') ?></td>
                            <td class="text-nowrap small"><?php echo date('Y-m-d', strtotime($row['date_added'])) ?></td>
                            <td class="text-center">
                                <div class="ash-table-actions">
                                    <button type="button" class="ash-icon-btn ash-icon-btn-edit edit-staff" title="Edit" data-id="<?php echo (int)$row['id'] ?>"><i class="fas fa-edit"></i></button>
                                    <button type="button" class="ash-icon-btn ash-icon-btn-edit reset-staff-pw" title="Reset Password" data-id="<?php echo (int)$row['id'] ?>" data-name="<?php echo htmlspecialchars($fullname) ?>"><i class="fas fa-key"></i></button>
                                    <?php if((int)$row['id'] !== (int)$_SESSION['userdata']['id']): ?>
                                    <button type="button" class="ash-icon-btn ash-icon-btn-edit toggle-staff-status" title="Toggle Status" data-id="<?php echo (int)$row['id'] ?>" data-active="<?php echo $active ? '1' : '0' ?>"><i class="fas fa-<?php echo $active ? 'ban' : 'check' ?>"></i></button>
                                    <button type="button" class="ash-icon-btn ash-icon-btn-delete delete-staff" title="Delete" data-id="<?php echo (int)$row['id'] ?>"><i class="fas fa-trash"></i></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
$(function(){
    $('#create-user-btn').click(function(){
        uni_modal("<i class='fas fa-user-plus'></i> Create User", "users/manage_user.php", "modal-lg");
    });
    $('.edit-staff').click(function(){
        uni_modal("<i class='fas fa-user-edit'></i> Edit User", "users/manage_user.php?id="+$(this).data('id'), "modal-lg");
    });
    $('.delete-staff').click(function(){
        _conf('Delete this user permanently?','delete_staff_user',[$(this).data('id')]);
    });
    $('.toggle-staff-status').click(function(){
        var active = $(this).data('active') == 1;
        _conf((active ? 'Deactivate' : 'Activate')+' this user?','toggle_staff_user',[$(this).data('id')]);
    });
    $('.reset-staff-pw').click(function(){
        var id = $(this).data('id');
        var name = $(this).data('name');
        $('#uni_modal .modal-title').html("<i class='fas fa-key'></i> Reset Password");
        $('#uni_modal .modal-body').html(
            '<p class="small text-muted mb-2">Set a new password for <strong>'+escapeHtml(name)+'</strong>. It will not be shown again after saving.</p>'+
            '<div class="form-group mb-0"><label>New Password</label><input type="password" class="form-control" id="reset-pw-input" minlength="4" required></div>'
        );
        $('#uni_modal .modal-dialog').addClass('modal-md modal-dialog-centered');
        $('#uni_modal #submit').off('click').on('click', function(){
            var pw = $('#reset-pw-input').val();
            if(!pw || pw.length < 4){ alert_toast('Password must be at least 4 characters.','error'); return; }
            $('#uni_modal').modal('hide');
            run_reset_staff_password(id, pw);
        });
        $('#uni_modal').modal('show');
    });
    if($('#staff-list tbody tr').length){
        $('#staff-list').dataTable({ order:[[5,'desc']], columnDefs:[{orderable:false,targets:[6]}] });
    }
});
function delete_staff_user(id){
    start_loader();
    $.post(_base_url_+'classes/Master.php?f=delete_staff_user',{id:id},function(resp){
        if(resp && resp.status==='success') location.reload();
        else { alert_toast((resp&&resp.msg)||'Delete failed','error'); end_loader(); }
    },'json');
}
function toggle_staff_user(id){
    start_loader();
    $.post(_base_url_+'classes/Master.php?f=toggle_staff_status',{id:id},function(resp){
        if(resp && resp.status==='success') location.reload();
        else { alert_toast((resp&&resp.msg)||'Update failed','error'); end_loader(); }
    },'json');
}
function run_reset_staff_password(id, password){
    start_loader();
    $.post(_base_url_+'classes/Master.php?f=reset_staff_password',{id:id,password:password},function(resp){
        if(resp && resp.status==='success'){ alert_toast(resp.msg,'success'); end_loader(); }
        else { alert_toast((resp&&resp.msg)||'Reset failed','error'); end_loader(); }
    },'json');
}
function escapeHtml(s){ return $('<div>').text(s).html(); }
</script>
