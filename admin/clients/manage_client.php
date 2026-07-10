<?php 
require_once('./../../config.php');
if(isset($_GET['id']) && $_GET['id'] !== ''){
	$client_id = (int)$_GET['id'];
	$client = $conn->query("SELECT * FROM clients WHERE id = '{$client_id}' LIMIT 1");
	if($client && $client->num_rows > 0){
		foreach($client->fetch_array() as $k => $v){
			if(!is_numeric($k))
				$$k = $v;
		}
	}
}
$email_display = '';
if(isset($email) && $email !== '' && !preg_match('/@customer\.local$/i', $email)){
	$email_display = $email;
}
?>
<div class="container-fluid">
	<form action="" id="update-client">
		<input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
		<div class="form-group">
			<label for="firstname" class="control-label">First Name <span class="text-danger">*</span></label>
			<input type="text" name="firstname" id="firstname" class="form-control form" value="<?php echo isset($firstname) ? htmlspecialchars($firstname) : '' ?>" required>
		</div>
		<div class="form-group">
			<label for="lastname" class="control-label">Last Name</label>
			<input type="text" name="lastname" id="lastname" class="form-control form" value="<?php echo isset($lastname) ? htmlspecialchars($lastname) : '' ?>">
		</div>
		<div class="form-group">
			<label for="contact" class="control-label">Phone Number <span class="text-danger">*</span></label>
			<input type="text" name="contact" id="contact" class="form-control form" value="<?php echo isset($contact) ? htmlspecialchars($contact) : '' ?>" required>
		</div>
		<div class="form-group">
			<label for="email" class="control-label">Email</label>
			<input type="email" name="email" id="email" class="form-control form" value="<?php echo htmlspecialchars($email_display) ?>">
		</div>
		<div class="form-group">
			<label for="default_delivery_address" class="control-label">Address</label>
			<textarea class="form-control form" rows="2" name="default_delivery_address" id="default_delivery_address"><?php echo isset($default_delivery_address) ? htmlspecialchars($default_delivery_address) : '' ?></textarea>
		</div>
		<div class="form-group mb-0">
			<label for="status" class="control-label">Status</label>
			<select name="status" id="status" class="custom-select select" required>
				<option value="1" <?php echo (!isset($status) || $status == "1") ? "selected" : '' ?>>Active</option>
				<option value="0" <?php echo isset($status) && $status == "0" ? "selected" : '' ?>>Inactive</option>
			</select>
		</div>
	</form>
</div>
<script>
	$('#update-client').submit(function(e){
		e.preventDefault();
		start_loader()
		var _this = $(this)
		$('.err-msg').remove();
		$.ajax({
			url:_base_url_+'classes/Master.php?f=update_client',
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
						$("#uni_modal .modal-body").scrollTop(0);
						end_loader()
				}else{
					alert_toast("An error occured",'error');
					end_loader();
					console.log(resp)
				}
			}
		})
	})

</script>
