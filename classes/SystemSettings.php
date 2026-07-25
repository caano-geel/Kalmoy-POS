<?php
if(!class_exists('DBConnection')){
	require_once('../config.php');
	require_once('DBConnection.php');
}
class SystemSettings extends DBConnection{
	public function __construct(){
		parent::__construct();
	}
	function check_connection(){
		return($this->conn);
	}
	function load_system_info(){
		$_SESSION['system_info'] = array();
		$bid = function_exists('tenant_id') ? tenant_id() : 0;
		if($bid <= 0){
			$_SESSION['system_info'] = array(
				'name' => 'Kalmoy POS',
				'short_name' => 'Kalmoy POS',
				'currency_symbol' => 'Ksh',
			);
			return;
		}
		$sql = "SELECT * FROM business_settings WHERE business_id = '{$bid}'";
		$qry = $this->conn->query($sql);
		if($qry){
			while($row = $qry->fetch_assoc()){
				$_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
			}
		}
		if(empty($_SESSION['system_info']) && function_exists('tenant_seed_default_settings')){
			$bq = $this->conn->query("SELECT name FROM businesses WHERE id = '{$bid}' LIMIT 1");
			if($bq && $bq->num_rows > 0){
				$bname = $bq->fetch_assoc()['name'];
				tenant_seed_default_settings($bid, $bname, $this->conn);
				$qry2 = $this->conn->query($sql);
				if($qry2){
					while($row = $qry2->fetch_assoc()){
						$_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
					}
				}
			}
		}
		if(function_exists('tenant_ensure_tenant_resources')){
			$repaired = tenant_ensure_tenant_resources($bid, $this->conn);
			if($repaired){
				$_SESSION['system_info'] = array();
				$qry3 = $this->conn->query($sql);
				if($qry3){
					while($row = $qry3->fetch_assoc()){
						$_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
					}
				}
			}
		}
	}
	function update_system_info(){
		$bid = function_exists('tenant_id') ? tenant_id() : 0;
		if($bid <= 0) return false;
		$sql = "SELECT * FROM business_settings WHERE business_id = '{$bid}'";
		$qry = $this->conn->query($sql);
		if($qry){
			while($row = $qry->fetch_assoc()){
				if(isset($_SESSION['system_info'][$row['meta_field']]))unset($_SESSION['system_info'][$row['meta_field']]);
				$_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
			}
		}
		return true;
	}
	function update_settings_info(){
		$bid = function_exists('tenant_id') ? tenant_id() : 0;
		if($bid <= 0){
			return json_encode(array('status'=>'failed','msg'=>'No business context.'));
		}
		$uploadBase = function_exists('tenant_ensure_upload_dir') ? tenant_ensure_upload_dir($bid) : base_app.'uploads/';
		$uploadPrefix = function_exists('tenant_upload_dir') ? tenant_upload_dir($bid) : 'uploads/';
		foreach ($_POST as $key => $value) {
			if($key === 'low_stock_threshold'){
				$value = max(0, (int)$value);
			}
			if(in_array($key, array('business_start_date', 'business_closed_date'), true)){
				$value = trim((string)$value);
				if($value !== ''){
					$value = expenses_normalize_date($value, '');
					if($value === '') continue;
				}
			}
			$value = $this->conn->real_escape_string(str_replace("'", "&apos;", $value));
			if(isset($_SESSION['system_info'][$key])){
				$qry = $this->conn->query("UPDATE business_settings set meta_value = '{$value}' where business_id = '{$bid}' AND meta_field = '{$key}' ");
			}else{
				$keyEsc = $this->conn->real_escape_string($key);
				$qry = $this->conn->query("INSERT into business_settings set business_id = '{$bid}', meta_value = '{$value}', meta_field = '{$keyEsc}' ");
			}
		}
		if(!empty($_FILES['img']['tmp_name'])){
			$ext = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
			$fname = $uploadPrefix."logo-".(time()).".$ext";
			$accept = array('image/jpeg','image/png');
			if(!in_array($_FILES['img']['type'],$accept)){
				$err = "Image file type is invalid";
			}
			if($_FILES['img']['type'] == 'image/jpeg')
				$uploadfile = imagecreatefromjpeg($_FILES['img']['tmp_name']);
			elseif($_FILES['img']['type'] == 'image/png')
				$uploadfile = imagecreatefrompng($_FILES['img']['tmp_name']);
			if(!$uploadfile){
				$err = "Image is invalid";
			}
			$temp = imagescale($uploadfile,200,200);
			if(is_file(base_app.$fname))
			unlink(base_app.$fname);
			if($_FILES['img']['type'] == 'image/jpeg')
			$upload =imagejpeg($temp,base_app.$fname);
			elseif($_FILES['img']['type'] == 'image/png')
			$upload =imagepng($temp,base_app.$fname);
			else
			$upload = false;
			if($upload){
				if(isset($_SESSION['system_info']['logo'])){
					$qry = $this->conn->query("UPDATE business_settings set meta_value = CONCAT('{$fname}', '?v=',unix_timestamp(CURRENT_TIMESTAMP)) where business_id = '{$bid}' AND meta_field = 'logo' ");
					if(is_file(base_app.$_SESSION['system_info']['logo'])) unlink(base_app.$_SESSION['system_info']['logo']);
				}else{
					$qry = $this->conn->query("INSERT into business_settings set business_id = '{$bid}', meta_value = '{$fname}',meta_field = 'logo' ");
				}
			}
			imagedestroy($temp);
		}
		if(!empty($_FILES['cover']['tmp_name'])){
			$ext = pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION);
			$fname = $uploadPrefix."cover-".(time()).".$ext";
			$accept = array('image/jpeg','image/png');
			if(!in_array($_FILES['cover']['type'],$accept)){
				$err = "Image file type is invalid";
			}
			if($_FILES['cover']['type'] == 'image/jpeg')
				$uploadfile = imagecreatefromjpeg($_FILES['cover']['tmp_name']);
			elseif($_FILES['cover']['type'] == 'image/png')
				$uploadfile = imagecreatefrompng($_FILES['cover']['tmp_name']);
			if(!$uploadfile){
				$err = "Image is invalid";
			}
			list($width,$height) = getimagesize($_FILES['cover']['tmp_name']);
			$temp = imagescale($uploadfile,$width,$height);
			if(is_file(base_app.$fname))
			unlink(base_app.$fname);
			if($_FILES['cover']['type'] == 'image/jpeg')
			$upload =imagejpeg($temp,base_app.$fname);
			elseif($_FILES['cover']['type'] == 'image/png')
			$upload =imagepng($temp,base_app.$fname);
			else
			$upload = false;
			if($upload){
				if(isset($_SESSION['system_info']['cover'])){
					$qry = $this->conn->query("UPDATE business_settings set meta_value = CONCAT('{$fname}', '?v=',unix_timestamp(CURRENT_TIMESTAMP)) where business_id = '{$bid}' AND meta_field = 'cover' ");
					if(is_file(base_app.$_SESSION['system_info']['cover'])) unlink(base_app.$_SESSION['system_info']['cover']);
				}else{
					$qry = $this->conn->query("INSERT into business_settings set business_id = '{$bid}', meta_value = '{$fname}',meta_field = 'cover' ");
				}
			}
			imagedestroy($temp);
		}
		if(!empty($_FILES['scanner_sound']['tmp_name'])){
			$max_size = 1048576;
			$ext = strtolower(pathinfo($_FILES['scanner_sound']['name'], PATHINFO_EXTENSION));
			$allowed_ext = array('mp3','wav','ogg');
			if(!in_array($ext, $allowed_ext)){
				return json_encode(array('status'=>'failed','msg'=>'Scanner sound must be MP3, WAV, or OGG.'));
			}
			if($_FILES['scanner_sound']['size'] > $max_size){
				return json_encode(array('status'=>'failed','msg'=>'Scanner sound must be 1MB or smaller.'));
			}
			$fname = $uploadPrefix.'scanner-sound.'.$ext;
			foreach(array('mp3','wav','ogg') as $oldext){
				$old = $uploadPrefix.'scanner-sound.'.$oldext;
				if(is_file(base_app.$old))
					unlink(base_app.$old);
			}
			if(!move_uploaded_file($_FILES['scanner_sound']['tmp_name'], base_app.$fname)){
				return json_encode(array('status'=>'failed','msg'=>'Failed to upload scanner sound.'));
			}
			$value = $fname;
			if(isset($_SESSION['system_info']['scanner_sound_file'])){
				$qry = $this->conn->query("UPDATE business_settings set meta_value = '{$value}' where business_id = '{$bid}' AND meta_field = 'scanner_sound_file' ");
			}else{
				$qry = $this->conn->query("INSERT into business_settings set business_id = '{$bid}', meta_value = '{$value}', meta_field = 'scanner_sound_file' ");
			}
		}
		if(isset($_FILES['banners']) && count($_FILES['banners']['tmp_name']) > 0){
			$err='';
			$banner_path = $uploadPrefix."banner/";
			if(!is_dir(base_app.$banner_path))
				mkdir(base_app.$banner_path, 0755, true);
			foreach($_FILES['banners']['tmp_name'] as $k => $v){
				if(!empty($_FILES['banners']['tmp_name'][$k])){
					$accept = array('image/jpeg','image/png');
					if(!in_array($_FILES['banners']['type'][$k],$accept)){
						$err = "Image file type is invalid";
						break;
					}
					if($_FILES['banners']['type'][$k] == 'image/jpeg')
						$uploadfile = imagecreatefromjpeg($_FILES['banners']['tmp_name'][$k]);
					elseif($_FILES['banners']['type'][$k] == 'image/png')
						$uploadfile = imagecreatefrompng($_FILES['banners']['tmp_name'][$k]);
					if(!$uploadfile){
						$err = "Image is invalid";
						break;
					}
					$temp = imagescale($uploadfile,1200,400);
					$spath = base_app.$banner_path.'/'.$_FILES['banners']['name'][$k];
					$i = 1;
					while(true){
						if(is_file($spath)){
							$spath = base_app.$banner_path.'/'.($i++).'_'.$_FILES['banners']['name'][$k];
						}else{
							break;
						}
					}
					if($_FILES['banners']['type'][$k] == 'image/jpeg')
					imagejpeg($temp,$spath);
					elseif($_FILES['banners']['type'][$k] == 'image/png')
					imagepng($temp,$spath);

					imagedestroy($temp);
				}
			}
			if(!empty($err)){
				$resp['status'] = 'failed';
				$resp['msg'] = $err;
			}
		}
		
		$update = $this->update_system_info();
		$flash = $this->set_flashdata('success','System Info Successfully Updated.');
		if($update && $flash){
			// var_dump($_SESSION);
			$resp['status'] = 'success';
			admin_activity_log('settings_updated', 'System settings updated');
		}else{
			$resp['status'] = 'failed';
		}
		return json_encode($resp);
	}
	function set_userdata($field='',$value=''){
		if(!empty($field) && !empty($value)){
			$_SESSION['userdata'][$field]= $value;
		}
	}
	function userdata($field = ''){
		if(!empty($field)){
			if(isset($_SESSION['userdata'][$field]))
				return $_SESSION['userdata'][$field];
			else
				return null;
		}else{
			return false;
		}
	}
	function set_flashdata($flash='',$value=''){
		if(!empty($flash) && !empty($value)){
			$_SESSION['flashdata'][$flash]= $value;
		return true;
		}
	}
	function chk_flashdata($flash = ''){
		if(isset($_SESSION['flashdata'][$flash])){
			return true;
		}else{
			return false;
		}
	}
	function flashdata($flash = ''){
		if(!empty($flash)){
			$_tmp = $_SESSION['flashdata'][$flash];
			unset($_SESSION['flashdata']);
			return $_tmp;
		}else{
			return false;
		}
	}
	function sess_des(){
		if(isset($_SESSION['userdata'])){
				unset($_SESSION['userdata']);
			return true;
		}
			return true;
	}
	function info($field=''){
		if(!empty($field)){
			if(isset($_SESSION['system_info'][$field]))
				return $_SESSION['system_info'][$field];
			else
				return false;
		}else{
			return false;
		}
	}
	function set_info($field='',$value=''){
		if(!empty($field) && !empty($value)){
			$_SESSION['system_info'][$field] = $value;
		}
	}
}
$_settings = new SystemSettings();
$_settings->load_system_info();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
if($action === 'update_settings' && !admin_cashier_can('settings')){
	echo json_encode(['status'=>'failed','msg'=>'Access denied.']);
	exit;
}
switch ($action) {
	case 'update_settings':
		echo $sysset->update_settings_info();
		break;
	default:
		// echo $sysset->index();
		break;
}
?>