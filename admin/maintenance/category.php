<?php
require_once __DIR__.'/../inc/module_ui.php';
if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<?php $category_counts = ash_category_product_counts(); ?>
<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">Categories</h3>
		<div class="card-tools d-flex align-items-center flex-wrap">
			<?php echo module_export_toolbar('categories', array(), 'mr-2'); ?>
			<?php if(admin_cashier_can('categories')): ?>
			<a href="javascript:void(0)" id="create_new" class="btn btn-flat btn-primary btn-sm"><span class="fas fa-plus"></span> Create New</a>
			<?php endif; ?>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
			<div class="ash-table-wrap">
			<table class="table table-hover ash-table" id="category-list">
				<thead>
					<tr>
						<th>Category</th>
						<th class="text-center">Products</th>
						<th class="text-center">Status</th>
						<th class="text-center ash-col-actions">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						$qry = $conn->query("SELECT * from `categories` where delete_flag = 0 order by category asc ");
						while($row = $qry->fetch_assoc()):
                            $row['description'] = strip_tags(stripslashes(html_entity_decode($row['description'])));
                            $prod_cnt = isset($category_counts[(int)$row['id']]) ? $category_counts[(int)$row['id']] : 0;
					?>
						<tr>
							<td><span class="ash-text-bold"><?php echo htmlspecialchars($row['category']) ?></span></td>
							<td class="text-center"><?php echo format_num($prod_cnt) ?></td>
							<td class="text-center">
                                <?php echo $row['status'] == 1 ? ash_status_badge('Active', 'active') : ash_status_badge('Inactive', 'inactive') ?>
                            </td>
							<td class="text-center">
								<div class="ash-table-actions">
								<?php echo ash_icon_btn('view', 'View Details', 'view_data', array('data-id' => $row['id'])) ?>
								<?php if(admin_cashier_can('categories')): ?>
								<?php echo ash_icon_btn('edit', 'Edit', 'edit_data', array('data-id' => $row['id'])) ?>
								<?php if(admin_cashier_can('delete_actions')): ?>
								<?php echo ash_icon_btn('delete', 'Delete', 'delete_data', array('data-id' => $row['id'])) ?>
								<?php endif; ?>
								<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
			</div>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		$('[data-toggle="tooltip"]').tooltip();
		$('.delete_data').click(function(){
			_conf("Are you sure to delete this Category permanently?","delete_category",[$(this).attr('data-id')])
		})
		$('#create_new').click(function(){
			uni_modal("<i class='fa fa-plus'></i> Add New Category","maintenance/manage_category.php")
		})
		$('.view_data').click(function(){
			uni_modal("<i class='fa fa-eye'></i> Category Details","maintenance/view_category.php?id="+$(this).attr('data-id'))
		})
		$('.edit_data').click(function(){
			uni_modal("<i class='fa fa-edit'></i> Update Category Details","maintenance/manage_category.php?id="+$(this).attr('data-id'))
		})
		$('#category-list').dataTable({
			columnDefs: [{ orderable: false, targets: [3] }],
			order:[[0,'asc']]
		});
	})
	function delete_category($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_category",
			method:"POST",
			data:{id: $id},
			dataType:"json",
			error:err=>{
				console.log(err)
				alert_toast("An error occured.",'error');
				end_loader();
			},
			success:function(resp){
				if(typeof resp== 'object' && resp.status == 'success'){
					location.reload();
				}else{
					alert_toast("An error occured.",'error');
					end_loader();
				}
			}
		})
	}
</script>
