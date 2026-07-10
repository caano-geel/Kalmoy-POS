<?php
require_once __DIR__.'/../inc/module_ui.php';
if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif;
$date_range = report_resolve_range(
    isset($_GET['report_preset']) ? $_GET['report_preset'] : ((isset($_GET['date_start']) || isset($_GET['date_end'])) ? 'custom' : 'month'),
    isset($_GET['date_start']) ? $_GET['date_start'] : '',
    isset($_GET['date_end']) ? $_GET['date_end'] : ''
);
$date_start = $date_range['start'];
$date_end = $date_range['end'];
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$expenses_today = dashboard_expenses_today();
$expenses_month = dashboard_expenses_month();
$expenses_ytd = expenses_total(report_resolve_range('year')['start'], report_resolve_range('year')['end'], '');
$expenses_lifetime = expenses_total(business_operating_bounds()['start'], business_operating_bounds()['end'], '');
$expenses_total_all = expenses_total($date_start, $date_end, $category_filter);
$can_manage = admin_cashier_can('expenses');
$report_preset = isset($_GET['report_preset']) ? $_GET['report_preset'] : ((isset($_GET['date_start']) || isset($_GET['date_end'])) ? 'custom' : 'month');
$cat_extra = $category_filter !== '' ? array('category' => $category_filter) : array();
$expenses_export_params = array_merge(array(
    'report_preset' => $report_preset,
    'date_start' => $date_start,
    'date_end' => $date_end,
), $cat_extra);
echo module_page_styles();
?>
<div class="mod-page">
    <div class="card mod-header mod-header-expenses">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
            <div class="mb-2 mb-md-0">
                <h4><i class="fas fa-file-invoice-dollar mr-2"></i>Operating Expenses</h4>
                <p class="mod-subtitle">Record, categorize, and monitor business operating expenses</p>
            </div>
            <?php if($can_manage): ?>
            <button type="button" class="btn mod-btn-action mod-btn-primary" id="create_new">
                <i class="fas fa-plus mr-1"></i> Add Expense
            </button>
            <?php endif; ?>
            <?php echo module_export_buttons_mod('expenses', $expenses_export_params); ?>
        </div>
    </div>

    <div class="mod-section">
        <div class="mod-section-header mod-sh-expenses"><i class="fas fa-chart-pie"></i> Summary</div>
        <div class="mod-section-body">
            <div class="row">
                <?php
                echo module_mini_stat('Expenses Today', format_price($expenses_today), 'fa-calendar-day', 'bg-danger');
                echo module_mini_stat('Expenses (MTD)', format_price($expenses_month), 'fa-calendar-alt', 'bg-warning');
                echo module_mini_stat('Expenses (YTD)', format_price($expenses_ytd), 'fa-chart-area', 'bg-secondary');
                echo module_mini_stat('All-Time Expenses', format_price($expenses_lifetime), 'fa-history', 'bg-indigo');
                echo module_mini_stat('Period Expenses', format_price($expenses_total_all), 'fa-coins', 'bg-primary');
                ?>
            </div>
        </div>
    </div>

    <div class="mod-section">
        <div class="mod-section-header mod-sh-filter"><i class="fas fa-filter"></i> Filter Expenses</div>
        <div class="mod-section-body">
            <?php
            $cat_extra = $category_filter !== '' ? array('category' => $category_filter) : array();
            $filter_html = module_report_date_filter('expenses', $date_range, $cat_extra);
            $filter_html = str_replace('</form>', '<div class="row align-items-end mt-2"><div class="col-12 col-md-4 form-group mb-0"><label>Category</label><select name="category" class="form-control form-control-sm"><option value="">All Categories</option>'.implode('', array_map(function($cat) use ($category_filter){
                $sel = $category_filter === $cat ? ' selected' : '';
                return '<option value="'.htmlspecialchars($cat).'"'.$sel.'>'.htmlspecialchars($cat).'</option>';
            }, expense_categories())).'</select></div><div class="col-12 col-md-4 form-group mb-0"><button type="submit" class="btn btn-sm btn-primary btn-block" style="border-radius:8px;font-weight:600;"><i class="fas fa-search mr-1"></i> Apply with Category</button></div></div></form>', $filter_html);
            echo $filter_html;
            ?>
        </div>
    </div>

    <div class="mod-section">
        <div class="mod-section-header mod-sh-expenses"><i class="fas fa-list"></i> Expense Ledger</div>
        <div class="mod-section-body p-0">
            <div class="ash-table-wrap mod-table-wrap">
                <table class="table table-hover ash-table mod-table" id="list">
                    <thead>
                        <tr>
                            <th class="ash-col-id">Expense ID</th>
                            <th>Date</th>
                            <th>Category</th>
                            <th class="ash-col-desc">Description</th>
                            <th>Payment</th>
                            <th class="text-right ash-col-money">Amount</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <?php if($can_manage): ?><th class="text-center ash-col-actions">Action</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $where = expenses_where_sql($date_start, $date_end, $category_filter);
                        $qry = expenses_table_enabled() ? $conn->query("SELECT * FROM expenses WHERE {$where} ORDER BY expense_date DESC, id DESC") : false;
                        $expense_rows = ($qry && $qry->num_rows > 0) ? $qry->num_rows : 0;
                        if($qry):
                            while($row = $qry->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo ash_table_id_badge(expense_format_id($row['id'])) ?></td>
                            <td class="text-nowrap"><?php echo date('Y-m-d', strtotime($row['expense_date'])) ?></td>
                            <td><?php echo ash_expense_category_badge($row['category']) ?></td>
                            <td class="ash-cell-wrap"><?php echo htmlspecialchars(stripslashes($row['description'])) ?></td>
                            <td><?php echo htmlspecialchars($row['payment_method']) ?></td>
                            <td class="text-right"><?php echo ash_format_money_cell($row['amount'], 'expense') ?></td>
                            <td><?php echo htmlspecialchars($row['created_by_name'] !== '' ? $row['created_by_name'] : 'System') ?></td>
                            <td class="text-nowrap text-muted small"><?php echo date('Y-m-d H:i', strtotime($row['date_created'])) ?></td>
                            <?php if($can_manage): ?>
                            <td class="text-center">
                                <div class="ash-table-actions">
                                <button type="button" class="btn btn-outline-primary ash-action-btn mod-action-btn edit_data" data-id="<?php echo $row['id'] ?>" title="Edit"><i class="fas fa-edit"></i></button>
                                <?php if(admin_cashier_can('delete_actions')): ?>
                                <button type="button" class="btn btn-outline-danger ash-action-btn mod-action-btn delete_data" data-id="<?php echo $row['id'] ?>" title="Delete"><i class="fas fa-trash"></i></button>
                                <?php endif; ?>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; endif; ?>
                        <?php if($qry && $expense_rows === 0): ?>
                        <tr class="expense-empty-row ash-table-empty">
                            <td colspan="<?php echo $can_manage ? 9 : 8 ?>">No expenses found for the selected filters.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if(!expenses_table_enabled()): ?>
            <p class="text-muted text-center py-4 mb-0">Expenses table not found. Please run <code>database/update_new_modules.sql</code>.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
$(document).ready(function(){
    $('#create_new').click(function(){
        uni_modal("<i class='fa fa-plus'></i> Add Expense","expenses/manage_expense.php",'mid-large')
    })
    $('.delete_data').click(function(){
        _conf("Are you sure to delete this expense?","delete_expense",[$(this).attr('data-id')])
    })
    $('.edit_data').click(function(){
        uni_modal("<i class='fa fa-edit'></i> Edit Expense","expenses/manage_expense.php?id="+$(this).attr('data-id'),'mid-large')
    })
    if($('#list tbody tr').not('.expense-empty-row').length > 0){
        if($.fn.DataTable.isDataTable('#list')){
            $('#list').DataTable().destroy();
        }
        $('#list').DataTable({
            columnDefs: [{ orderable: false, targets: [-1] }],
            order: [[1,'desc']],
            stateSave: false,
            searching: true,
            pageLength: 25
        });
    }
})
function delete_expense($id){
    start_loader();
    $.ajax({
        url:_base_url_+"classes/Master.php?f=delete_expense",
        method:"POST",
        data:{id: $id},
        dataType:"json",
        error:function(){ alert_toast("An error occured.",'error'); end_loader(); },
        success:function(resp){
            if(typeof resp== 'object' && resp.status == 'success') location.reload();
            else { alert_toast(resp.msg || "An error occured.",'error'); end_loader(); }
        }
    })
}
</script>
