<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>

<style>
	img#cimg{
		height: 15vh;
		width: 15vh;
		max-width: 120px;
		max-height: 120px;
		object-fit: contain;
		border-radius: 50%;
	}
	img#cimg2{
		height: 50vh;
		width: 100%;
		object-fit: contain;
		/* border-radius: 100% 100%; */
	}
</style>
<div class="col-12 col-lg-12">
	<div class="card card-outline card-primary">
		<div class="card-header">
			<h5 class="card-title">Store Settings</h5>
			<!-- <div class="card-tools">
				<a class="btn btn-block btn-sm btn-default btn-flat border-primary new_department" href="javascript:void(0)"><i class="fa fa-plus"></i> Add New</a>
			</div> -->
		</div>
		<div class="card-body">
			<form action="" id="system-frm">
				<div id="msg" class="form-group"></div>
				<div class="form-group">
					<label for="name" class="control-label">Business Name</label>
					<input type="text" class="form-control form-control-sm" name="name" id="name" value="<?php echo $_settings->info('name') ?>">
				</div>
				<div class="form-group">
					<label for="short_name" class="control-label">Short Business Name</label>
					<input type="text" class="form-control form-control-sm" name="short_name" id="short_name" value="<?php echo  $_settings->info('short_name') ?>">
				</div>
				<div class="form-group">
					<label for="low_stock_threshold" class="control-label">Low Stock Threshold</label>
					<input type="number" min="0" step="1" class="form-control form-control-sm" name="low_stock_threshold" id="low_stock_threshold" value="<?php echo inventory_low_stock_threshold() ?>">
					<small class="text-muted">Inventory at or below this quantity (above zero) is flagged as low stock. Default is 5.</small>
				</div>
				<div class="form-group">
					<label for="business_start_date" class="control-label">Business Start Date</label>
					<input type="date" class="form-control form-control-sm" name="business_start_date" id="business_start_date" value="<?php echo htmlspecialchars(business_settings_date('business_start_date')) ?>">
					<small class="text-muted">Opening date for lifetime reports. Leave blank to use the first recorded sale or expense.</small>
				</div>
				<div class="form-group">
					<label for="business_closed_date" class="control-label">Business Closed Date <span class="text-muted">(optional)</span></label>
					<input type="date" class="form-control form-control-sm" name="business_closed_date" id="business_closed_date" value="<?php echo htmlspecialchars(business_settings_date('business_closed_date')) ?>">
					<small class="text-muted">If set, reports end on this date. Historical data remains available for review.</small>
				</div>
			<div class="form-group">
				<label for="" class="control-label">About Us</label>
	             <textarea name="about_us" id="" cols="30" rows="2" class="form-control summernote"><?php echo  is_file(base_app.'about.html') ? file_get_contents(base_app.'about.html') : "" ?></textarea>
			</div>
			<div class="form-group">
				<label for="" class="control-label">Privacy Policy</label>
	             <textarea name="privacy_policy" id="" cols="30" rows="2" class="form-control summernote"><?php echo  is_file(base_app.'privacy_policy.html') ? file_get_contents(base_app.'privacy_policy.html') : "" ?></textarea>
			</div>
			
			<div class="form-group">
				<label for="scanner_sound_mode" class="control-label">Scanner Success Sound</label>
				<select name="scanner_sound_mode" id="scanner_sound_mode" class="form-control form-control-sm">
					<?php $scanner_mode = $_settings->info('scanner_sound_mode') ?: 'default_beep'; ?>
					<option value="default_beep" <?php echo $scanner_mode == 'default_beep' ? 'selected' : '' ?>>Default Beep</option>
					<option value="uploaded" <?php echo $scanner_mode == 'uploaded' ? 'selected' : '' ?>>Uploaded Sound</option>
					<option value="none" <?php echo $scanner_mode == 'none' ? 'selected' : '' ?>>No Sound</option>
				</select>
			</div>
			<div class="form-group" id="scanner-sound-upload-wrap">
				<label for="scanner_sound" class="control-label">Custom Scanner Sound</label>
				<div class="custom-file">
					<input type="file" class="custom-file-input" id="scanner_sound" name="scanner_sound" accept=".mp3,.wav,.ogg,audio/mpeg,audio/wav,audio/ogg">
					<label class="custom-file-label" for="scanner_sound">Choose file</label>
				</div>
				<small class="text-muted d-block mt-1">MP3, WAV, or OGG only. Max 1MB.</small>
				<?php
				$scanner_file = $_settings->info('scanner_sound_file');
				if(!empty($scanner_file)):
					$scanner_path = explode('?', $scanner_file)[0];
					if(is_file(base_app.$scanner_path)):
				?>
				<div class="mt-2">
					<small class="text-muted d-block mb-1">Current sound:</small>
					<audio controls preload="none" style="max-width:100%;height:32px;">
						<source src="<?php echo base_url.$scanner_file ?>" type="audio/<?php echo pathinfo($scanner_path, PATHINFO_EXTENSION) === 'mp3' ? 'mpeg' : pathinfo($scanner_path, PATHINFO_EXTENSION) ?>">
					</audio>
				</div>
				<?php endif; endif; ?>
			</div>
			<div class="form-group">
				<label for="" class="control-label">System Logo</label>
				<div class="custom-file">
	              <input type="file" class="custom-file-input rounded-circle" id="customFile" name="img" onchange="displayImg(this,$(this))">
	              <label class="custom-file-label" for="customFile">Choose file</label>
	            </div>
			</div>
			<div class="form-group d-flex justify-content-center">
				<span class="system-logo-wrapper system-logo-login system-favicon-preview d-inline-block mb-2">
				<img src="<?php echo validate_image($_settings->info('logo')) ?>" alt="" id="cimg" class="img-fluid">
				</span>
			</div>
			<div class="form-group">
				<label for="" class="control-label">Website Cover</label>
				<div class="custom-file">
	              <input type="file" class="custom-file-input rounded-circle" id="customFile" name="cover" onchange="displayImg2(this,$(this))">
	              <label class="custom-file-label" for="customFile">Choose file</label>
	            </div>
			</div>
			<div class="form-group d-flex justify-content-center">
				<img src="<?php echo validate_image($_settings->info('cover')) ?>" alt="" id="cimg2" class="img-fluid img-thumbnail">
			</div>
			<div class="form-group">
				<label for="" class="control-label">Banner Images</label>
				<div class="custom-file">
	              <input type="file" class="custom-file-input rounded-circle" id="customFile" name="banners[]" multiple accept=".png,.jpg,.jpeg" onchange="displayImg3(this,$(this))">
	              <label class="custom-file-label" for="customFile">Choose file</label>
	            </div>
				<small><i>Choose to upload new banner immages</i></small>
			</div>
			<?php 
            $upload_path = "uploads/banner";
            if(is_dir(base_app.$upload_path)): 
			$file= scandir(base_app.$upload_path);
                foreach($file as $img):
                    if(in_array($img,array('.','..')))
                        continue;
                    
                
            ?>
                <div class="d-flex w-100 align-items-center img-item">
                    <span><img src="<?php echo base_url.$upload_path.'/'.$img."?v=".(time()) ?>" width="150px" height="100px" style="object-fit:cover;" class="img-thumbnail" alt=""></span>
                    <span class="ml-4"><button class="btn btn-sm btn-default text-danger rem_img" type="button" data-path="<?php echo base_app.$upload_path.'/'.$img ?>"><i class="fa fa-trash"></i></button></span>
                </div>
            <?php endforeach; ?>
            <?php endif; ?>
			</form>
		</div>
		<div class="card-footer">
			<div class="col-md-12">
				<div class="row">
					<button class="btn btn-sm btn-primary" form="system-frm">Update</button>
				</div>
			</div>
		</div>

	</div>
</div>
<script>
	function displayImg(input,_this) {
	    if (input.files && input.files[0]) {
	        var reader = new FileReader();
	        reader.onload = function (e) {
	        	$('#cimg').attr('src', e.target.result);
	        	_this.siblings('.custom-file-label').html(input.files[0].name)
	        }

	        reader.readAsDataURL(input.files[0]);
	    }
	}
	function displayImg2(input,_this) {
	    if (input.files && input.files[0]) {
	        var reader = new FileReader();
	        reader.onload = function (e) {
	        	_this.siblings('.custom-file-label').html(input.files[0].name)
	        	$('#cimg2').attr('src', e.target.result);
	        }

	        reader.readAsDataURL(input.files[0]);
	    }
	}
	function displayImg3(input,_this) {
		var fnames = [];
		Object.keys(input.files).map(function(k){
			fnames.push(input.files[k].name)

		})
		_this.siblings('.custom-file-label').html(fnames.join(", "))
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
		function toggleScannerSoundUpload(){
			$('#scanner-sound-upload-wrap').toggle($('#scanner_sound_mode').val() === 'uploaded');
		}
		toggleScannerSoundUpload();
		$('#scanner_sound_mode').change(toggleScannerSoundUpload);
		$('#scanner_sound').on('change', function(){
			var name = this.files && this.files[0] ? this.files[0].name : 'Choose file';
			$(this).siblings('.custom-file-label').html(name);
		});
		$('.rem_img').click(function(){
            _conf("Are sure to delete this image permanently?",'delete_img',["'"+$(this).attr('data-path')+"'"])
        })
		 $('.summernote').summernote({
		        height: 200,
		        toolbar: [
		            [ 'style', [ 'style' ] ],
		            [ 'font', [ 'bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear'] ],
		            [ 'fontname', [ 'fontname' ] ],
		            [ 'fontsize', [ 'fontsize' ] ],
		            [ 'color', [ 'color' ] ],
		            [ 'para', [ 'ol', 'ul', 'paragraph', 'height' ] ],
		            [ 'table', [ 'table' ] ],
		            [ 'view', [ 'undo', 'redo', 'fullscreen', 'codeview', 'help' ] ]
		        ]
		    })
	})
</script>