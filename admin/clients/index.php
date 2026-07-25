<?php
require_once __DIR__.'/../inc/module_ui.php';
if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title mb-0">Customers</h3>
		<div class="card-tools d-flex align-items-center flex-wrap">
			<?php echo module_export_toolbar('customers', array(), 'mr-2'); ?>
			<?php if(admin_cashier_can('clients')): ?>
			<button type="button" class="btn btn-flat btn-primary btn-sm" id="create_new"><span class="fas fa-plus"></span> Add Customer</button>
			<?php endif; ?>
		</div>
	</div>
	<div class="card-body">
        <div class="container-fluid">
			<div class="ash-table-wrap">
			<table class="table table-hover ash-table" id="list">
				<thead>
					<tr>
						<th>Customer</th>
						<th>Email</th>
						<th class="text-center">Status</th>
						<th class="text-center ash-col-actions">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						$qry = $conn->query("SELECT *,concat(firstname,' ',lastname) as fullname from `clients` where delete_flag = 0".tenant_sql()." order by concat(firstname,' ',lastname) asc ");
						while($row = $qry->fetch_assoc()):
					?>
						<tr>
							<td>
								<div class="ash-text-bold"><?php echo htmlspecialchars($row['fullname']) ?></div>
								<div class="ash-product-sub text-nowrap"><?php echo date("Y-m-d",strtotime($row['date_created'])) ?></div>
							</td>
							<td><?php echo ash_email_cell($row['email']) ?></td>
							<td class="text-center">
                                <?php echo $row['status'] == 1 ? ash_status_badge('Active', 'active') : ash_status_badge('Inactive', 'inactive') ?>
                            </td>
							<td class="text-center">
								<div class="ash-table-actions">
								<a href="<?php echo base_url ?>admin/?page=clients/view_client&id=<?php echo (int)$row['id'] ?>" class="btn btn-flat btn-sm btn-info" title="View"><i class="fa fa-eye"></i></a>
								<?php if(admin_cashier_can('clients')): ?>
								<?php echo ash_icon_btn('edit', 'Edit', 'edit_data', array('data-id' => $row['id'])) ?>
								<?php if(admin_cashier_can('delete_actions')): ?>
								<?php echo ash_icon_btn('delete', 'Delete', 'delete_data', array('data-id' => $row['id'])) ?>
								<?php endif; ?>
								<?php else: ?>
								<span class="text-muted small">View only</span>
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
			_conf("Are you sure to delete this client permanently?","delete_client",[$(this).attr('data-id')])
		})
		$('.edit_data').click(function(){
			uni_modal("<i class='fa fa-edit'></i> Update Customer","clients/manage_client.php?id="+$(this).attr('data-id'))
		})
		$('#create_new').click(function(){
			uni_modal("<i class='fa fa-plus'></i> Add Customer","clients/manage_client.php")
		})
		$('#list').dataTable({ columnDefs:[{orderable:false,targets:[3]}], order:[[0,'asc']] });
	})
	function delete_client($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_client",
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
