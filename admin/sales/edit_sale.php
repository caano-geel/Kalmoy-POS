<?php
require_once('./../../config.php');
if(!sales_can_edit_sale()){
	echo '<div class="alert alert-danger mb-0">Access denied.</div>';
	exit;
}
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if($order_id <= 0){
	echo '<div class="alert alert-danger mb-0">No order specified.</div>';
	exit;
}
$oq = $conn->query("SELECT o.*, s.id AS sale_id
	FROM orders o
	INNER JOIN sales s ON s.order_id = o.id
	WHERE o.id = '{$order_id}' AND o.status = 3
	LIMIT 1");
if(!$oq || $oq->num_rows === 0){
	echo '<div class="alert alert-danger mb-0">Sale not found or cannot be edited.</div>';
	exit;
}
$order = $oq->fetch_assoc();
$has_discount = db_table_has_column('orders', 'discount_total');
$discount_total = $has_discount ? (float)$order['discount_total'] : 0;
$walkin_id = 0;
$wq = $conn->query("SELECT id FROM clients WHERE email = '".$conn->real_escape_string(CustomerDebtService::WALKIN_EMAIL)."' AND delete_flag = 0 LIMIT 1");
if($wq && $wq->num_rows) $walkin_id = (int)$wq->fetch_assoc()['id'];
$is_walkin = ($walkin_id > 0 && (int)$order['client_id'] === $walkin_id);
$walkin_name = '';
if($is_walkin && preg_match('/Customer:\s*(.+)$/i', $order['delivery_address'], $m)){
	$walkin_name = trim($m[1]);
}
$registered_clients = CustomerDebtService::registered_clients($conn);
$can_debt = debt_can_sale();
$cash_part = 0;
$mpesa_part = 0;
if(stripos($order['delivery_address'], 'Mixed:') !== false && preg_match('/Cash\s+([\d,\.]+)\s+\+\s+M-Pesa\s+([\d,\.]+)/i', $order['delivery_address'], $mx)){
	$cash_part = (float)str_replace(',', '', $mx[1]);
	$mpesa_part = (float)str_replace(',', '', $mx[2]);
}
$lines = array();
$lq = $conn->query("SELECT ol.*, p.name, p.barcode, i.variant, b.name AS bname
	FROM order_list ol
	INNER JOIN inventory i ON i.id = ol.inventory_id
	INNER JOIN products p ON p.id = i.product_id
	INNER JOIN brands b ON b.id = p.brand_id
	WHERE ol.order_id = '{$order_id}'
	ORDER BY ol.id ASC");
while($lq && ($row = $lq->fetch_assoc())){
	$lines[] = $row;
}
$subtotal = 0;
foreach($lines as $ln){
	$subtotal += (float)$ln['quantity'] * (float)$ln['price'];
}
?>
<style>
#sale-edit-lines td { vertical-align: middle !important; }
#sale-edit-search-results .sale-edit-result { cursor: pointer; }
#sale-edit-search-results .sale-edit-result:hover { background: #f4f6f9; }
</style>
<div class="container-fluid" id="sale-edit-wrap">
	<form id="sale-edit-form">
		<input type="hidden" name="order_id" value="<?php echo (int)$order_id ?>">
		<div class="row">
			<div class="form-group col-6 col-md-4">
				<label class="control-label">Receipt No.</label>
				<input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($order['ref_code']) ?>" readonly>
			</div>
			<div class="form-group col-6 col-md-4">
				<label class="control-label">Sale Date</label>
				<input type="text" class="form-control form-control-sm" value="<?php echo date('Y-m-d H:i', strtotime($order['date_created'])) ?>" readonly>
			</div>
			<div class="form-group col-12 col-md-4">
				<label for="sale-edit-payment" class="control-label">Payment Method</label>
				<select name="payment_method" id="sale-edit-payment" class="form-control form-control-sm" required>
					<option value="Cash" <?php echo strcasecmp($order['payment_method'], 'Cash') === 0 ? 'selected' : '' ?>>Cash</option>
					<option value="M-Pesa" <?php echo strcasecmp($order['payment_method'], 'M-Pesa') === 0 ? 'selected' : '' ?>>M-Pesa</option>
					<option value="Mixed" <?php echo strcasecmp($order['payment_method'], 'Mixed') === 0 ? 'selected' : '' ?>>Mixed Payment</option>
					<?php if($can_debt): ?>
					<option value="Debt" <?php echo strcasecmp($order['payment_method'], 'Debt') === 0 ? 'selected' : '' ?>>Debt (Credit)</option>
					<?php endif; ?>
				</select>
			</div>
		</div>
		<div class="row">
			<div class="form-group col-12 col-md-6" id="sale-edit-walkin-wrap" style="<?php echo $is_walkin ? '' : 'display:none' ?>">
				<label for="sale-edit-customer-name">Walk-in Name <small class="text-muted">(optional)</small></label>
				<input type="text" id="sale-edit-customer-name" class="form-control form-control-sm" value="<?php echo htmlspecialchars($walkin_name) ?>">
			</div>
			<div class="form-group col-12 col-md-6">
				<label for="sale-edit-client-id">Customer <span id="sale-edit-client-req" class="text-danger" style="display:none">*</span></label>
				<select id="sale-edit-client-id" class="form-control form-control-sm">
					<option value="">— Walk-in —</option>
					<?php foreach($registered_clients as $pc): ?>
					<option value="<?php echo (int)$pc['id'] ?>" <?php echo !$is_walkin && (int)$order['client_id'] === (int)$pc['id'] ? 'selected' : '' ?>><?php echo htmlspecialchars($pc['fullname'].' — '.$pc['contact']) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<div class="row" id="sale-edit-mixed-wrap" style="display:none">
			<div class="form-group col-6">
				<label for="sale-edit-cash">Cash Amount</label>
				<input type="number" id="sale-edit-cash" class="form-control form-control-sm" min="0" step="0.01" value="<?php echo $cash_part ?>">
			</div>
			<div class="form-group col-6">
				<label for="sale-edit-mpesa">M-Pesa Amount</label>
				<input type="number" id="sale-edit-mpesa" class="form-control form-control-sm" min="0" step="0.01" value="<?php echo $mpesa_part ?>">
			</div>
		</div>
		<?php if($has_discount): ?>
		<div class="row">
			<div class="form-group col-6 col-md-3">
				<label for="sale-edit-discount-pct">Discount (%)</label>
				<input type="number" id="sale-edit-discount-pct" class="form-control form-control-sm" value="0" min="0" max="100" step="0.01">
			</div>
			<div class="form-group col-6 col-md-3">
				<label for="sale-edit-discount-ksh">Discount (Ksh)</label>
				<input type="number" id="sale-edit-discount-ksh" class="form-control form-control-sm" value="<?php echo $discount_total > 0 ? $discount_total : 0 ?>" min="0" step="0.01">
			</div>
		</div>
		<?php endif; ?>
		<hr class="my-2">
		<div class="form-group mb-2">
			<label>Add Product</label>
			<div class="input-group input-group-sm">
				<input type="text" id="sale-edit-search" class="form-control" placeholder="Barcode or product name..." autocomplete="off">
				<div class="input-group-append">
					<button type="button" class="btn btn-default" id="sale-edit-search-btn"><i class="fa fa-search"></i></button>
				</div>
			</div>
		</div>
		<div id="sale-edit-search-results" class="border rounded mb-2" style="max-height:140px;overflow-y:auto;display:none"></div>
		<div class="table-responsive">
			<table class="table table-sm table-bordered mb-2" id="sale-edit-lines">
				<thead>
					<tr>
						<th>Product</th>
						<th width="90">Unit Price</th>
						<th width="70">Qty</th>
						<th width="90">Line Total</th>
						<th width="36"></th>
					</tr>
				</thead>
				<tbody id="sale-edit-lines-body">
					<?php foreach($lines as $ln): ?>
					<tr data-line-id="<?php echo (int)$ln['id'] ?>" data-inventory-id="<?php echo (int)$ln['inventory_id'] ?>" data-stock="0">
						<td>
							<span class="line-name"><?php echo htmlspecialchars(stripslashes($ln['name'].' — '.$ln['variant'])) ?></span>
							<small class="d-block text-muted"><?php echo htmlspecialchars($ln['bname']) ?></small>
						</td>
						<td><input type="number" class="form-control form-control-sm line-price" min="0" step="0.01" value="<?php echo (float)$ln['price'] ?>"></td>
						<td><input type="number" class="form-control form-control-sm line-qty" min="1" step="1" value="<?php echo (int)$ln['quantity'] ?>"></td>
						<td class="text-right line-total align-middle"><?php echo format_price((float)$ln['price'] * (int)$ln['quantity']) ?></td>
						<td class="text-center align-middle"><button type="button" class="btn btn-sm btn-outline-danger line-remove" title="Remove"><i class="fa fa-times"></i></button></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<tr><th colspan="3" class="text-right">Subtotal</th><th colspan="2" id="sale-edit-subtotal"><?php echo format_price($subtotal) ?></th></tr>
					<?php if($has_discount): ?>
					<tr><th colspan="3" class="text-right">Discount</th><th colspan="2" id="sale-edit-discount-display"><?php echo format_price($discount_total) ?></th></tr>
					<?php endif; ?>
					<tr><th colspan="3" class="text-right">Order Total</th><th colspan="2" id="sale-edit-total"><?php echo format_price((float)$order['amount']) ?></th></tr>
				</tfoot>
			</table>
		</div>
	</form>
	<div class="d-flex justify-content-between align-items-center mt-2">
		<button type="button" class="btn btn-sm btn-outline-danger" id="sale-edit-delete-order"><i class="fa fa-trash"></i> Delete Sale</button>
		<div>
			<button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
			<button type="button" class="btn btn-sm btn-primary" id="sale-edit-save"><i class="fa fa-save"></i> Save Changes</button>
		</div>
	</div>
</div>
<script>
(function(){
	var orderId = <?php echo (int)$order_id ?>;
	var hasDiscount = <?php echo $has_discount ? 'true' : 'false' ?>;
	var walkinId = <?php echo (int)$walkin_id ?>;
	var initialSubtotal = <?php echo json_encode(round($subtotal, 2)) ?>;
	var initialDiscount = <?php echo json_encode(round($discount_total, 2)) ?>;

	$('#uni_modal .modal-footer').hide();
	$('#uni_modal .modal-dialog').removeClass('modal-md').addClass('modal-lg');

	function fmtMoney(n){
		n = parseFloat(n) || 0;
		return 'Ksh ' + n.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
	}
	function activeLineCount(){
		return $('#sale-edit-lines-body tr:not(.removed)').length;
	}
	function recalcTotals(){
		var subtotal = 0;
		$('#sale-edit-lines-body tr:not(.removed)').each(function(){
			var price = parseFloat($(this).find('.line-price').val()) || 0;
			var qty = parseInt($(this).find('.line-qty').val(), 10) || 0;
			if(qty < 1) qty = 1;
			var line = price * qty;
			subtotal += line;
			$(this).find('.line-total').text(fmtMoney(line));
		});
		var discPct = hasDiscount ? (parseFloat($('#sale-edit-discount-pct').val()) || 0) : 0;
		var discKsh = hasDiscount ? (parseFloat($('#sale-edit-discount-ksh').val()) || 0) : 0;
		if(discPct < 0) discPct = 0;
		if(discPct > 100) discPct = 100;
		if(discKsh < 0) discKsh = 0;
		var discount = Math.min(subtotal, (subtotal * discPct / 100) + discKsh);
		var total = Math.max(0, subtotal - discount);
		$('#sale-edit-subtotal').text(fmtMoney(subtotal));
		if(hasDiscount) $('#sale-edit-discount-display').text(fmtMoney(discount));
		$('#sale-edit-total').text(fmtMoney(total));
		return { subtotal: subtotal, discount: discount, total: total };
	}
	function togglePaymentUi(){
		var pm = $('#sale-edit-payment').val();
		var isDebt = pm === 'Debt';
		var isMixed = pm === 'Mixed';
		$('#sale-edit-mixed-wrap').toggle(isMixed);
		$('#sale-edit-client-req').toggle(isDebt);
		if(isDebt){
			$('#sale-edit-walkin-wrap').hide();
		}else{
			var cid = $('#sale-edit-client-id').val();
			$('#sale-edit-walkin-wrap').toggle(!cid || cid === '');
		}
	}
	function collectItems(){
		var items = [];
		$('#sale-edit-lines-body tr').each(function(){
			var $tr = $(this);
			if($tr.hasClass('removed')){
				var rid = parseInt($tr.data('line-id'), 10);
				if(rid > 0){
					items.push({ order_list_id: rid, remove: true });
				}
				return;
			}
			var lineId = parseInt($tr.data('line-id'), 10) || 0;
			var invId = parseInt($tr.data('inventory-id'), 10);
			var qty = parseInt($tr.find('.line-qty').val(), 10);
			var price = parseFloat($tr.find('.line-price').val());
			items.push({
				order_list_id: lineId,
				inventory_id: invId,
				quantity: qty,
				price: price
			});
		});
		return items;
	}
	function addLine(item){
		var html = '<tr data-line-id="0" data-inventory-id="'+item.inventory_id+'" data-stock="'+item.stock+'">'+
			'<td><span class="line-name">'+$('<div>').text(item.name+' — '+item.variant).html()+'</span>'+
			'<small class="d-block text-muted">'+$('<div>').text(item.bname).html()+'</small></td>'+
			'<td><input type="number" class="form-control form-control-sm line-price" min="0" step="0.01" value="'+item.price+'"></td>'+
			'<td><input type="number" class="form-control form-control-sm line-qty" min="1" step="1" value="1"></td>'+
			'<td class="text-right line-total align-middle">'+fmtMoney(item.price)+'</td>'+
			'<td class="text-center align-middle"><button type="button" class="btn btn-sm btn-outline-danger line-remove"><i class="fa fa-times"></i></button></td></tr>';
		$('#sale-edit-lines-body').append(html);
		recalcTotals();
	}
	$('#sale-edit-payment').on('change', togglePaymentUi);
	$('#sale-edit-client-id').on('change', togglePaymentUi);
	$('#sale-edit-lines-body').on('input', '.line-price, .line-qty', recalcTotals);
	if(hasDiscount){
		$('#sale-edit-discount-pct, #sale-edit-discount-ksh').on('input', recalcTotals);
	}
	$('#sale-edit-lines-body').on('click', '.line-remove', function(){
		if(activeLineCount() <= 1){
			ashAlert('At least one line item is required.', 'warning');
			return;
		}
		var $tr = $(this).closest('tr');
		if(parseInt($tr.data('line-id'), 10) > 0){
			$tr.addClass('removed').hide();
		}else{
			$tr.remove();
		}
		recalcTotals();
	});
	function doSearch(){
		var q = $('#sale-edit-search').val().trim();
		if(!q) return;
		$.ajax({
			url: _base_url_+'classes/Master.php?f=sales_search_product',
			method: 'POST',
			data: { q: q, order_id: orderId },
			dataType: 'json',
			error: function(){ ashAlert('Search failed.', 'error'); },
			success: function(resp){
				if(resp.status !== 'success' || !resp.items || !resp.items.length){
					$('#sale-edit-search-results').html('<div class="text-muted text-center py-2 small">No products found</div>').show();
					return;
				}
				var html = '';
				resp.items.forEach(function(it){
					html += '<div class="sale-edit-result border-bottom px-2 py-1" data-item="'+encodeURIComponent(JSON.stringify(it))+'">'+
						'<strong>'+$('<div>').text(it.name+' — '+it.variant).html()+'</strong>'+
						'<span class="float-right">'+fmtMoney(it.price)+' · Stock: '+it.stock+'</span></div>';
				});
				$('#sale-edit-search-results').html(html).show();
			}
		});
	}
	$('#sale-edit-search-btn').on('click', doSearch);
	$('#sale-edit-search').on('keypress', function(e){ if(e.which === 13){ e.preventDefault(); doSearch(); }});
	$('#sale-edit-search-results').on('click', '.sale-edit-result', function(){
		var item = JSON.parse(decodeURIComponent($(this).data('item')));
		if(item.stock < 1){
			ashAlert('Product is out of stock.', 'warning');
			return;
		}
		addLine(item);
		$('#sale-edit-search-results').hide().empty();
		$('#sale-edit-search').val('');
	});
	$('#sale-edit-save').on('click', function(){
		var totals = recalcTotals();
		var items = collectItems();
		if(!items.filter(function(i){ return !i.remove; }).length){
			ashAlert('At least one line item is required.', 'warning');
			return;
		}
		for(var i = 0; i < items.length; i++){
			var it = items[i];
			if(it.remove) continue;
			if(it.quantity <= 0){
				ashAlert('Quantity must be greater than zero.', 'warning');
				return;
			}
			if(it.price < 0){
				ashAlert('Unit price cannot be negative.', 'warning');
				return;
			}
		}
		var pm = $('#sale-edit-payment').val();
		var clientId = $('#sale-edit-client-id').val() || '';
		if(pm === 'Debt' && !clientId){
			ashAlert('Select a registered customer for credit sales.', 'warning');
			return;
		}
		var payload = {
			order_id: orderId,
			payment_method: pm,
			client_id: clientId,
			customer_name: $('#sale-edit-customer-name').val(),
			items: JSON.stringify(items),
			amount: totals.total,
			discount_percent: hasDiscount ? ($('#sale-edit-discount-pct').val() || 0) : 0,
			discount_ksh: hasDiscount ? ($('#sale-edit-discount-ksh').val() || 0) : 0,
			cash_amount: $('#sale-edit-cash').val() || 0,
			mpesa_amount: $('#sale-edit-mpesa').val() || 0
		};
		start_loader();
		$.ajax({
			url: _base_url_+'classes/Master.php?f=sales_update_order',
			method: 'POST',
			data: payload,
			dataType: 'json',
			error: function(){ end_loader(); ashAlert('Unable to save sale.', 'error'); },
			success: function(resp){
				end_loader();
				if(resp.status === 'success'){
					$('#uni_modal').modal('hide');
					ashAlert(resp.msg || 'Sale updated successfully.', 'success', 'Success', function(){ location.reload(); });
				}else{
					ashAlert(resp.msg || 'Failed to update sale.', 'error');
				}
			}
		});
	});
	$('#sale-edit-delete-order').on('click', function(){
		ashConfirm('Delete this sale permanently? Stock will be restored and debt records voided.', function(){
			start_loader();
			$.ajax({
				url: _base_url_+'classes/Master.php?f=delete_order',
				method: 'POST',
				data: { id: orderId },
				dataType: 'json',
				error: function(){ end_loader(); ashAlert('Unable to delete sale.', 'error'); },
				success: function(resp){
					end_loader();
					if(resp.status === 'success'){
						$('#uni_modal').modal('hide');
						ashAlert('Sale deleted successfully.', 'success', 'Success', function(){ location.reload(); });
					}else{
						ashAlert(resp.err || resp.msg || 'Failed to delete sale.', 'error');
					}
				}
			});
		}, { title: 'Delete Sale' });
	});
	if(hasDiscount && initialSubtotal > 0 && initialDiscount > 0){
		var pct = (initialDiscount / initialSubtotal) * 100;
		if(Math.abs(pct - Math.round(pct * 100) / 100) < 0.02 && pct <= 100){
			$('#sale-edit-discount-pct').val(pct.toFixed(2));
			$('#sale-edit-discount-ksh').val(0);
		}
	}
	togglePaymentUi();
	recalcTotals();
})();
</script>
