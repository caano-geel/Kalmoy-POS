<?php
require_once __DIR__.'/../inc/module_ui.php';
if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<?php if($_settings->chk_flashdata('error')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('error') ?>",'error')
</script>
<?php endif;?>
<?php
$order_status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
if($order_status_filter !== '' && !in_array($order_status_filter, array('0','1','2','3','4'), true)){
    $order_status_filter = '';
}
$order_status_sql = $order_status_filter !== '' ? " WHERE o.status = '".(int)$order_status_filter."' " : '';
function ash_order_status_badge($status){
    switch((string)$status){
        case '0': return ash_status_badge('Open', 'open');
        case '1': return ash_status_badge('Packed', 'packed');
        case '2': return ash_status_badge('Out for Delivery', 'delivery');
        case '3': return ash_status_badge('Delivered', 'delivered');
        default: return ash_status_badge('Cancelled', 'cancelled');
    }
}
?>
<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">Orders</h3>
		<div class="card-tools d-flex align-items-center flex-wrap">
			<?php echo module_export_toolbar('orders', $order_status_filter !== '' ? array('status' => $order_status_filter) : array(), 'mr-2'); ?>
			<form method="get" action="" class="form-inline">
				<input type="hidden" name="page" value="orders">
				<label for="order_status_filter" class="mr-2 mb-0 small text-muted">Status</label>
				<select name="status" id="order_status_filter" class="form-control form-control-sm" onchange="this.form.submit()">
					<option value="" <?php echo $order_status_filter === '' ? 'selected' : '' ?>>All</option>
					<option value="0" <?php echo $order_status_filter === '0' ? 'selected' : '' ?>>Open</option>
					<option value="1" <?php echo $order_status_filter === '1' ? 'selected' : '' ?>>Packed</option>
					<option value="2" <?php echo $order_status_filter === '2' ? 'selected' : '' ?>>Out for Delivery</option>
					<option value="3" <?php echo $order_status_filter === '3' ? 'selected' : '' ?>>Delivered</option>
					<option value="4" <?php echo $order_status_filter === '4' ? 'selected' : '' ?>>Cancelled</option>
				</select>
			</form>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
			<div class="ash-table-wrap">
			<table class="table table-hover ash-table" id="orders-list">
				<thead>
					<tr>
						<th>Date</th>
						<th>Customer</th>
						<th class="text-right ash-col-money">Amount</th>
						<th class="text-center">Paid</th>
						<th class="text-center">Status</th>
						<th class="text-center ash-col-actions">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						$qry = $conn->query("SELECT o.*,concat(c.firstname,' ',c.lastname) as client from `orders` o inner join clients c on c.id = o.client_id {$order_status_sql}".tenant_sql('o')." order by unix_timestamp(o.date_created) desc ");
						while($row = $qry->fetch_assoc()):
					?>
						<tr>
							<td class="text-nowrap small"><?php echo date("Y-m-d H:i",strtotime($row['date_created'])) ?></td>
							<td><span class="ash-text-bold"><?php echo htmlspecialchars($row['client']) ?></span></td>
							<td class="text-right"><?php echo ash_format_money_cell($row['amount'], 'revenue') ?></td>
							<td class="text-center">
                                <?php echo $row['paid'] == 0 ? ash_status_badge('No', 'paid_no') : ash_status_badge('Yes', 'paid_yes') ?>
                            </td>
							<td class="text-center"><?php echo ash_order_status_badge($row['status']) ?></td>
							<td class="text-center">
								<div class="ash-table-actions">
								<?php echo ash_icon_link('?page=orders/view_order&id='.$row['id'], 'view', 'View Order') ?>
								<?php if(admin_cashier_can('orders_manage')): ?>
								<?php if($row['paid'] == 0 && $row['status'] != 4): ?>
								<?php echo ash_icon_btn('pay', 'Mark as Paid', 'pay_order', array('data-id' => $row['id'])) ?>
								<?php endif; ?>
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
			_conf("Are you sure to delete this order permanently?","delete_order",[$(this).attr('data-id')])
		})
		$('.pay_order').click(function(){
			_conf("Are you sure to mark this order as paid?","pay_order",[$(this).attr('data-id')])
		})
		$('#orders-list').dataTable({ order:[[0,'desc']], columnDefs:[{orderable:false,targets:[5]}] });
	})
	function pay_order($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=pay_order",
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
	function delete_order($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_order",
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
