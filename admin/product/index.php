<?php
require_once __DIR__.'/../inc/module_ui.php';
if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">Products</h3>
		<div class="card-tools d-flex align-items-center flex-wrap">
			<?php echo module_export_toolbar('products', array(), 'mr-2'); ?>
			<?php if(admin_cashier_can('products')): ?>
			<a href="?page=product/manage_product" class="btn btn-flat btn-primary btn-sm"><span class="fas fa-plus"></span> Create New</a>
			<?php endif; ?>
		</div>
	</div>
	<div class="card-body">
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
			<table class="table table-hover ash-table" id="product-list">
				<thead>
					<tr>
						<th>Product</th>
						<th class="ash-col-desc">Description</th>
						<th class="text-center">Status</th>
						<th class="text-center ash-col-actions">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$qry = $conn->query("SELECT p.*,b.name as bname from `products` p inner join brands b on p.brand_id = b.id where p.delete_flag = 0".tenant_sql('p')." order by (p.name) asc ");
					while($row = $qry->fetch_assoc()):
						foreach($row as $k=> $v){
							$row[$k] = trim(stripslashes($v));
						}
						$row['specs'] = strip_tags(stripslashes(html_entity_decode($row['specs'])));
					?>
						<tr>
							<td><?php echo ash_product_cell($row['name'], $row['barcode'] ?? '', $row['bname']) ?></td>
							<td class="ash-col-desc"><?php echo ash_truncate_tooltip($row['specs']) ?></td>
							<td class="text-center">
                                <?php echo $row['status'] == 1 ? ash_status_badge('Active', 'active') : ash_status_badge('Inactive', 'inactive') ?>
                            </td>
							<td class="text-center">
								<div class="ash-table-actions">
								<?php if(admin_cashier_can('products')): ?>
								<a href="?page=product/manage_product&id=<?php echo $row['id'] ?>" class="ash-icon-btn ash-icon-btn-edit" title="Edit" data-toggle="tooltip"><i class="fas fa-edit"></i></a>
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
<script src="<?php echo base_url ?>dist/js/html5-qrcode.min.js"></script>
<script>
	$(document).ready(function(){
		$('[data-toggle="tooltip"]').tooltip();
		$('.delete_data').click(function(){
			_conf("Are you sure to delete this product permanently?","delete_product",[$(this).attr('data-id')])
		})
		var listTable = $('#product-list').DataTable({
			columnDefs: [{ orderable: false, targets: [3] }],
			order:[[0,'asc']]
		});
		$('.dataTables_filter label').addClass('mb-0 d-flex align-items-center flex-wrap');
		$('.dataTables_filter input').addClass('mr-2');
		$('.dataTables_filter label').append('<button type="button" class="btn btn-sm btn-info mb-1" id="list-start-barcode-scan"><i class="fa fa-barcode"></i> Scan</button>');

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
	function delete_product($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_product",
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
