<?php
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT * from `products` where id = '{$_GET['id']}' ");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=stripslashes($v);
        }
    }
}
?>
<div class="card card-outline card-info">
	<div class="card-header">
		<h3 class="card-title"><?php echo isset($id) ? "Update ": "Create New " ?> Product</h3>
	</div>
	<div class="card-body">
		<form action="" id="product-form">
			<input type="hidden" name ="id" value="<?php echo isset($id) ? $id : '' ?>">
            <div class="form-group">
				<label for="brand_id" class="control-label">Brand</label>
                <select name="brand_id" id="brand_id" class="custom-select select2" required>
                <option value=""></option>
                <?php
                    $qry = $conn->query("SELECT * FROM `brands` where delete_flag = 0 ".(isset($brand_id) ? " or id = '{$brand_id}' ": "")." order by `name` asc");
                    while($row= $qry->fetch_assoc()):
                ?>
                <option value="<?php echo $row['id'] ?>" <?php echo isset($brand_id) && $brand_id == $row['id'] ? 'selected' : '' ?>><?php echo $row['name'] ?></option>
                <?php endwhile; ?>
                </select>
			</div>
            <div class="form-group">
				<label for="category_id" class="control-label">Category</label>
                <select name="category_id" id="category_id" class="custom-select select2" required>
                <option value=""></option>
                <?php
                    $qry = $conn->query("SELECT * FROM `categories` where delete_flag = 0 ".(isset($category_id) ? " or id = '{$category_id}' ": "")." order by category asc");
                    while($row= $qry->fetch_assoc()):
                ?>
                <option value="<?php echo $row['id'] ?>" <?php echo isset($category_id) && $category_id == $row['id'] ? 'selected' : '' ?>><?php echo $row['category'] ?></option>
                <?php endwhile; ?>
                </select>
			</div>
			<div class="form-group">
				<label for="name" class="control-label">Product Name</label>
                <input type="text" name="name" id="name" class="form-control rounded-0" required value="<?php echo isset($name) ?$name : '' ?>" />
			</div>
            <div class="form-group">
				<label for="barcode" class="control-label">Barcode</label>
                <div class="input-group">
                    <input type="text" name="barcode" id="barcode" class="form-control rounded-0" value="<?php echo isset($barcode) ? $barcode : '' ?>" maxlength="100" autocomplete="off" />
                    <div class="input-group-append">
                        <button type="button" class="btn btn-default" id="start-barcode-scan">Scan Barcode</button>
                    </div>
                </div>
                <div id="barcode-scanner-wrap" class="mt-2" style="display:none;">
                    <div id="barcode-camera-select-wrap" class="mb-2" style="display:none; max-width:420px;">
                        <label for="barcode-camera-select" class="small mb-1 d-block">Select Camera</label>
                        <select id="barcode-camera-select" class="form-control form-control-sm rounded-0"></select>
                    </div>
                    <p class="small text-muted mb-2">Hold barcode straight, close, and well-lit.</p>
                    <div id="barcode-scanner-reader" style="max-width:420px;"></div>
                    <button type="button" class="btn btn-sm btn-secondary mt-2" id="stop-barcode-scan">Stop Scan</button>
                </div>
			</div>
            <div class="form-group">
				<label for="specs" class="control-label">Description</label>
                <textarea name="specs" id="" cols="30" rows="2" class="form-control form no-resize summernote"><?php echo isset($specs) ? $specs : ''; ?></textarea>
			</div>
            <div class="form-group">
				<label for="status" class="control-label">Status</label>
                <select name="status" id="status" class="custom-select selevt">
                <option value="1" <?php echo isset($status) && $status == 1 ? 'selected' : '' ?>>Active</option>
                <option value="0" <?php echo isset($status) && $status == 0 ? 'selected' : '' ?>>Inactive</option>
                </select>
			</div>
            <div class="form-group">
				<label for="" class="control-label">Images</label>
				<div class="custom-file">
	              <input type="file" class="custom-file-input rounded-circle" id="customFile" name="img[]" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" onchange="displayImg(this,$(this))">
	              <label class="custom-file-label" for="customFile">Choose file</label>
	            </div>
                <small class="text-muted d-block mt-1">Allowed formats: JPG, JPEG, PNG, WEBP. Recommended size: 800x800 px or higher.</small>
                <div id="img-upload-msg"></div>
			</div>
            <?php 
            if(isset($id)):
            $upload_path = "uploads/product_".$id;
            if(is_dir(base_app.$upload_path)): 
            ?>
            <?php 
            
                $file= scandir(base_app.$upload_path);
                foreach($file as $img):
                    if(in_array($img,array('.','..')))
                        continue;
                    
                
            ?>
                <div class="d-flex w-100 align-items-center img-item">
                    <span><img src="<?php echo base_url.$upload_path.'/'.$img ?>" width="150px" height="100px" style="object-fit:contain;background-color:#fff;" class="img-thumbnail" alt=""></span>
                    <span class="ml-4"><button class="btn btn-sm btn-default text-danger rem_img" type="button" data-path="<?php echo base_app.$upload_path.'/'.$img ?>"><i class="fa fa-trash"></i></button></span>
                </div>
            <?php endforeach; ?>
            <?php endif; ?>
            <?php endif; ?>
			
		</form>
	</div>
	<div class="card-footer">
		<button class="btn btn-flat btn-primary" form="product-form">Save</button>
		<a class="btn btn-flat btn-default" href="?page=product">Cancel</a>
	</div>
</div>
<script src="<?php echo base_url ?>dist/js/html5-qrcode.min.js"></script>
<script>
    var barcodeScanner = null;
    var barcodeScanning = false;
    var availableCameras = [];

    function resetBarcodeCameraSelect(){
        $('#barcode-camera-select-wrap').hide();
        $('#barcode-camera-select').empty();
        availableCameras = [];
    }

    function getPreferredCameraId(cameras){
        for(var i = 0; i < cameras.length; i++){
            var label = (cameras[i].label || '').toLowerCase();
            if(label.indexOf('irium') !== -1 || label.indexOf('external') !== -1 || label.indexOf('usb') !== -1){
                return cameras[i].id;
            }
        }
        return cameras[cameras.length - 1].id;
    }

    function populateCameraSelect(cameras){
        var $select = $('#barcode-camera-select');
        $select.empty();
        cameras.forEach(function(camera, index){
            var label = camera.label && camera.label.trim() ? camera.label : ('Camera ' + (index + 1));
            $select.append($('<option>', { value: camera.id, text: label }));
        });
        if(cameras.length > 1){
            $('#barcode-camera-select-wrap').show();
            $select.val(getPreferredCameraId(cameras));
        }else{
            $('#barcode-camera-select-wrap').hide();
        }
    }

    function stopBarcodeScan(showMsg){
        var finish = function(){
            barcodeScanning = false;
            barcodeScanner = null;
            $('#barcode-scanner-wrap').hide();
            resetBarcodeCameraSelect();
            if(showMsg){
                alert_toast("Barcode scanner stopped","info");
            }
        };
        if(barcodeScanner && barcodeScanning){
            barcodeScanner.stop().then(function(){
                barcodeScanner.clear();
            }).catch(function(){}).finally(finish);
        }else{
            finish();
        }
    }

    function onBarcodeScanned(decodedText){
        playScannerSound();
        $('#barcode').val(decodedText);
        alert_toast("Barcode scanned successfully","success");
        stopBarcodeScan(false);
    }

    function startBarcodeScan(cameraId){
        var formatsToSupport = [
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
            Html5QrcodeSupportedFormats.UPC_A,
            Html5QrcodeSupportedFormats.UPC_E
        ];
        var config = {
            fps: 30,
            qrbox: { width: 420, height: 160 }
        };
        barcodeScanner = new Html5Qrcode("barcode-scanner-reader", { formatsToSupport: formatsToSupport, verbose: false });
        return barcodeScanner.start(
            cameraId,
            config,
            onBarcodeScanned,
            function(){}
        ).then(function(){
            barcodeScanning = true;
        });
    }

    function restartBarcodeScanWithSelectedCamera(){
        var cameraId = $('#barcode-camera-select').val();
        if(!cameraId && availableCameras.length){
            cameraId = availableCameras.length > 1 ? getPreferredCameraId(availableCameras) : availableCameras[0].id;
        }
        if(!cameraId){
            alert_toast("No camera selected","error");
            return Promise.reject();
        }
        if(barcodeScanner && barcodeScanning){
            return barcodeScanner.stop().then(function(){
                barcodeScanner.clear();
                barcodeScanning = false;
                barcodeScanner = null;
                return startBarcodeScan(cameraId);
            }).catch(function(){
                barcodeScanning = false;
                barcodeScanner = null;
                return startBarcodeScan(cameraId);
            });
        }
        return startBarcodeScan(cameraId);
    }

    function displayImg(input,_this) {
        $('#img-upload-msg').html('');
        var allowedExt = ['jpg','jpeg','png','webp'];
        var invalidFiles = [];
        var smallFiles = [];
        var validFiles = [];

        if(!input.files || input.files.length < 1){
            _this.siblings('.custom-file-label').html('Choose file');
            return;
        }

        Array.from(input.files).forEach(function(file){
            var ext = file.name.split('.').pop().toLowerCase();
            if(allowedExt.indexOf(ext) === -1){
                invalidFiles.push(file.name);
            }else{
                validFiles.push(file);
            }
        });

        if(invalidFiles.length > 0){
            input.value = '';
            _this.siblings('.custom-file-label').html('Choose file');
            $('#img-upload-msg').html('<div class="alert alert-danger mt-2 mb-0 py-2">Only JPG, JPEG, PNG, and WEBP files are allowed. Invalid file(s): '+invalidFiles.join(', ')+'</div>');
            return;
        }

        var fnames = validFiles.map(function(file){ return file.name; });
        _this.siblings('.custom-file-label').html(fnames.join(", "));

        var checks = validFiles.map(function(file){
            return new Promise(function(resolve){
                var reader = new FileReader();
                reader.onload = function(e){
                    var img = new Image();
                    img.onload = function(){
                        if(img.width < 800 || img.height < 800){
                            smallFiles.push(file.name + ' (' + img.width + 'x' + img.height + ' px)');
                        }
                        resolve();
                    };
                    img.onerror = function(){ resolve(); };
                    img.src = e.target.result;
                };
                reader.onerror = function(){ resolve(); };
                reader.readAsDataURL(file);
            });
        });

        Promise.all(checks).then(function(){
            if(smallFiles.length > 0){
                $('#img-upload-msg').html('<div class="alert alert-warning mt-2 mb-0 py-2">Recommended image size is 800x800 px or higher. These image(s) are smaller but can still be uploaded: '+smallFiles.join(', ')+'</div>');
            }
        });
    }
    function delete_img($path){
        start_loader()
        
        $.ajax({
            url: _base_url_+'classes/Master.php?f=delete_img',
            data:{path:$path},
            method:'POST',
            dataType:"json",
            error:err=>{
                console.log(err)
                alert_toast("An error occured while deleting an Image","error");
                end_loader()
            },
            success:function(resp){
                $('.modal').modal('hide')
                if(typeof resp =='object' && resp.status == 'success'){
                    $('[data-path="'+$path+'"]').closest('.img-item').hide('slow',function(){
                        $('[data-path="'+$path+'"]').closest('.img-item').remove()
                    })
                    alert_toast("Image Successfully Deleted","success");
                }else{
                    console.log(resp)
                    alert_toast("An error occured while deleting an Image","error");
                }
                end_loader()
            }
        })
    }
	$(document).ready(function(){
        $('#start-barcode-scan').click(function(){
            if(typeof Html5Qrcode === 'undefined'){
                alert_toast("Barcode scanner library failed to load","error");
                return;
            }
            stopBarcodeScan(false);
            $('#barcode-scanner-wrap').show();
            Html5Qrcode.getCameras().then(function(cameras){
                availableCameras = cameras || [];
                if(!availableCameras.length){
                    alert_toast("No webcam found","error");
                    stopBarcodeScan(false);
                    return;
                }
                populateCameraSelect(availableCameras);
                return restartBarcodeScanWithSelectedCamera();
            }).catch(function(err){
                console.log(err);
                alert_toast("Unable to access webcam","error");
                stopBarcodeScan(false);
            });
        });
        $('#barcode-camera-select').change(function(){
            if(!$('#barcode-scanner-wrap').is(':visible')){
                return;
            }
            restartBarcodeScanWithSelectedCamera().catch(function(err){
                console.log(err);
                alert_toast("Unable to switch camera","error");
            });
        });
        $('#stop-barcode-scan').click(function(){
            stopBarcodeScan(true);
        });
        $('.rem_img').click(function(){
            _conf("Are sure to delete this image permanently?",'delete_img',["'"+$(this).attr('data-path')+"'"])
        })
       
        $('.select2').select2({placeholder:"Please Select here",width:"relative"})
        if(parseInt("<?php echo isset($category_id) ? $category_id : 0 ?>") > 0){
            console.log('test')
            start_loader()
            setTimeout(() => {
                $('#category_id').trigger("change");
                end_loader()
            }, 750);
        }
		$('#product-form').submit(function(e){
			e.preventDefault();
            var _this = $(this)
			 $('.err-msg').remove();
			start_loader();
			$.ajax({
				url:_base_url_+"classes/Master.php?f=save_product",
				data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
				error:err=>{
					console.log(err)
					alert_toast("An error occured",'error');
					end_loader();
				},
				success:function(resp){
					if(typeof resp =='object' && resp.status == 'success'){
						location.href = "./?page=product";
					}else if(resp.status == 'failed' && !!resp.msg){
                        var el = $('<div>')
                            el.addClass("alert alert-danger err-msg").text(resp.msg)
                            _this.prepend(el)
                            el.show('slow')
                            $("html, body").animate({ scrollTop: _this.closest('.card').offset().top }, "fast");
                            if(!!resp.id)
                            $('[name="id"]').val(resp.id)
                            end_loader()
                    }else{
						alert_toast("An error occured",'error');
						end_loader();
                        console.log(resp)
					}
				}
			})
		})

        $('.summernote').summernote({
		        height: 200,
		        toolbar: [
		            [ 'style', [ 'style' ] ],
		            [ 'font', [ 'bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear'] ],
		            // [ 'fontname', [ 'fontname' ] ],
		            [ 'fontsize', [ 'fontsize' ] ],
		            [ 'color', [ 'color' ] ],
		            [ 'para', [ 'ol', 'ul', 'paragraph' ] ],
		            [ 'table', [ 'table' ] ],
		            [ 'view', [ 'undo', 'redo', 'codeview', 'help' ] ]
		        ]
		    })
	})
</script>