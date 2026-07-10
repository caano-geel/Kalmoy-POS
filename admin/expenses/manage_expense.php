<?php
require_once('./../../config.php');
if(isset($_GET['id'])){
	$id = (int)$_GET['id'];
	$qry = $conn->query("SELECT * FROM expenses WHERE id = '{$id}' AND delete_flag = 0");
	if($qry && $qry->num_rows > 0){
		foreach($qry->fetch_array() as $k => $v){
			if(!is_numeric($k)) $$k = $v;
		}
	}
}
$categories = expense_categories();
$payments = expense_payment_methods();
?>
<div class="container-fluid">
	<form action="" id="expense-form">
		<input type="hidden" name="id" value="<?php echo isset($id) ? (int)$id : '' ?>">
		<div class="form-group">
			<label class="control-label">Expense Date</label>
			<input type="date" name="expense_date" class="form-control form" value="<?php echo isset($expense_date) ? expenses_normalize_date($expense_date) : date('Y-m-d') ?>" required>
		</div>
		<div class="form-group">
			<label class="control-label">Category</label>
			<select name="category" class="custom-select form" required>
				<option value="">Select Category</option>
				<?php foreach($categories as $cat): ?>
				<option value="<?php echo htmlspecialchars($cat) ?>" <?php echo (isset($category) && $category === $cat) ? 'selected' : '' ?>><?php echo htmlspecialchars($cat) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="form-group">
			<label class="control-label">Description</label>
			<textarea name="description" class="form-control form" rows="3" required><?php echo isset($description) ? htmlspecialchars(stripslashes($description)) : '' ?></textarea>
		</div>
		<div class="row">
			<div class="col-md-6 form-group">
				<label class="control-label">Amount (Ksh)</label>
				<input type="number" step="0.01" min="0" name="amount" class="form-control form" value="<?php echo isset($amount) ? $amount : '' ?>" required>
			</div>
			<div class="col-md-6 form-group">
				<label class="control-label">Payment Method</label>
				<select name="payment_method" class="custom-select form" required>
					<?php foreach($payments as $pm): ?>
					<option value="<?php echo htmlspecialchars($pm) ?>" <?php echo (isset($payment_method) && $payment_method === $pm) ? 'selected' : '' ?>><?php echo htmlspecialchars($pm) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
	</form>
</div>
<script>
	$('#expense-form').submit(function(e){
		e.preventDefault();
		start_loader()
		var _this = $(this)
		$('.err-msg').remove();
		$.ajax({
			url:_base_url_+'classes/Master.php?f=save_expense',
			data: new FormData($(this)[0]),
		    cache: false,
		    contentType: false,
		    processData: false,
		    method: 'POST',
		    type: 'POST',
		    dataType: 'json',
			success:function(resp){
				if(typeof resp =='object' && resp.status == 'success'){
					location.reload()
				}else if(resp.status == 'failed' && !!resp.msg){
					var el = $('<div>')
						el.addClass("alert alert-danger err-msg").text(resp.msg)
						_this.prepend(el)
						el.show('slow')
						$("html, body,.modal").scrollTop(0);
						end_loader()
				}else{
					alert_toast("An error occured",'error');
					end_loader();
				}
			}
		})
	})
</script>
