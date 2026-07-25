<?php
require_once __DIR__.'/../inc/module_ui.php';
if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<style>
.import-row-highlight td {
    background-color: #d4edda !important;
    box-shadow: inset 0 0 0 1px #28a745;
    transition: background-color 0.4s ease;
}
</style>
<?php
$stock_filter = isset($_GET['stock_filter']) ? trim($_GET['stock_filter']) : '';
if(!in_array($stock_filter, array('', 'low', 'out'), true)){
    $stock_filter = '';
}
$low_threshold = inventory_low_stock_threshold();
$avail_expr = inventory_available_stock_sql('i');
$sold_sub = inventory_sold_subquery_sql();
$having_sql = '';
if($stock_filter === 'out'){
    $having_sql = " HAVING avail <= 0 ";
}elseif($stock_filter === 'low'){
    $having_sql = " HAVING avail > 0 AND avail <= {$low_threshold} ";
}
$inventory_action_col = (admin_can_view_profit() ? 5 : 4);
$inventory_stock_col = admin_can_view_profit() ? 3 : 2;
?>
<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">Stock &amp; Inventory</h3>
		<div class="card-tools d-flex align-items-center flex-wrap">
			<?php echo module_export_toolbar('inventory', array(), 'mr-2 mb-1'); ?>
			<form method="get" action="" class="form-inline mr-2 mb-1 mb-md-0">
				<input type="hidden" name="page" value="inventory">
				<label for="stock_filter" class="mr-2 mb-0 small text-muted">Stock Status</label>
				<select name="stock_filter" id="stock_filter" class="form-control form-control-sm" onchange="this.form.submit()">
					<option value="" <?php echo $stock_filter === '' ? 'selected' : '' ?>>All</option>
					<option value="low" <?php echo $stock_filter === 'low' ? 'selected' : '' ?>>Low Stock</option>
					<option value="out" <?php echo $stock_filter === 'out' ? 'selected' : '' ?>>Out of Stock</option>
				</select>
			</form>
		<?php if(admin_cashier_can('inventory_manage')): ?>
			<button type="button" class="btn btn-flat btn-success mb-1 mb-md-0 mr-1" id="inventory-import-excel-btn"><span class="fas fa-file-excel"></span>  Import Excel</button>
			<a href="?page=inventory/manage_inventory" class="btn btn-flat btn-primary mb-1 mb-md-0"><span class="fas fa-plus"></span>  Create New</a>
		<?php endif; ?>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
        <div class="container-fluid">
			<div id="list-barcode-scanner-wrap" class="mb-2" style="display:none;">
				<div id="list-barcode-camera-select-wrap" class="mb-2" style="display:none; max-width:420px;">
					<label for="list-barcode-camera-select" class="small mb-1 d-block">Select Camera</label>
					<select id="list-barcode-camera-select" class="form-control form-control-sm"></select>
				</div>
				<p class="small text-muted mb-2">Hold barcode straight, close, and well-lit.</p>
				<div id="list-barcode-scanner-reader" style="max-width:420px;"></div>
				<button type="button" class="btn btn-sm btn-secondary mt-2" id="list-stop-barcode-scan">Stop Scan</button>
			</div>
			<div class="ash-table-wrap">
			<table class="table table-hover ash-table" id="inventory-list">
				<thead>
					<tr>
						<th>Product</th>
						<th class="text-right ash-col-money">Price</th>
						<?php if(admin_can_view_profit()): ?><th class="text-right ash-col-money">Cost</th><?php endif; ?>
						<th class="text-center">Stock</th>
						<th class="text-center">Status</th>
						<?php if(admin_cashier_can('inventory_manage')): ?><th class="text-center ash-col-actions">Actions</th><?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<?php 
					$inventory_sql = "SELECT i.*, p.name AS product, p.barcode, b.name AS bname, {$avail_expr} AS avail
						FROM inventory i
						INNER JOIN products p ON p.id = i.product_id
						INNER JOIN brands b ON p.brand_id = b.id
						LEFT JOIN {$sold_sub} sold ON sold.inventory_id = i.id
						WHERE p.delete_flag = 0 AND p.status = 1".tenant_sql('i')."
						{$having_sql}
						ORDER BY avail ASC, unix_timestamp(i.date_created) DESC";
					$qry = $conn->query($inventory_sql);
					if($qry):
						while($row = $qry->fetch_assoc()):
						$avail = (float)$row['avail'];
						$stock_status = inventory_stock_status($avail, $low_threshold);
						foreach($row as $k=> $v){
							$row[$k] = trim(stripslashes((string)($v ?? '')));
						}
					?>
						<tr>
							<td><?php echo ash_inventory_product_cell($row['product'], $row['barcode'] ?? '', $row['bname'], $row['variant']) ?></td>
							<td class="text-right"><?php echo ash_format_money_cell($row['price'], 'neutral') ?></td>
							<?php if(admin_can_view_profit()): ?>
							<td class="text-right"><?php echo (isset($row['cost_price']) && $row['cost_price'] !== '' && (float)$row['cost_price'] > 0) ? ash_format_money_cell($row['cost_price'], 'neutral') : '&mdash;' ?></td>
							<?php endif; ?>
							<td class="text-center font-weight-bold"><?php echo format_num($avail) ?></td>
							<td class="text-center"><?php echo inventory_stock_status_badge($stock_status) ?></td>
							<?php if(admin_cashier_can('inventory_manage')): ?>
							<td class="text-center">
								<div class="ash-table-actions">
								<a href="?page=inventory/manage_inventory&id=<?php echo $row['id'] ?>" class="ash-icon-btn ash-icon-btn-edit" title="Edit" data-toggle="tooltip"><i class="fas fa-edit"></i></a>
								<?php if(admin_cashier_can('delete_actions')): ?>
								<?php echo ash_icon_btn('delete', 'Delete', 'delete_data', array('data-id' => $row['id'])) ?>
								<?php endif; ?>
								</div>
							</td>
							<?php endif; ?>
						</tr>
					<?php endwhile; endif; ?>
				</tbody>
			</table>
			</div>
		</div>
		</div>
	</div>
</div>
<script src="<?php echo base_url ?>dist/js/html5-qrcode.min.js"></script>
<script>
	$(document).ready(function(){
		$('[data-toggle="tooltip"]').tooltip();
		(function applyImportHighlight(){
			var raw = sessionStorage.getItem('inventory_import_highlight');
			if(!raw) return;
			sessionStorage.removeItem('inventory_import_highlight');
			var data;
			try { data = JSON.parse(raw); } catch(e) { return; }
			if(!data || !data.names || !data.names.length) return;
			var names = data.names.map(function(n){ return String(n).toLowerCase().trim(); });
			var barcodes = (data.barcodes || []).map(function(b){ return String(b).toLowerCase().trim(); });
			var $matched = $();
			$('#inventory-list tbody tr').each(function(){
				var cellText = $(this).find('td:first').text().toLowerCase();
				var match = false;
				names.forEach(function(n){ if(n && cellText.indexOf(n) !== -1) match = true; });
				if(!match){
					barcodes.forEach(function(b){ if(b && cellText.indexOf(b) !== -1) match = true; });
				}
				if(match){
					$(this).addClass('import-row-highlight');
					$matched = $matched.add(this);
				}
			});
			if($matched.length){
				var scrollTarget = $matched.first()[0];
				if(scrollTarget && scrollTarget.scrollIntoView){
					setTimeout(function(){
						scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
					}, 400);
				}
				if($matched.length === 1){
					$matched.addClass('import-row-highlight-single');
				}
			}
			setTimeout(function(){
				$('#inventory-list tbody tr.import-row-highlight').removeClass('import-row-highlight import-row-highlight-single');
			}, 3000);
		})();
		$('#inventory-import-excel-btn').on('click', function(){
			start_loader();
			$.ajax({
				url: 'inventory/import.php',
				error: function(){
					alert_toast('Could not open import dialog.','error');
					end_loader();
				},
				success: function(resp){
					if(!resp) return;
					$('#uni_modal .modal-title').html("<i class='fas fa-file-excel'></i> Import Excel");
					$('#uni_modal .modal-body').html(resp);
					$('#uni_modal .modal-dialog').removeAttr('class').addClass('modal-dialog modal-xl modal-dialog-centered');
					$('#uni_modal').modal({
						show: true,
						backdrop: true,
						keyboard: true
					});
					end_loader();
				}
			});
		});
		$('.delete_data').click(function(){
			_conf("Are you sure to delete this inventory permanently?","delete_inventory",[$(this).attr('data-id')])
		})
		var listTable = $('#inventory-list').DataTable({
			columnDefs: [
					<?php if(admin_cashier_can('inventory_manage')): ?>{ orderable: false, targets: [<?php echo $inventory_action_col ?>] }<?php endif; ?>
			],
			order:[[<?php echo $inventory_stock_col ?>, 'asc']]
		});

		$('.dataTables_filter label').addClass('mb-0 d-flex align-items-center flex-wrap');
		$('.dataTables_filter input').addClass('mr-2');
		$('.dataTables_filter label').append('<button type="button" class="btn btn-sm btn-info mb-1" id="list-start-barcode-scan"><i class="fa fa-barcode"></i> Scan Barcode</button>');

		var listBarcodeScanner = null;
		var listBarcodeScanning = false;
		var listAvailableCameras = [];

		function resetListBarcodeCameraSelect(){
			$('#list-barcode-camera-select-wrap').hide();
			$('#list-barcode-camera-select').empty();
			listAvailableCameras = [];
		}
		function getPreferredCameraId(cameras){
			for(var i = 0; i < cameras.length; i++){
				var label = (cameras[i].label || '').toLowerCase();
				if(label.indexOf('irium') !== -1 || label.indexOf('iriun') !== -1 || label.indexOf('external') !== -1 || label.indexOf('usb') !== -1){
					return cameras[i].id;
				}
			}
			return cameras[cameras.length - 1].id;
		}
		function populateListCameraSelect(cameras){
			var $select = $('#list-barcode-camera-select');
			$select.empty();
			cameras.forEach(function(camera, index){
				var label = camera.label && camera.label.trim() ? camera.label : ('Camera ' + (index + 1));
				$select.append($('<option>', { value: camera.id, text: label }));
			});
			if(cameras.length > 1){
				$('#list-barcode-camera-select-wrap').show();
				$select.val(getPreferredCameraId(cameras));
			}else{
				$('#list-barcode-camera-select-wrap').hide();
			}
		}
		function stopListBarcodeScan(showMsg){
			var finish = function(){
				listBarcodeScanning = false;
				listBarcodeScanner = null;
				$('#list-barcode-scanner-wrap').hide();
				resetListBarcodeCameraSelect();
				if(showMsg) alert_toast('Barcode scanner stopped','info');
			};
			if(listBarcodeScanner && listBarcodeScanning){
				listBarcodeScanner.stop().then(function(){ listBarcodeScanner.clear(); }).catch(function(){}).finally(finish);
			}else{
				finish();
			}
		}
		function onListBarcodeScanned(decodedText){
			playScannerSound();
			listTable.search(decodedText).draw();
			stopListBarcodeScan(false);
		}
		function startListBarcodeScan(cameraId){
			var formatsToSupport = [
				Html5QrcodeSupportedFormats.CODE_128,
				Html5QrcodeSupportedFormats.EAN_13,
				Html5QrcodeSupportedFormats.EAN_8,
				Html5QrcodeSupportedFormats.UPC_A,
				Html5QrcodeSupportedFormats.UPC_E
			];
			listBarcodeScanner = new Html5Qrcode('list-barcode-scanner-reader', { formatsToSupport: formatsToSupport, verbose: false });
			return listBarcodeScanner.start(cameraId, { fps: 30, qrbox: { width: 420, height: 160 } }, onListBarcodeScanned, function(){});
		}

		$('#list-start-barcode-scan').click(function(){
			if(typeof Html5Qrcode === 'undefined'){
				alert_toast('Barcode scanner library not loaded','error');
				return;
			}
			$('#list-barcode-scanner-wrap').show();
			Html5Qrcode.getCameras().then(function(cameras){
				if(!cameras || cameras.length === 0){
					alert_toast('No camera found','error');
					return;
				}
				listAvailableCameras = cameras;
				populateListCameraSelect(cameras);
				var cameraId = cameras.length > 1 ? getPreferredCameraId(cameras) : cameras[0].id;
				return startListBarcodeScan(cameraId);
			}).then(function(){
				listBarcodeScanning = true;
			}).catch(function(err){
				alert_toast('Could not start scanner','error');
				console.log(err);
			});
		});
		$('#list-stop-barcode-scan').click(function(){ stopListBarcodeScan(true); });
		$('#list-barcode-camera-select').change(function(){
			if(!$('#list-barcode-scanner-wrap').is(':visible')) return;
			var cameraId = $(this).val();
			if(listBarcodeScanner && listBarcodeScanning){
				listBarcodeScanner.stop().then(function(){
					listBarcodeScanner.clear();
					listBarcodeScanning = false;
					return startListBarcodeScan(cameraId);
				}).then(function(){ listBarcodeScanning = true; });
			}
		});

	})
	function delete_inventory($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_inventory",
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