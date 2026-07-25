<?php
require_once __DIR__.'/../inc/module_ui.php';
if(admin_is_cashier()){
    echo '<div class="alert alert-danger">Access denied. Backup & Restore is available to administrators only.</div>';
    return;
}
$backup_dir = backup_dir_path();
$backup_count = 0;
$backup_storage = 0;
$last_backup_date = '&mdash;';
if(backup_logs_table_enabled()){
    $stats_row = $conn->query("SELECT COUNT(*) AS total, COALESCE(SUM(file_size), 0) AS storage FROM backup_logs WHERE 1=1".tenant_sql())->fetch_assoc();
    $backup_count = (int)($stats_row['total'] ?? 0);
    $backup_storage = (float)($stats_row['storage'] ?? 0);
    $last_backup = backup_last_info();
    if($last_backup) $last_backup_date = date('M d, Y H:i', strtotime($last_backup['date_created']));
}
echo module_page_styles();
?>
<?php if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif; ?>
<div class="mod-page">
    <div class="card mod-header mod-header-backup">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
            <div class="mb-2 mb-md-0">
                <h4><i class="fas fa-database mr-2"></i>Backup & Restore</h4>
                <p class="mod-subtitle">Create, download, and restore secure database backups</p>
            </div>
            <?php if(backup_logs_table_enabled()): ?>
            <button type="button" class="btn mod-btn-action mod-btn-success" id="createBackupBtn">
                <i class="fas fa-cloud-download-alt mr-1"></i> Create Backup
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if(!backup_logs_table_enabled()): ?>
    <div class="alert alert-warning">Backup module not installed. Run <code>database/update_new_modules.sql</code>.</div>
    <?php else: ?>

    <div class="mod-section">
        <div class="mod-section-header mod-sh-backup"><i class="fas fa-info-circle"></i> Overview</div>
        <div class="mod-section-body">
            <div class="row">
                <?php
                echo module_mini_stat('Backup Archives', format_num($backup_count), 'fa-archive', 'bg-info');
                echo module_mini_stat('Last Backup', $last_backup_date, 'fa-clock', 'bg-primary');
                echo module_mini_stat('Storage Used', format_file_size($backup_storage), 'fa-hdd', 'bg-teal');
                ?>
            </div>
        </div>
    </div>

    <div class="mod-warning-card">
        <div class="mod-warning-title"><i class="fas fa-exclamation-triangle mr-1"></i> Multi-Tenant Backup</div>
        <p>Backups include <strong>only your business data</strong>. Restore and bulk clean actions are disabled in SaaS mode to protect other tenants. Contact Kalmoy Tech Solutions if you need a restore.</p>
    </div>

    <div class="mod-section">
        <div class="mod-section-header mod-sh-backup d-flex justify-content-between align-items-center">
            <span><i class="fas fa-history"></i> Backup History</span>
            <?php echo module_export_toolbar('backup_history', array(), 'mb-0'); ?>
        </div>
        <div class="mod-section-body p-0">
            <p class="small text-muted px-3 pt-2 mb-0">Files stored in <code>uploads/backups/</code></p>
            <div class="ash-table-wrap mod-table-wrap mt-2">
                <table class="table table-hover ash-table mod-table" id="backup-list">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>File Name</th>
                            <th>File Size</th>
                            <th>Date Created</th>
                            <th>Created By</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        $qry = $conn->query("SELECT * FROM backup_logs WHERE 1=1".tenant_sql()." ORDER BY date_created DESC, id DESC");
                        if($qry):
                            while($row = $qry->fetch_assoc()):
                        ?>
                        <tr>
                            <td class="text-muted"><?php echo $i++ ?></td>
                            <td><span class="ash-badge ash-badge-id"><i class="fas fa-file-code mr-1"></i><?php echo htmlspecialchars($row['filename']) ?></span></td>
                            <td><?php echo format_file_size($row['file_size']) ?></td>
                            <td class="text-nowrap"><?php echo date('Y-m-d H:i', strtotime($row['date_created'])) ?></td>
                            <td><?php echo htmlspecialchars($row['created_by_name'] !== '' ? $row['created_by_name'] : 'System') ?></td>
                            <td>
                                <?php if($row['status'] === 'success'): ?>
                                <span class="ash-badge ash-badge-status ash-badge-status-success">Success</span>
                                <?php else: ?>
                                <span class="ash-badge ash-badge-status ash-badge-status-danger"><?php echo htmlspecialchars($row['status']) ?></span>
                                <?php endif; ?>
                                <?php if(!empty($row['message'])): ?>
                                <div class="small text-muted mt-1"><?php echo htmlspecialchars(stripslashes($row['message'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-nowrap">
                                <div class="ash-table-actions">
                                <a href="<?php echo base_url ?>classes/Master.php?f=download_backup&id=<?php echo (int)$row['id'] ?>"
                                   class="btn btn-outline-primary mod-action-btn mr-1" title="Download"><i class="fas fa-download"></i></a>
                                <button type="button" class="btn btn-outline-danger mod-action-btn delete-backup"
                                    data-id="<?php echo (int)$row['id'] ?>" title="Delete"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if(!$qry || $qry->num_rows === 0): ?>
            <p class="text-muted text-center py-4 mb-0">No backups yet. Click <strong>One-Click Backup</strong> to create your first backup.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if(false && backup_logs_table_enabled() && admin_is_owner()): ?>
    <div class="mod-section">
        <div class="mod-section-header mod-sh-backup"><i class="fas fa-broom"></i> Clean Data</div>
        <div class="mod-section-body">
            <p class="text-muted mb-3">Remove selected business data safely after an automatic backup. This action cannot be undone once confirmed.</p>
            <div class="mod-warning-card mb-3">
                <div class="mod-warning-title"><i class="fas fa-shield-alt mr-1"></i> Safety</div>
                <p class="mb-0">A full database backup is created automatically before every clean action. Users, settings, categories, and brands are never removed.</p>
            </div>
            <div class="clean-data-actions">
                <button type="button" class="btn btn-outline-danger btn-clean-data" data-scope="sales">
                    <i class="fas fa-receipt" aria-hidden="true"></i><span>Clean Sales</span>
                </button>
                <button type="button" class="btn btn-outline-danger btn-clean-data" data-scope="purchases">
                    <i class="fas fa-truck-loading" aria-hidden="true"></i><span>Clean Purchases</span>
                </button>
                <button type="button" class="btn btn-outline-danger btn-clean-data" data-scope="expenses">
                    <i class="fas fa-file-invoice-dollar" aria-hidden="true"></i><span>Clean Expenses</span>
                </button>
                <button type="button" class="btn btn-outline-warning btn-clean-data" data-scope="notifications">
                    <i class="fas fa-bell" aria-hidden="true"></i><span>Clean Notifications</span>
                </button>
                <button type="button" class="btn btn-danger btn-clean-data btn-clean-data-reset" data-scope="full">
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i><span>Full Business Reset</span>
                </button>
            </div>
            <ul class="clean-data-notes small text-muted mb-0 pl-3">
                <li><strong>Clean Sales</strong> — orders, sales, order lines, cart</li>
                <li><strong>Clean Purchases</strong> — purchase receipts and linked stock adjustment</li>
                <li><strong>Clean Expenses</strong> — all expense records</li>
                <li><strong>Clean Notifications</strong> — dashboard notifications only</li>
                <li><strong>Full Business Reset</strong> — sales, purchases, expenses, notifications, inventory, and non-master products</li>
            </ul>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<div class="modal fade" id="cleanDataModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-broom mr-1"></i> <span id="cleanDataModalTitle">Clean Data</span></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p id="cleanDataModalMessage" class="mb-3">This action cannot be undone. A backup will be created first.</p>
                <div id="cleanPurchasesOptions" class="custom-control custom-checkbox mb-3" style="display:none;">
                    <input type="checkbox" class="custom-control-input" id="cleanResetStock" checked>
                    <label class="custom-control-label" for="cleanResetStock">Reverse stock quantities linked to purchase receipt items</label>
                </div>
                <div id="cleanFullConfirmWrap" style="display:none;">
                    <label for="cleanConfirmInput" class="small font-weight-bold">Type <code>CONFIRM</code> to proceed</label>
                    <input type="text" class="form-control" id="cleanConfirmInput" autocomplete="off" placeholder="CONFIRM">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="cleanDataProceedBtn" disabled>Proceed</button>
            </div>
        </div>
    </div>
</div>
<script>
$(function(){
    var cleanScope = '';
    var cleanLabels = {
        sales: 'Clean Sales',
        purchases: 'Clean Purchases',
        expenses: 'Clean Expenses',
        notifications: 'Clean Notifications',
        full: 'Full Business Reset'
    };
    var cleanMessages = {
        sales: 'Delete all sales, orders, order lines, and cart data. Products and inventory will be kept.',
        purchases: 'Delete purchase receipts and line items. Linked stock can be reversed automatically.',
        expenses: 'Delete all expense records.',
        notifications: 'Delete all dashboard notifications.',
        full: 'Delete sales, purchases, expenses, notifications, all inventory, and non-master products. Users, settings, categories, and brands are kept.'
    };

    function updateCleanProceedState(){
        var ready = true;
        if(cleanScope === 'full'){
            ready = ($('#cleanConfirmInput').val().trim() === 'CONFIRM');
        }
        $('#cleanDataProceedBtn').prop('disabled', !ready);
    }

    $('.btn-clean-data').click(function(){
        cleanScope = $(this).data('scope');
        $('#cleanDataModalTitle').text(cleanLabels[cleanScope] || 'Clean Data');
        $('#cleanDataModalMessage').text('This action cannot be undone. A backup will be created first. ' + (cleanMessages[cleanScope] || ''));
        $('#cleanPurchasesOptions').toggle(cleanScope === 'purchases');
        $('#cleanFullConfirmWrap').toggle(cleanScope === 'full');
        $('#cleanConfirmInput').val('');
        updateCleanProceedState();
        $('#cleanDataModal').modal('show');
    });

    $('#cleanConfirmInput').on('input', updateCleanProceedState);

    $('#cleanDataProceedBtn').click(function(){
        if(!cleanScope) return;
        if(cleanScope === 'full' && $('#cleanConfirmInput').val().trim() !== 'CONFIRM'){
            alert_toast('Type CONFIRM to proceed.','error');
            return;
        }
        $('#cleanDataModal').modal('hide');
        run_clean_business_data(cleanScope);
    });

    $('#createBackupBtn').click(function(){
        _conf('Create a full database backup now?','run_create_backup',[]);
    });
    $('.restore-backup').click(function(){
        var id = $(this).data('id');
        var name = $(this).data('name');
        _conf('WARNING: Restore will overwrite ALL current data with backup "'+name+'". Continue?','run_restore_backup',[id]);
    });
    $('.delete-backup').click(function(){
        _conf('Delete this backup file permanently?','run_delete_backup',[$(this).data('id')]);
    });
    if($('#backup-list tbody tr').length) {
        $('#backup-list').dataTable({ order:[[3,'desc']], columnDefs:[{orderable:false,targets:[6]}] });
    }
});
function run_create_backup(){
    start_loader();
    $.post(_base_url_+'classes/Master.php?f=create_backup',{},function(resp){
        if(typeof resp==='object' && resp.status==='success') location.reload();
        else { alert_toast(resp.msg||'Backup failed','error'); end_loader(); }
    },'json');
}
function run_restore_backup(id){
    start_loader();
    $.post(_base_url_+'classes/Master.php?f=restore_backup',{id:id},function(resp){
        if(typeof resp==='object' && resp.status==='success'){ alert_toast(resp.msg,'success'); setTimeout(function(){ location.reload(); },1500); }
        else { alert_toast(resp.msg||'Restore failed','error'); end_loader(); }
    },'json');
}
function run_delete_backup(id){
    start_loader();
    $.post(_base_url_+'classes/Master.php?f=delete_backup',{id:id},function(resp){
        if(typeof resp==='object' && resp.status==='success') location.reload();
        else { alert_toast('Delete failed','error'); end_loader(); }
    },'json');
}
function run_clean_business_data(scope){
    start_loader();
    $.post(_base_url_+'classes/Master.php?f=clean_business_data',{
        scope: scope,
        confirm_text: $('#cleanConfirmInput').val().trim(),
        reset_stock: $('#cleanResetStock').is(':checked') ? 1 : 0
    },function(resp){
        if(typeof resp==='object' && resp.status==='success'){
            alert_toast(resp.msg || 'Data cleaned successfully.','success');
            setTimeout(function(){ location.reload(); }, 1200);
        }else{
            alert_toast((resp && resp.msg) ? resp.msg : 'Clean failed.','error');
            end_loader();
        }
    },'json').fail(function(){
        alert_toast('Clean request failed.','error');
        end_loader();
    });
}
</script>
