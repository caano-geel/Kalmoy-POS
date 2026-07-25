<?php
require_once('../config.php');
Class Master extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	private function _bid(){
		return tenant_id();
	}
	private function _ts($alias = ''){
		return tenant_sql($alias);
	}
	private function _own($table, $id){
		if(!empty($id) && (int)$id > 0 && !tenant_owned($this->conn, $table, (int)$id)){
			echo json_encode(array('status'=>'failed','msg'=>'Record not found or access denied.'));
			exit;
		}
	}
	function capture_err(){
		if(!$this->conn->error)
			return false;
		else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			return json_encode($resp);
			exit;
		}
	}
	function save_brand(){
		extract($_POST);
		if(!empty($id)){
			$this->_own('brands', $id);
		}
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!empty($data)) $data .=",";
				$v = addslashes(trim($v));
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `brands` where business_id = '{$this->_bid()}' and  `name` = '{$name}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Brand Name already exist.";
			return json_encode($resp);
			exit;
		}
		if(empty($id)){
			$sql = "INSERT INTO `brands` set business_id = '{$this->_bid()}', {$data} ";
		}else{
			$sql = "UPDATE `brands` set {$data} where id = '{$id}'{$this->_ts()} ";
		}
			$save = $this->conn->query($sql);
		if($save){
			$bid = !empty($id) ? $id : $this->conn->insert_id;
			$resp['status'] = 'success';
			if(empty($id))
				$resp['msg'] = "New Brand successfully saved.";
			else
				$resp['msg'] = "Brand successfully updated.";
			if(!empty($_FILES['img']['tmp_name'])){
				if(!is_dir(base_app."uploads/brands"))
				mkdir(base_app."uploads/brands");
				$ext = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
				$fname = "uploads/brands/$bid.$ext";
				$accept = array('image/jpeg','image/png');
				if(!in_array($_FILES['img']['type'],$accept)){
					$resp['msg'] .= " Image file type is invalid";
				}
				if($_FILES['img']['type'] == 'image/jpeg')
					$uploadfile = imagecreatefromjpeg($_FILES['img']['tmp_name']);
				elseif($_FILES['img']['type'] == 'image/png')
					$uploadfile = imagecreatefrompng($_FILES['img']['tmp_name']);
				if(!$uploadfile){
					$resp['msg'] .= " Image is invalid";
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
					$qry = $this->conn->query("UPDATE brands set `image_path` = CONCAT('{$fname}', '?v=',unix_timestamp(CURRENT_TIMESTAMP)) where id = '{$bid}'{$this->_ts()} ");
				}
				imagedestroy($temp);
			}
			
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		if($resp['status'] == 'success')
			$this->settings->set_flashdata('success',$resp['msg']);
			return json_encode($resp);
	}
	function delete_brand(){
		extract($_POST);
		$this->_own('brands', $id);
		$del = $this->conn->query("UPDATE `brands` set `delete_flag` = 1 where id = '{$id}'{$this->_ts()}");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Brand successfully deleted.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function save_category(){
		extract($_POST);
		if(!empty($id)){
			$this->_own('categories', $id);
		}
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id','description'))){
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if(isset($_POST['description'])){
			if(!empty($data)) $data .=",";
				$data .= " `description`='".addslashes(htmlentities($description))."' ";
		}
		$check = $this->conn->query("SELECT * FROM `categories` where business_id = '{$this->_bid()}' and  `category` = '{$category}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Category already exist.";
			return json_encode($resp);
			exit;
		}
		if(empty($id)){
			$sql = "INSERT INTO `categories` set business_id = '{$this->_bid()}', {$data} ";
			$save = $this->conn->query($sql);
		}else{
			$sql = "UPDATE `categories` set {$data} where id = '{$id}'{$this->_ts()} ";
			$save = $this->conn->query($sql);
		}
		if($save){
			$resp['status'] = 'success';
			if(empty($id))
				$this->settings->set_flashdata('success',"New Category successfully saved.");
			else
				$this->settings->set_flashdata('success',"Category successfully updated.");
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_category(){
		extract($_POST);
		$this->_own('categories', $id);
		$del = $this->conn->query("UPDATE `categories` set delete_flag = 1 where id = '{$id}'{$this->_ts()}");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Category successfully deleted.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function save_product(){
		$_POST['specs'] = htmlentities($_POST['specs']);
		if(isset($_POST['barcode'])){
			$_POST['barcode'] = trim($_POST['barcode']);
		}
		foreach($_POST as $k =>$v){
			$_POST[$k] = addslashes($v);
		}
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!empty($data)) $data .=",";
				if($k == 'barcode' && $v === ''){
					$data .= " `barcode`=NULL ";
				}else{
					$v = addslashes($v);
					$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
				}
			}
		}
		$check = $this->conn->query("SELECT * FROM `products` where business_id = '{$this->_bid()}' and  `name` = '{$name}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Product already exist.";
			return json_encode($resp);
			exit;
		}
		if(!empty($barcode)){
			$check_barcode = $this->conn->query("SELECT * FROM `products` where business_id = '{$this->_bid()}' and  `barcode` = '{$barcode}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
			if($check_barcode > 0){
				$resp['status'] = 'failed';
				$resp['msg'] = "Barcode already exists.";
				return json_encode($resp);
				exit;
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `products` set business_id = '{$this->_bid()}', {$data} ";
		}else{
			$sql = "UPDATE `products` set {$data} where id = '{$id}'{$this->_ts()} ";
		}
		$save = $this->conn->query($sql);
		if($save){
			$pid = empty($id) ? $this->conn->insert_id : $id;
			$upload_path = "uploads/product_".$pid;
			if(!is_dir(base_app.$upload_path))
				mkdir(base_app.$upload_path);
			if(isset($_FILES['img']) && count($_FILES['img']['tmp_name']) > 0){
				$err = "";
				foreach($_FILES['img']['tmp_name'] as $k => $v){
					if(!empty($_FILES['img']['tmp_name'][$k])){
						$ext = strtolower(pathinfo($_FILES['img']['name'][$k], PATHINFO_EXTENSION));
						$allowed_ext = array('jpg','jpeg','png','webp');
						if(!in_array($ext, $allowed_ext)){
							$err = "Image file type is invalid. Allowed formats: JPG, JPEG, PNG, WEBP";
							break;
						}
						if(in_array($ext, array('jpg','jpeg')))
							$uploadfile = imagecreatefromjpeg($_FILES['img']['tmp_name'][$k]);
						elseif($ext == 'png')
							$uploadfile = imagecreatefrompng($_FILES['img']['tmp_name'][$k]);
						elseif($ext == 'webp')
							$uploadfile = function_exists('imagecreatefromwebp') ? imagecreatefromwebp($_FILES['img']['tmp_name'][$k]) : false;
						if(!$uploadfile){
							$err = "Image is invalid";
							break;
						}
						$temp = imagescale($uploadfile,400,400);
						$spath = base_app.$upload_path.'/'.$_FILES['img']['name'][$k];
						$i = 0;
						while(true){
							if(is_file($spath)){
								$spath = base_app.$upload_path.'/'.$i."_".$_FILES['img']['name'][$k];
							}else{
								break;
							}
							$i++;
						}
						if(in_array($ext, array('jpg','jpeg')))
						imagejpeg($temp, $spath);
						elseif($ext == 'png')
						imagepng($temp, $spath);
						elseif($ext == 'webp' && function_exists('imagewebp'))
						imagewebp($temp, $spath);

						imagedestroy($temp);
					}
				}
				if(!empty($err)){
					$resp['status'] = 'failed';
					$resp['msg'] = 'Product successfully saved but '.$err;
					$resp['id'] = $pid;
				}
			}
			if(!isset($resp)){
				$resp['status'] = 'success';
				if(empty($id))
					$this->settings->set_flashdata('success',"New Product successfully saved.");
				else
					$this->settings->set_flashdata('success',"Product successfully updated.");
				admin_activity_log(empty($id) ? 'product_created' : 'product_updated', stripslashes($name));
			}
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_product(){
		extract($_POST);
		$product = $this->conn->query("SELECT name FROM products WHERE id = '{$id}' LIMIT 1")->fetch_assoc();
		$del = $this->conn->query("UPDATE `products` set delete_flag = 1 where id = '{$id}'{$this->_ts()}");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Product successfully deleted.");
			admin_activity_log('product_deleted', $product ? stripslashes($product['name']) : 'Product #'.$id);
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function delete_img(){
		extract($_POST);
		if(is_file($path)){
			if(unlink($path)){
				$resp['status'] = 'success';
			}else{
				$resp['status'] = 'failed';
				$resp['error'] = 'failed to delete '.$path;
			}
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = 'Unkown '.$path.' path';
		}
		return json_encode($resp);
	}
	function save_inventory(){
		extract($_POST);
		if(!empty($id)){
			$this->_own('inventory', $id);
		}
		if(!empty($product_id) && !tenant_owned($this->conn, 'products', (int)$product_id)){
			return json_encode(array('status'=>'failed','msg'=>'Product not found or access denied.'));
		}
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id','description'))){
				if(admin_is_cashier() && $k === 'cost_price')
					continue;
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `inventory` where business_id = '{$this->_bid()}' and  `product_id` = '{$product_id}' and variant = '{$variant}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Inventory already exist.";
			return json_encode($resp);
			exit;
		}
		if(empty($id)){
			$sql = "INSERT INTO `inventory` set business_id = '{$this->_bid()}', {$data} ";
			$save = $this->conn->query($sql);
		}else{
			$sql = "UPDATE `inventory` set {$data} where id = '{$id}'{$this->_ts()} ";
			$save = $this->conn->query($sql);
		}
		if($save){
			$resp['status'] = 'success';
			if(empty($id))
				$this->settings->set_flashdata('success',"New Inventory successfully saved.");
			else
				$this->settings->set_flashdata('success',"Inventory successfully updated.");
			$inv_id = empty($id) ? $this->conn->insert_id : $id;
			$info = $this->conn->query("SELECT p.name, i.variant FROM inventory i INNER JOIN products p ON p.id = i.product_id WHERE i.id = '{$inv_id}'".tenant_sql('i')." LIMIT 1")->fetch_assoc();
			$detail = $info ? stripslashes($info['name']).' ('.stripslashes($info['variant']).')' : 'Inventory #'.$inv_id;
			admin_activity_log(empty($id) ? 'inventory_created' : 'inventory_updated', $detail);
			notifications_sync_system();
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_inventory(){
		extract($_POST);
		$this->_own('inventory', $id);
		$info = $this->conn->query("SELECT p.name, i.variant FROM inventory i INNER JOIN products p ON p.id = i.product_id WHERE i.id = '{$id}'".tenant_sql('i')." LIMIT 1")->fetch_assoc();
		$del = $this->conn->query("DELETE FROM `inventory` where id = '{$id}'{$this->_ts()}");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Invenory successfully deleted.");
			$detail = $info ? stripslashes($info['name']).' ('.stripslashes($info['variant']).')' : 'Inventory #'.$id;
			admin_activity_log('inventory_deleted', $detail);
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function register(){
		extract($_POST);
		$data = "";
		$_POST['password'] = md5($_POST['password']);
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `clients` where business_id = '{$this->_bid()}' and  `email` = '{$email}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Email already taken.";
			return json_encode($resp);
			exit;
		}
		if(empty($id)){
			$sql = "INSERT INTO `clients` set business_id = '{$this->_bid()}', {$data} ";
		}else{
			$sql = "UPDATE `clients` set {$data} where id = '{$id}'{$this->_ts()} ";
		}
			$save = $this->conn->query($sql);
		if($save){
			$cid = !empty($id) ? $id : $this->conn->insert_id;
			$resp['status'] = 'success';
			if(empty($id))
				$this->settings->set_flashdata('success',"Account successfully created.");
			else
				$this->settings->set_flashdata('success',"Account successfully updated.");
			$this->settings->set_userdata('login_type',2);
			foreach($_POST as $k =>$v){
				$this->settings->set_userdata($k,$v);
			}
			$this->settings->set_userdata('id',$cid);

		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function add_to_cart(){
		extract($_POST);
		$data = " client_id = '".$this->settings->userdata('id')."' ";
		$_POST['price'] = str_replace(",","",$_POST['price']); 
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `cart` where `inventory_id` = '{$inventory_id}' and client_id = ".$this->settings->userdata('id'))->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$sql = "UPDATE `cart` set quantity = quantity + {$quantity} where `inventory_id` = '{$inventory_id}' and client_id = ".$this->settings->userdata('id');
		}else{
			$sql = "INSERT INTO `cart` set business_id = '{$this->_bid()}', {$data} ";
		}
		
		$save = $this->conn->query($sql);
		if($this->capture_err())
			return $this->capture_err();
			if($save){
				$resp['status'] = 'success';
				$resp['cart_count'] = $this->conn->query("SELECT SUM(quantity) as items from `cart` where client_id =".$this->settings->userdata('id'))->fetch_assoc()['items'];
			}else{
				$resp['status'] = 'failed';
				$resp['err'] = $this->conn->error."[{$sql}]";
			}
			return json_encode($resp);
	}
	function update_cart_qty(){
		extract($_POST);
		
		$save = $this->conn->query("UPDATE `cart` set quantity = '{$quantity}' where id = '{$id}'");
		if($this->capture_err())
			return $this->capture_err();
		if($save){
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
		
	}
	function empty_cart(){
		$delete = $this->conn->query("DELETE FROM `cart` where client_id = ".$this->settings->userdata('id'));
		if($this->capture_err())
			return $this->capture_err();
		if($delete){
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_cart(){
		extract($_POST);
		$delete = $this->conn->query("DELETE FROM `cart` where id = '{$id}'{$this->_ts()}");
		if($this->capture_err())
			return $this->capture_err();
		if($delete){
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_order(){
		extract($_POST);
		if(class_exists('CustomerDebtService')){
			CustomerDebtService::void_debt_for_order($this->conn, (int)$id);
		}
		$delete = $this->conn->query("DELETE FROM `orders` where id = '{$id}'{$this->_ts()}");
		$delete2 = $this->conn->query("DELETE FROM `order_list` where order_id = '{$id}'");
		$delete3 = $this->conn->query("DELETE FROM `sales` where order_id = '{$id}'");
		if($this->capture_err())
			return $this->capture_err();
		if($delete){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Order successfully deleted");
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function place_order(){
		if(empty($id)){
			$prefix = date("Ym");
			$code = sprintf("%'.05d",1);
			while(true){
				$check = $this->conn->query("SELECT * FROM `orders` where business_id = '{$this->_bid()}' and  ref_code = '{$prefix}{$code}' ")->num_rows;
				if($check > 0){
					$code = sprintf("%'.05d",ceil($code) + 1);
				}else{
					break;
				}
			}
			$_POST['ref_code'] = $prefix.$code;
		}
		extract($_POST);
		$client_id = $this->settings->userdata('id');
		
		$data = " client_id = '{$client_id}' ";
		if(isset($ref_code))
		$data .= " ,ref_code = '{$ref_code}' ";
		$data .= " ,payment_method = '{$payment_method}' ";
		$data .= " ,amount = '{$amount}' ";
		$data .= " ,paid = '{$paid}' ";
		$data .= " ,delivery_address = '{$delivery_address}' ";
		$now = date('Y-m-d H:i:s');
		$data .= " ,date_created = '{$now}' ";
		$order_sql = "INSERT INTO `orders` set $data";
		$save_order = $this->conn->query($order_sql);
		if($this->capture_err())
			return $this->capture_err();
		if($save_order){
			$order_id = $this->conn->insert_id;
			$data = '';
			$cart = $this->conn->query("SELECT c.*,p.name,i.price,p.id as pid from `cart` c inner join `inventory` i on i.id=c.inventory_id inner join products p on p.id = i.product_id where c.client_id ='{$client_id}' ");
			while($row= $cart->fetch_assoc()):
				if(!empty($data)) $data .= ", ";
				$total = $row['price'] * $row['quantity'];
				$data .= "('{$order_id}','{$row['pid']}','{$row['quantity']}','{$row['price']}', $total)";
			endwhile;
			$list_sql = "INSERT INTO `order_list` (order_id,inventory_id,quantity,price,total) VALUES {$data} ";
			$save_olist = $this->conn->query($list_sql);
			if($this->capture_err())
				return $this->capture_err();
			if($save_olist){
				$empty_cart = $this->conn->query("DELETE FROM `cart` where client_id = '{$client_id}'");
				$data = " order_id = '{$order_id}'";
				$data .= " ,total_amount = '{$amount}'";
				$data .= " ,date_created = '{$now}'";
				$save_sales = $this->conn->query("INSERT INTO `sales` set $data");
				if($this->capture_err())
					return $this->capture_err();
				$resp['status'] ='success';
				$this->settings->set_flashdata('success'," Order has been placed successfully.");
			}else{
				$resp['status'] ='failed';
				$resp['err_sql'] =$save_olist;
			}

		}else{
			$resp['status'] ='failed';
			$resp['err_sql'] =$save_order;
		}
		return json_encode($resp);
	}
	function update_order_status(){
		extract($_POST);
		$order = $this->conn->query("SELECT ref_code FROM orders WHERE id = '{$id}' LIMIT 1")->fetch_assoc();
		$update = $this->conn->query("UPDATE `orders` set `status` = '$status' where id = '{$id}' ");
		if($update){
			$resp['status'] ='success';
			$this->settings->set_flashdata("success"," Order status successfully updated.");
			$status_labels = array(0 => 'Open', 1 => 'Packed', 2 => 'Out for Delivery', 3 => 'Delivered', 4 => 'Cancelled');
			$label = isset($status_labels[(int)$status]) ? $status_labels[(int)$status] : 'Status '.$status;
			$ref = $order ? $order['ref_code'] : 'Order #'.$id;
			admin_activity_log('order_updated', $ref.' updated to '.$label);
		}else{
			$resp['status'] ='failed';
			$resp['err'] =$this->conn->error;
		}
		return json_encode($resp);
	}
	function pay_order(){
		extract($_POST);
		$update = $this->conn->query("UPDATE `orders` set `paid` = '1' where id = '{$id}' ");
		if($update){
			$resp['status'] ='success';
			$this->settings->set_flashdata("success"," Order payment status successfully updated.");
		}else{
			$resp['status'] ='failed';
			$resp['err'] =$this->conn->error;
		}
		return json_encode($resp);
	}
	function update_account(){
		if(!empty($_POST['password']))
			$_POST['password'] = md5($password);
		else
			unset($_POST['password']);
		extract($_POST);
		$data = "";
		if(md5($cpassword) != $this->settings->userdata('password')){
			$resp['status'] = 'failed';
			$resp['msg'] = "Current Password is Incorrect";
			return json_encode($resp);
			exit;
		}
		$check = $this->conn->query("SELECT * FROM `clients`  where `email`='{$email}' and `id` != $id ")->num_rows;
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Email already taken.";
			return json_encode($resp);
			exit;
		}
		foreach($_POST as $k =>$v){
			if($k == 'cpassword' || ($k == 'password' && empty($v)))
				continue;
				if(!empty($data)) $data .=",";
					$data .= " `{$k}`='{$v}' ";
		}
		$save = $this->conn->query("UPDATE `clients` set $data where id = $id ");
		if($save){
			foreach($_POST as $k =>$v){
				if($k != 'cpassword')
				$this->settings->set_userdata($k,$v);
			}
			
			$this->settings->set_userdata('id',$this->conn->insert_id);
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',' Your Account Details has been updated successfully.');
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function update_client(){
		$resp = array('status' => 'failed', 'msg' => 'Unable to save customer.');
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
		$lastname = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
		$contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
		$email = isset($_POST['email']) ? trim($_POST['email']) : '';
		$default_delivery_address = isset($_POST['default_delivery_address']) ? trim($_POST['default_delivery_address']) : '';
		$status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

		if($firstname === ''){
			$resp['msg'] = 'First name is required.';
			return json_encode($resp);
		}
		if($contact === ''){
			$resp['msg'] = 'Phone number is required.';
			return json_encode($resp);
		}

		if($email !== ''){
			$email_esc = $this->conn->real_escape_string($email);
			$check_sql = "SELECT id FROM `clients` where business_id = '{$this->_bid()}' and  `email` = '{$email_esc}'";
			if($id > 0) $check_sql .= " AND id != {$id}";
			if($this->conn->query($check_sql)->num_rows > 0){
				$resp['msg'] = 'Email already taken.';
				return json_encode($resp);
			}
		}elseif($id > 0){
			$existing = $this->conn->query("SELECT email FROM `clients` where business_id = '{$this->_bid()}' and  id = {$id} LIMIT 1");
			if($existing && $existing->num_rows){
				$old_email = trim($existing->fetch_assoc()['email']);
				if($old_email !== '' && !preg_match('/@customer\.local$/i', $old_email)){
					$email = 'customer.'.uniqid('', true).'@customer.local';
				}else{
					$email = $old_email !== '' ? $old_email : 'customer.'.uniqid('', true).'@customer.local';
				}
			}else{
				$email = 'customer.'.uniqid('', true).'@customer.local';
			}
		}else{
			$email = 'customer.'.uniqid('', true).'@customer.local';
		}

		$fields = array(
			'firstname' => $firstname,
			'lastname' => $lastname,
			'contact' => $contact,
			'email' => $email,
			'default_delivery_address' => $default_delivery_address,
			'status' => $status,
			'gender' => 'N/A',
		);
		$data = '';
		foreach($fields as $k => $v){
			if($data !== '') $data .= ', ';
			$data .= " `{$k}`='".$this->conn->real_escape_string($v)."' ";
		}
		if($id <= 0){
			$data .= ", `password`='".md5(uniqid('cust', true))."' ";
			$data .= ", `delete_flag`='0' ";
			$save = $this->conn->query("INSERT INTO `clients` SET {$data}, date_created = '".date('Y-m-d H:i:s')."'");
			$msg = 'Customer successfully created.';
		}else{
			$save = $this->conn->query("UPDATE `clients` SET {$data} WHERE id = {$id}");
			$msg = 'Customer successfully updated.';
		}
		if($save){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', $msg);
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = 'Failed to save customer.';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function delete_client(){
		extract($_POST);
		$delete = $this->conn->query("UPDATE `clients` set delete_flag = 1 where id = '{$id}'{$this->_ts()}");
		if($delete){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Client successfully deleted");
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	private function pos_require_admin(){
		if($this->settings->userdata('login_type') != 1){
			return json_encode(['status'=>'failed','msg'=>'Access denied.']);
		}
		return false;
	}
	private function get_pos_client_id(){
		$email = 'pos.walkin@local';
		$check = $this->conn->query("SELECT id FROM `clients` where business_id = '{$this->_bid()}' and  email = '{$email}' AND delete_flag = 0 LIMIT 1");
		if($check && $check->num_rows > 0){
			return (int)$check->fetch_assoc()['id'];
		}
		$pwd = md5('pos_walkin');
		$sql = "INSERT INTO `clients` SET firstname='Walk-in', lastname='Customer', gender='N/A', contact='0000000000', email='{$email}', password='{$pwd}', default_delivery_address='In-Store POS', status=1, delete_flag=0";
		if($this->conn->query($sql)){
			return (int)$this->conn->insert_id;
		}
		return 0;
	}
	private function get_inventory_stock($inventory_id){
		$inventory_id = (int)$inventory_id;
		$cost_select = db_table_has_column('inventory', 'cost_price') ? ', i.cost_price' : '';
		$sold_sub = inventory_sold_subquery_sql();
		$row = $this->conn->query("SELECT i.id, i.price{$cost_select}, i.quantity, p.name, p.barcode, i.variant, b.name as bname,
			(i.quantity - IFNULL(sold.qty, 0)) AS stock
			FROM inventory i
			INNER JOIN products p ON p.id = i.product_id
			INNER JOIN brands b ON p.brand_id = b.id
			LEFT JOIN {$sold_sub} sold ON sold.inventory_id = i.id
			WHERE i.id = '{$inventory_id}' AND p.delete_flag = 0 AND p.status = 1".tenant_sql('i')."
			LIMIT 1")->fetch_assoc();
		return $row ?: null;
	}
	private function snapshot_cost_price($inv){
		if(!is_array($inv) || !array_key_exists('cost_price', $inv))
			return null;
		if($inv['cost_price'] === null || $inv['cost_price'] === '')
			return null;
		$cost = (float)$inv['cost_price'];
		if($cost <= 0)
			return null;
		return $cost;
	}
	function pos_search_product(){
		if($denied = $this->pos_require_admin()) return $denied;
		$q = isset($_POST['q']) ? trim($_POST['q']) : '';
		if($q === ''){
			return json_encode(['status'=>'failed','msg'=>'Enter a barcode or product name.']);
		}
		$q_esc = $this->conn->real_escape_string($q);
		$like = '%'.$q_esc.'%';
		$sold_sub = inventory_sold_subquery_sql();
		$sql = "SELECT i.id AS inventory_id, i.variant, i.price, p.name, p.barcode, b.name AS bname,
			(i.quantity - IFNULL(sold.qty, 0)) AS stock
			FROM inventory i
			INNER JOIN products p ON p.id = i.product_id
			INNER JOIN brands b ON p.brand_id = b.id
			LEFT JOIN {$sold_sub} sold ON sold.inventory_id = i.id
			WHERE p.delete_flag = 0 AND p.status = 1".tenant_sql('i')."
			AND (p.barcode = '{$q_esc}' OR p.name LIKE '{$like}' OR p.barcode LIKE '{$like}')
			ORDER BY (p.barcode = '{$q_esc}') DESC, p.name ASC, i.variant ASC
			LIMIT 25";
		$qry = $this->conn->query($sql);
		if($this->capture_err()) return $this->capture_err();
		$items = [];
		while($row = $qry->fetch_assoc()){
			$row['price'] = (float)$row['price'];
			$row['stock'] = (float)$row['stock'];
			$row['inventory_id'] = (int)$row['inventory_id'];
			$items[] = $row;
		}
		return json_encode(['status'=>'success','items'=>$items]);
	}
	function pos_search_customers(){
		if($denied = $this->pos_require_admin()) return $denied;
		$q = isset($_POST['q']) ? trim($_POST['q']) : '';
		$email = $this->conn->real_escape_string(CustomerDebtService::WALKIN_EMAIL);
		$sql = "SELECT id, firstname, lastname, contact, email,
			CONCAT(firstname,' ',lastname) AS fullname
			FROM clients WHERE delete_flag = 0 AND status = 1 AND email != '{$email}'".tenant_sql();
		if($q !== ''){
			$q_esc = $this->conn->real_escape_string($q);
			$like = '%'.$q_esc.'%';
			$sql .= " AND (firstname LIKE '{$like}' OR lastname LIKE '{$like}' OR contact LIKE '{$like}' OR email LIKE '{$like}')";
		}
		$sql .= " ORDER BY firstname, lastname LIMIT 30";
		$qry = $this->conn->query($sql);
		$items = [];
		while($qry && ($row = $qry->fetch_assoc())){
			$row['id'] = (int)$row['id'];
			$row['outstanding'] = CustomerDebtService::client_outstanding($this->conn, $row['id']);
			$items[] = $row;
		}
		return json_encode(['status'=>'success','items'=>$items]);
	}
	function debt_receive_payment(){
		$resp = ['status'=>'failed','msg'=>'Unable to process payment.'];
		if(admin_is_cashier() && !admin_cashier_has_permission('debt_payment')){
			$resp['msg'] = 'Access denied.';
			return json_encode($resp);
		}
		$client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
		$amount = isset($_POST['amount']) ? (float)str_replace(',', '', $_POST['amount']) : 0;
		$payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
		$reference = isset($_POST['reference']) ? trim($_POST['reference']) : '';
		$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
		$user = [
			'id' => (int)$this->settings->userdata('id'),
			'name' => trim($this->settings->userdata('firstname').' '.$this->settings->userdata('lastname')),
		];
		$result = CustomerDebtService::apply_payment($this->conn, $client_id, $amount, $payment_method, $reference, $notes, $user);
		if($result['status'] !== 'success'){
			$resp['msg'] = isset($result['msg']) ? $result['msg'] : 'Payment failed.';
			return json_encode($resp);
		}
		$cq = $this->conn->query("SELECT CONCAT(firstname,' ',lastname) AS fullname, contact FROM clients WHERE id = '{$client_id}' LIMIT 1");
		$customer = $cq ? $cq->fetch_assoc() : ['fullname'=>'Customer','contact'=>''];
		CustomerDebtService::notify_large_payment($amount, $customer['fullname']);
		admin_activity_log('debt_payment_received', $customer['fullname'].' | '.format_price($amount).' | '.$payment_method);
		notifications_sync_system();
		return json_encode(array_merge($result, [
			'customer_name' => $customer['fullname'],
			'customer_phone' => $customer['contact'],
			'payment_method' => $payment_method,
			'reference' => $reference,
			'date_created' => date('Y-m-d H:i:s'),
		]));
	}
	function debt_delete_payment(){
		$resp = ['status'=>'failed','msg'=>'Unable to delete payment.'];
		if(admin_is_cashier() && !admin_cashier_has_permission('debt_payment_delete')){
			$resp['msg'] = 'Access denied.';
			return json_encode($resp);
		}
		$payment_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$result = CustomerDebtService::delete_payment($this->conn, $payment_id);
		if($result['status'] === 'success'){
			admin_activity_log('debt_payment_deleted', 'Payment #'.$payment_id);
		}
		return json_encode($result);
	}
	function debt_client_summary(){
		$client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
		if($client_id <= 0){
			return json_encode(['status'=>'failed','msg'=>'Invalid customer.']);
		}
		$summary = CustomerDebtService::client_summary($this->conn, $client_id);
		$cq = $this->conn->query("SELECT CONCAT(firstname,' ',lastname) AS fullname, contact FROM clients WHERE id = '{$client_id}' LIMIT 1");
		$customer = $cq ? $cq->fetch_assoc() : null;
		return json_encode(['status'=>'success','summary'=>$summary,'customer'=>$customer]);
	}
	function pos_complete_sale(){
		if($denied = $this->pos_require_admin()) return $denied;
		$items_raw = isset($_POST['items']) ? $_POST['items'] : '';
		$payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
		$amount = isset($_POST['amount']) ? (float)str_replace(',','',$_POST['amount']) : 0;
		$allowed_payments = ['Cash','M-Pesa','Mixed','Debt'];
		if(!in_array($payment_method, $allowed_payments)){
			return json_encode(['status'=>'failed','msg'=>'Invalid payment method.']);
		}
		$is_debt = ($payment_method === 'Debt');
		if($is_debt && admin_is_cashier() && !admin_cashier_has_permission('debt_sale')){
			return json_encode(['status'=>'failed','msg'=>'You do not have permission for credit sales.']);
		}
		$items = is_string($items_raw) ? json_decode($items_raw, true) : $items_raw;
		if(!is_array($items) || count($items) === 0){
			return json_encode(['status'=>'failed','msg'=>'Cart is empty.']);
		}
		$registered_client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
		$client_id = 0;
		$customer_name = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
		$display_customer = 'Walk-in Customer';
		if($is_debt){
			if($registered_client_id <= 0){
				return json_encode(['status'=>'failed','msg'=>'Select a registered customer for credit sales.']);
			}
			if(CustomerDebtService::is_walkin_client($this->conn, $registered_client_id)){
				return json_encode(['status'=>'failed','msg'=>'Walk-in customers cannot buy on credit.']);
			}
			$client_id = $registered_client_id;
		}else{
			if($registered_client_id > 0 && !CustomerDebtService::is_walkin_client($this->conn, $registered_client_id)){
				$client_id = $registered_client_id;
			}else{
				$client_id = $this->get_pos_client_id();
			}
		}
		if($client_id <= 0){
			return json_encode(['status'=>'failed','msg'=>'Unable to resolve customer.']);
		}
		$cq = $this->conn->query("SELECT CONCAT(firstname,' ',lastname) AS fullname FROM clients WHERE id = '{$client_id}' LIMIT 1");
		if($cq && $cq->num_rows){
			$display_customer = $cq->fetch_assoc()['fullname'];
		}elseif($customer_name !== ''){
			$display_customer = $customer_name;
		}
		$validated = [];
		$computed_total = 0;
		foreach($items as $item){
			$inventory_id = isset($item['inventory_id']) ? (int)$item['inventory_id'] : 0;
			$quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;
			if($inventory_id <= 0 || $quantity <= 0){
				return json_encode(['status'=>'failed','msg'=>'Invalid cart item.']);
			}
			$inv = $this->get_inventory_stock($inventory_id);
			if(!$inv){
				return json_encode(['status'=>'failed','msg'=>'Product not found or unavailable.']);
			}
			if($quantity > $inv['stock']){
				return json_encode(['status'=>'failed','msg'=>$inv['name'].' ('.$inv['variant'].') has only '.format_num($inv['stock']).' in stock.']);
			}
			$sale_price = isset($item['price']) ? (float)str_replace(',', '', $item['price']) : (float)$inv['price'];
			if($sale_price < 0){
				return json_encode(['status'=>'failed','msg'=>'Invalid price for '.$inv['name'].'.']);
			}
			$line_total = $sale_price * $quantity;
			$computed_total += $line_total;
			$validated[] = [
				'inventory_id' => $inventory_id,
				'quantity' => $quantity,
				'price' => $sale_price,
				'total' => $line_total,
				'cost_price' => $this->snapshot_cost_price($inv),
				'name' => $inv['name'],
				'variant' => $inv['variant'],
				'bname' => $inv['bname']
			];
		}
		$subtotal = $computed_total;
		$discount_percent = isset($_POST['discount_percent']) ? (float)$_POST['discount_percent'] : 0;
		$discount_ksh = isset($_POST['discount_ksh']) ? (float)str_replace(',', '', $_POST['discount_ksh']) : 0;
		if($discount_percent < 0) $discount_percent = 0;
		if($discount_percent > 100) $discount_percent = 100;
		if($discount_ksh < 0) $discount_ksh = 0;
		$discount_total = min($subtotal, ($subtotal * $discount_percent / 100) + $discount_ksh);
		$expected_amount = max(0, $subtotal - $discount_total);
		if(abs($expected_amount - $amount) > 0.01){
			return json_encode(['status'=>'failed','msg'=>'Total mismatch. Please refresh and try again.']);
		}
		$amount = $expected_amount;
		$cash_part = isset($_POST['cash_amount']) ? (float)str_replace(',', '', $_POST['cash_amount']) : 0;
		$mpesa_part = isset($_POST['mpesa_amount']) ? (float)str_replace(',', '', $_POST['mpesa_amount']) : 0;
		if($payment_method === 'Mixed'){
			if(abs(($cash_part + $mpesa_part) - $amount) > 0.01){
				return json_encode(['status'=>'failed','msg'=>'Mixed payment must equal the sale total.']);
			}
			if($cash_part <= 0 && $mpesa_part <= 0){
				return json_encode(['status'=>'failed','msg'=>'Enter cash and/or M-Pesa amounts.']);
			}
		}
		$delivery_address = 'In-Store POS';
		if($payment_method === 'Mixed'){
			$delivery_address .= ' | Mixed: Cash '.number_format($cash_part, 2).' + M-Pesa '.number_format($mpesa_part, 2);
		}elseif($customer_name !== '' && CustomerDebtService::is_walkin_client($this->conn, $client_id)){
			$delivery_address .= ' - Customer: '.$this->conn->real_escape_string($customer_name);
		}
		$due_date = isset($_POST['due_date']) ? trim($_POST['due_date']) : '';
		$prefix = date("Ym");
		$code = sprintf("%'.05d",1);
		while(true){
			$check = $this->conn->query("SELECT id FROM `orders` where business_id = '{$this->_bid()}' and  ref_code = '{$prefix}{$code}' LIMIT 1")->num_rows;
			if($check > 0){
				$code = sprintf("%'.05d",ceil($code) + 1);
			}else{
				break;
			}
		}
		$ref_code = $prefix.$code;
		$payment_esc = $this->conn->real_escape_string($payment_method);
		$paid = $is_debt ? 0 : 1;
		$now = date('Y-m-d H:i:s');
		$this->conn->begin_transaction();
		$discount_sql = db_table_has_column('orders', 'discount_total') ? ", discount_total = '{$discount_total}'" : '';
		$order_sql = "INSERT INTO `orders` SET
			client_id = '{$client_id}',
			ref_code = '{$ref_code}',
			delivery_address = '{$delivery_address}',
			payment_method = '{$payment_esc}',
			order_type = 1,
			amount = '{$amount}',
			status = 3,
			paid = '{$paid}',
			date_created = '{$now}'{$discount_sql}";
		if(!$this->conn->query($order_sql)){
			$this->conn->rollback();
			return json_encode(['status'=>'failed','msg'=>'Failed to create order.','err'=>$this->conn->error]);
		}
		$order_id = $this->conn->insert_id;
		$list_values = [];
		$has_line_cost = db_table_has_column('order_list', 'cost_price');
		foreach($validated as $row){
			$cost_sql = 'NULL';
			if($has_line_cost && $row['cost_price'] !== null){
				$cost_sql = "'".$row['cost_price']."'";
			}
			if($has_line_cost){
				$list_values[] = "('{$order_id}','{$row['inventory_id']}','{$row['quantity']}','{$row['price']}','{$row['total']}',{$cost_sql})";
			}else{
				$list_values[] = "('{$order_id}','{$row['inventory_id']}','{$row['quantity']}','{$row['price']}','{$row['total']}')";
			}
		}
		if($has_line_cost){
			$list_sql = "INSERT INTO `order_list` (order_id,inventory_id,quantity,price,total,cost_price) VALUES ".implode(', ', $list_values);
		}else{
			$list_sql = "INSERT INTO `order_list` (order_id,inventory_id,quantity,price,total) VALUES ".implode(', ', $list_values);
		}
		if(!$this->conn->query($list_sql)){
			$this->conn->rollback();
			return json_encode(['status'=>'failed','msg'=>'Failed to save order items.','err'=>$this->conn->error]);
		}
		$sales_sql = "INSERT INTO `sales` SET order_id = '{$order_id}', total_amount = '{$amount}', date_created = '{$now}'";
		if(!$this->conn->query($sales_sql)){
			$this->conn->rollback();
			return json_encode(['status'=>'failed','msg'=>'Failed to record sale.','err'=>$this->conn->error]);
		}
		$outstanding_after = 0;
		if($is_debt){
			$debt_result = CustomerDebtService::record_debt_sale($this->conn, $order_id, $client_id, $amount, $due_date !== '' ? $due_date : null);
			if($debt_result['status'] !== 'success'){
				$this->conn->rollback();
				return json_encode(['status'=>'failed','msg'=>isset($debt_result['msg']) ? $debt_result['msg'] : 'Failed to record debt.']);
			}
			$outstanding_after = CustomerDebtService::client_outstanding($this->conn, $client_id);
			CustomerDebtService::notify_large_debt($amount, $display_customer, $ref_code);
			if($due_date !== '' && strtotime($due_date) < strtotime(date('Y-m-d'))){
				CustomerDebtService::notify_overdue_client($this->conn, $client_id);
			}
		}
		$this->conn->commit();
		admin_activity_log('pos_sale_completed', $ref_code.' | '.format_price($amount).' | '.$payment_method.($is_debt ? ' (Credit)' : ''));
		$notify_type = $is_debt ? 'warning' : 'success';
		admin_notify($notify_type, $is_debt ? 'Credit Sale' : 'Sale Completed', 'Receipt '.$ref_code.' — '.format_price($amount).' via '.$payment_method, base_url.'admin/?page=sales', 'sale_'.$order_id);
		notifications_sync_system();
		$receipt_items = array();
		foreach($validated as $row){
			$item = $row;
			unset($item['cost_price']);
			$receipt_items[] = $item;
		}
		$receipt_payment = $is_debt ? 'DEBT / CREDIT SALE' : $payment_method;
		if($payment_method === 'Mixed'){
			$receipt_payment = 'Mixed (Cash '.number_format($cash_part, 2).' + M-Pesa '.number_format($mpesa_part, 2).')';
		}
		return json_encode([
			'status' => 'success',
			'order_id' => $order_id,
			'ref_code' => $ref_code,
			'amount' => $amount,
			'subtotal' => $subtotal,
			'discount_percent' => $discount_percent,
			'discount_ksh' => $discount_ksh,
			'discount_total' => $discount_total,
			'customer_name' => $display_customer,
			'payment_method' => $receipt_payment,
			'is_debt' => $is_debt,
			'outstanding_balance' => $outstanding_after,
			'due_date' => $due_date,
			'items' => $receipt_items,
			'date_created' => $now
		]);
	}
	function save_cashier_permissions(){
		$resp = array('status' => 'failed', 'msg' => 'Unable to save permissions.');
		if(!admin_is_owner()){
			$resp['msg'] = 'Access denied.';
			return json_encode($resp);
		}
		$data = isset($_POST['permissions']) ? $_POST['permissions'] : array();
		if(admin_save_cashier_permissions($data)){
			$resp['status'] = 'success';
			$resp['msg'] = 'Default staff role permissions saved successfully.';
			$this->settings->set_flashdata('success', $resp['msg']);
			admin_activity_log('permissions_updated', 'Default cashier role permissions updated');
		}
		return json_encode($resp);
	}
	function save_user_permissions(){
		$resp = array('status' => 'failed', 'msg' => 'Unable to save user permissions.');
		if(!admin_is_owner()){
			$resp['msg'] = 'Access denied.';
			return json_encode($resp);
		}
		$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
		$data = isset($_POST['permissions']) ? $_POST['permissions'] : array();
		if($user_id <= 0){
			$resp['msg'] = 'Invalid user.';
			return json_encode($resp);
		}
		if(admin_save_user_permissions($user_id, $data)){
			$resp['status'] = 'success';
			$resp['msg'] = 'User permissions saved successfully.';
			$this->settings->set_flashdata('success', $resp['msg']);
			$u = $this->conn->query("SELECT username FROM users WHERE id = '{$user_id}' LIMIT 1")->fetch_assoc();
			admin_activity_log('user_permissions_updated', ($u ? $u['username'] : 'User #'.$user_id));
		}
		return json_encode($resp);
	}
	function save_staff_user(){
		$resp = array('status' => 'failed', 'msg' => 'Unable to save user.');
		if(!admin_is_owner()){
			$resp['msg'] = 'Access denied.';
			return json_encode($resp);
		}
		users_ensure_schema();
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
		$lastname = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
		$username = isset($_POST['username']) ? trim($_POST['username']) : '';
		$email = isset($_POST['email']) ? trim($_POST['email']) : '';
		$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
		$password = isset($_POST['password']) ? $_POST['password'] : '';
		$type = isset($_POST['type']) ? (int)$_POST['type'] : 2;
		$status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
		if($firstname === '' || $lastname === '' || $username === ''){
			$resp['msg'] = 'Full name and username are required.';
			return json_encode($resp);
		}
		if(!in_array($type, array(1, 2), true)) $type = 2;
		$status = $status === 1 ? 1 : 0;
		$fn = $this->conn->real_escape_string($firstname);
		$ln = $this->conn->real_escape_string($lastname);
		$un = $this->conn->real_escape_string($username);
		$em = $this->conn->real_escape_string($email);
		$ph = $this->conn->real_escape_string($phone);
		$dup = $this->conn->query("SELECT id FROM users WHERE username = '{$un}' ".($id > 0 ? "AND id != '{$id}'" : '')." LIMIT 1");
		if($dup && $dup->num_rows){
			$resp['msg'] = 'Username already exists.';
			return json_encode($resp);
		}
		if($id > 0){
			$sets = "firstname = '{$fn}', lastname = '{$ln}', username = '{$un}', type = '{$type}', status = '{$status}'";
			if(db_table_has_column('users', 'email')) $sets .= ", email = ".($email !== '' ? "'{$em}'" : 'NULL');
			if(db_table_has_column('users', 'phone')) $sets .= ", phone = ".($phone !== '' ? "'{$ph}'" : 'NULL');
			if($password !== ''){
				$hash = $this->conn->real_escape_string(app_hash_password($password));
				$sets .= ", password = '{$hash}'";
			}
			$sql = "UPDATE users SET {$sets} WHERE id = '{$id}'";
			$ok = $this->conn->query($sql);
			$action = 'staff_updated';
			$msg = 'User updated successfully.';
		}else{
			if($password === ''){
				$resp['msg'] = 'Password is required for new users.';
				return json_encode($resp);
			}
			$hash = $this->conn->real_escape_string(app_hash_password($password));
			$email_sql = db_table_has_column('users', 'email') ? ($email !== '' ? "'{$em}'" : 'NULL') : 'NULL';
			$phone_sql = db_table_has_column('users', 'phone') ? ($phone !== '' ? "'{$ph}'" : 'NULL') : 'NULL';
			$sql = "INSERT INTO users SET firstname = '{$fn}', lastname = '{$ln}', username = '{$un}',
				email = {$email_sql}, phone = {$phone_sql}, password = '{$hash}', type = '{$type}', status = '{$status}'";
			$ok = $this->conn->query($sql);
			$id = (int)$this->conn->insert_id;
			$action = 'staff_created';
			$msg = 'User created successfully.';
		}
		if($ok){
			if(isset($_POST['permissions']) && $type === 2){
				admin_save_user_permissions($id, $_POST['permissions']);
			}
			$resp['status'] = 'success';
			$resp['msg'] = $msg;
			$this->settings->set_flashdata('success', $msg);
			admin_activity_log($action, $username.' ('.trim($firstname.' '.$lastname).')');
		}else{
			$resp['msg'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function delete_staff_user(){
		$resp = array('status' => 'failed', 'msg' => 'Unable to delete user.');
		if(!admin_is_owner()){
			$resp['msg'] = 'Access denied.';
			return json_encode($resp);
		}
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$self = isset($_SESSION['userdata']['id']) ? (int)$_SESSION['userdata']['id'] : 0;
		if($id <= 0 || $id === $self){
			$resp['msg'] = 'You cannot delete this user.';
			return json_encode($resp);
		}
		$row = $this->conn->query("SELECT username, firstname, lastname FROM users WHERE id = '{$id}' LIMIT 1")->fetch_assoc();
		if($this->conn->query("DELETE FROM users WHERE id = '{$id}'")){
			$resp['status'] = 'success';
			$resp['msg'] = 'User deleted successfully.';
			$this->settings->set_flashdata('success', $resp['msg']);
			admin_activity_log('staff_deleted', $row ? $row['username'] : 'User #'.$id);
		}
		return json_encode($resp);
	}
	function reset_staff_password(){
		$resp = array('status' => 'failed', 'msg' => 'Unable to reset password.');
		if(!admin_is_owner()){
			$resp['msg'] = 'Access denied.';
			return json_encode($resp);
		}
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$password = isset($_POST['password']) ? $_POST['password'] : '';
		if($id <= 0 || strlen($password) < 4){
			$resp['msg'] = 'Enter a valid password (min 4 characters).';
			return json_encode($resp);
		}
		$hash = $this->conn->real_escape_string(app_hash_password($password));
		if($this->conn->query("UPDATE users SET password = '{$hash}' WHERE id = '{$id}'")){
			$u = $this->conn->query("SELECT username FROM users WHERE id = '{$id}' LIMIT 1")->fetch_assoc();
			$resp['status'] = 'success';
			$resp['msg'] = 'Password reset successfully.';
			$this->settings->set_flashdata('success', $resp['msg']);
			admin_activity_log('staff_password_reset', $u ? $u['username'] : 'User #'.$id);
		}
		return json_encode($resp);
	}
	function toggle_staff_status(){
		$resp = array('status' => 'failed', 'msg' => 'Unable to update status.');
		if(!admin_is_owner()){
			$resp['msg'] = 'Access denied.';
			return json_encode($resp);
		}
		users_ensure_schema();
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$self = isset($_SESSION['userdata']['id']) ? (int)$_SESSION['userdata']['id'] : 0;
		if($id <= 0 || $id === $self){
			$resp['msg'] = 'You cannot change your own status here.';
			return json_encode($resp);
		}
		$row = $this->conn->query("SELECT status, username FROM users WHERE id = '{$id}' LIMIT 1")->fetch_assoc();
		if(!$row){
			return json_encode($resp);
		}
		$new = (int)$row['status'] === 1 ? 0 : 1;
		if($this->conn->query("UPDATE users SET status = '{$new}' WHERE id = '{$id}'")){
			$resp['status'] = 'success';
			$resp['msg'] = $new === 1 ? 'User activated.' : 'User deactivated.';
			$this->settings->set_flashdata('success', $resp['msg']);
			admin_activity_log('staff_status_changed', $row['username'].' → '.($new ? 'Active' : 'Inactive'));
		}
		return json_encode($resp);
	}
	function save_expense(){
		$resp = array('status' => 'failed', 'msg' => 'Unable to save expense.');
		if(!expenses_table_enabled()){
			$resp['msg'] = 'Expenses module is not installed.';
			return json_encode($resp);
		}
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$expense_date = expenses_normalize_date(isset($_POST['expense_date']) ? $_POST['expense_date'] : '', '');
		$category = isset($_POST['category']) ? trim($_POST['category']) : '';
		$description = isset($_POST['description']) ? trim($_POST['description']) : '';
		$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
		$payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : 'Cash';
		if($expense_date === '' || $category === '' || $description === '' || $amount <= 0){
			$resp['msg'] = 'Please fill all required fields with a valid amount.';
			return json_encode($resp);
		}
		if(!in_array($category, expense_categories(), true)){
			$resp['msg'] = 'Invalid expense category.';
			return json_encode($resp);
		}
		if(!in_array($payment_method, expense_payment_methods(), true)){
			$resp['msg'] = 'Invalid payment method.';
			return json_encode($resp);
		}
		$user_id = isset($_SESSION['userdata']['id']) ? (int)$_SESSION['userdata']['id'] : 0;
		$user_name = '';
		if(isset($_SESSION['userdata']['firstname'])) $user_name .= trim($_SESSION['userdata']['firstname']);
		if(isset($_SESSION['userdata']['lastname'])) $user_name .= ' '.trim($_SESSION['userdata']['lastname']);
		$user_name = trim($user_name);
		if($user_name === '' && isset($_SESSION['userdata']['username'])) $user_name = $_SESSION['userdata']['username'];
		$cat_esc = $this->conn->real_escape_string($category);
		$desc_esc = $this->conn->real_escape_string($description);
		$pay_esc = $this->conn->real_escape_string($payment_method);
		$name_esc = $this->conn->real_escape_string($user_name);
		if($id > 0){
			$sql = "UPDATE expenses SET expense_date = '{$expense_date}', category = '{$cat_esc}', description = '{$desc_esc}',
				amount = '{$amount}', payment_method = '{$pay_esc}' WHERE id = '{$id}' AND delete_flag = 0";
		}else{
			$now = date('Y-m-d H:i:s');
			$sql = "INSERT INTO expenses SET expense_date = '{$expense_date}', category = '{$cat_esc}', description = '{$desc_esc}',
				amount = '{$amount}', payment_method = '{$pay_esc}', created_by = '{$user_id}', created_by_name = '{$name_esc}', date_created = '{$now}'";
		}
		if($this->conn->query($sql)){
			$eid = $id > 0 ? $id : $this->conn->insert_id;
			$resp['status'] = 'success';
			$resp['msg'] = $id > 0 ? 'Expense updated successfully.' : 'Expense added successfully.';
			$this->settings->set_flashdata('success', $resp['msg']);
			admin_activity_log($id > 0 ? 'expense_updated' : 'expense_created', expense_format_id($eid).' | '.format_price($amount).' | '.$category);
			admin_notify('info', 'Expense Recorded', expense_format_id($eid).' — '.format_price($amount).' ('.$category.')', base_url.'admin/?page=expenses', 'expense_'.$eid);
		}else{
			$resp['msg'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function delete_expense(){
		$resp = array('status' => 'failed', 'msg' => 'Unable to delete expense.');
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		if($id <= 0) return json_encode($resp);
		$row = $this->conn->query("SELECT * FROM expenses WHERE id = '{$id}' AND delete_flag = 0")->fetch_assoc();
		if($this->conn->query("UPDATE expenses SET delete_flag = 1 WHERE id = '{$id}'")){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', 'Expense deleted successfully.');
			$detail = $row ? expense_format_id($id).' | '.format_price($row['amount']) : 'Expense #'.$id;
			admin_activity_log('expense_deleted', $detail);
		}
		return json_encode($resp);
	}
	function get_notifications(){
		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
		$items = notifications_list($limit);
		$out = array();
		foreach($items as $row){
			$out[] = array(
				'id' => (int)$row['id'],
				'type' => $row['type'],
				'title' => stripslashes($row['title']),
				'message' => stripslashes($row['message']),
				'link' => notification_resolve_link($row),
				'ref_key' => isset($row['ref_key']) ? $row['ref_key'] : '',
				'is_read' => (int)$row['is_read'],
				'date_created' => $row['date_created'],
				'time_ago' => date('M d, H:i', strtotime($row['date_created'])),
			);
		}
		return json_encode(array('status' => 'success', 'items' => $out, 'unread' => notifications_unread_count()));
	}
	function mark_notification_read(){
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		if($id > 0 && notifications_table_enabled()){
			$this->conn->query("UPDATE notifications SET is_read = 1 WHERE id = '{$id}'");
		}
		return json_encode(array('status' => 'success', 'unread' => notifications_unread_count()));
	}
	function mark_all_notifications_read(){
		if(notifications_table_enabled()){
			$this->conn->query("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
		}
		return json_encode(array('status' => 'success', 'unread' => 0));
	}
	function delete_notification(){
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		if($id > 0 && notifications_table_enabled()){
			$this->conn->query("DELETE FROM notifications WHERE id = '{$id}'");
		}
		return json_encode(array('status' => 'success', 'unread' => notifications_unread_count()));
	}
	function profit_analytics_data(){
		if(!admin_can_view_profit()){
			return json_encode(array('status' => 'failed', 'msg' => 'Access denied.'));
		}
		$range = isset($_GET['range']) ? $_GET['range'] : 'month';
		$bounds = profit_analytics_period_bounds($range);
		$daily = profit_analytics_chart_series($bounds['start'], $bounds['end'], 'day');
		$monthly = profit_analytics_chart_series(date('Y-01-01'), date('Y-m-d'), 'month');
		return json_encode(array(
			'status' => 'success',
			'daily' => $daily,
			'monthly' => $monthly,
			'summary' => array(
				'today' => dashboard_net_profit(date('Y-m-d'), date('Y-m-d')),
				'week' => dashboard_net_profit(date('Y-m-d', strtotime('monday this week')), date('Y-m-d')),
				'month' => dashboard_net_profit(date('Y-m-01'), date('Y-m-d')),
				'year' => dashboard_net_profit(date('Y-01-01'), date('Y-m-d')),
			),
		));
	}
	function create_backup(){
		$resp = array('status' => 'failed', 'msg' => 'Unable to create backup.');
		if(admin_is_cashier()){
			$resp['msg'] = 'Access denied.';
			return json_encode($resp);
		}
		if(!backup_logs_table_enabled()){
			$resp['msg'] = 'Backup module is not installed.';
			return json_encode($resp);
		}
		if(tenant_id() <= 0){
			$resp['msg'] = 'No business context for backup.';
			return json_encode($resp);
		}
		$dir = backup_dir_path();
		$now = date('Y-m-d H:i:s');
		$filename = app_backup_filename($now);
		$filepath = $dir.$filename;
		$dump = tenant_generate_sql_dump($this->conn);
		if($dump === false || trim($dump) === ''){
			$dump = "-- Kalmoy POS tenant backup (business_id=".tenant_id().")\n-- Empty business data snapshot\n";
		}
		if(file_put_contents($filepath, $dump) === false){
			$resp['msg'] = 'Failed to write backup file.';
			return json_encode($resp);
		}
		$size = filesize($filepath);
		$user_id = isset($_SESSION['userdata']['id']) ? (int)$_SESSION['userdata']['id'] : 0;
		$user_name = dashboard_user_display_name();
		$bid = tenant_id();
		$name_esc = $this->conn->real_escape_string($user_name);
		$file_esc = $this->conn->real_escape_string($filename);
		$this->conn->query("INSERT INTO backup_logs SET business_id = '{$bid}', filename = '{$file_esc}', file_size = '{$size}',
			created_by = '{$user_id}', created_by_name = '{$name_esc}', status = 'success', message = 'Tenant backup created successfully', date_created = '{$now}'");
		admin_activity_log('backup_created', $filename.' ('.format_file_size($size).')');
		admin_notify('success', 'Backup Completed', 'Database backup '.$filename.' created successfully.', base_url.'admin/?page=backup', 'backup_'.$filename);
		$resp['status'] = 'success';
		$resp['msg'] = 'Backup created successfully.';
		$this->settings->set_flashdata('success', $resp['msg']);
		return json_encode($resp);
	}
	function generate_sql_dump(){
		$out = "-- CBPOS Database Backup\n-- Date: ".date('Y-m-d H:i:s')."\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";
		$tables = $this->conn->query("SHOW TABLES");
		if(!$tables) return false;
		while($trow = $tables->fetch_array()){
			$table = $trow[0];
			$create = $this->conn->query("SHOW CREATE TABLE `{$table}`");
			if(!$create) continue;
			$crow = $create->fetch_array();
			$out .= "DROP TABLE IF EXISTS `{$table}`;\n".$crow[1].";\n\n";
			$rows = $this->conn->query("SELECT * FROM `{$table}`");
			if($rows && $rows->num_rows > 0){
				while($row = $rows->fetch_assoc()){
					$cols = array_keys($row);
					$vals = array();
					foreach($row as $v){
						if($v === null) $vals[] = 'NULL';
						else $vals[] = "'".$this->conn->real_escape_string($v)."'";
					}
					$out .= "INSERT INTO `{$table}` (`".implode('`,`', $cols)."`) VALUES (".implode(', ', $vals).");\n";
				}
				$out .= "\n";
			}
		}
		$out .= "SET FOREIGN_KEY_CHECKS=1;\n";
		return $out;
	}
	function delete_backup(){
		$resp = array('status' => 'failed', 'msg' => 'Unable to delete backup.');
		if(admin_is_cashier()) return json_encode(array('status' => 'failed', 'msg' => 'Access denied.'));
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$row = $this->conn->query("SELECT * FROM backup_logs WHERE id = '{$id}'".tenant_sql())->fetch_assoc();
		if(!$row) return json_encode($resp);
		$path = backup_dir_path().$row['filename'];
		if(is_file($path)) @unlink($path);
		$this->conn->query("DELETE FROM backup_logs WHERE id = '{$id}'".tenant_sql());
		admin_activity_log('backup_deleted', $row['filename']);
		$resp['status'] = 'success';
		$this->settings->set_flashdata('success', 'Backup deleted.');
		return json_encode($resp);
	}
	function restore_backup(){
		$resp = array('status' => 'failed', 'msg' => 'Restore is disabled in multi-tenant mode. Contact Kalmoy Tech Solutions for assistance.');
		return json_encode($resp);
	}
	function download_backup(){
		if(admin_is_cashier()){
			header('HTTP/1.1 403 Forbidden');
			exit;
		}
		$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
		$row = $this->conn->query("SELECT * FROM backup_logs WHERE id = '{$id}'".tenant_sql())->fetch_assoc();
		if(!$row){
			header('HTTP/1.1 404 Not Found');
			exit;
		}
		$path = backup_dir_path().$row['filename'];
		if(!is_file($path)){
			header('HTTP/1.1 404 Not Found');
			exit;
		}
		header('Content-Type: application/octet-stream');
		$download_name = app_backup_download_filename($row['filename'], $row['date_created'] ?? null);
		header('Content-Disposition: attachment; filename="'.$download_name.'"');
		header('Content-Length: '.filesize($path));
		readfile($path);
		exit;
	}
	function dashboard_chart_data(){
		$range = isset($_GET['range']) ? strtolower(trim($_GET['range'])) : '7d';
		$allowed = array('7d', '30d', 'week', 'month', 'year', 'today', 'lifetime');
		if(!in_array($range, $allowed, true)) $range = '7d';
		$data = dashboard_chart_analytics($range);
		$show_profit = admin_can_view_profit();
		if(!$show_profit){
			$data['profit'] = array_fill(0, count($data['labels']), 0);
		}
		return json_encode(array(
			'status' => 'success',
			'data' => $data,
			'show_profit' => $show_profit,
		));
	}
	function inventory_import_template(){
		if(!isset($_SESSION['userdata']) || (int)$_SESSION['userdata']['login_type'] !== 1){
			header('HTTP/1.1 403 Forbidden');
			exit('Access denied.');
		}
		if(admin_cashier_api_denied('inventory_import_preview')){
			header('HTTP/1.1 403 Forbidden');
			exit('Access denied.');
		}
		require_once('../classes/InventoryImport.php');
		$importer = new InventoryImport($this->conn);
		$importer->downloadSimpleTemplate();
	}
	function inventory_export_existing(){
		if(!isset($_SESSION['userdata']) || (int)$_SESSION['userdata']['login_type'] !== 1){
			header('HTTP/1.1 403 Forbidden');
			exit('Access denied.');
		}
		if(admin_cashier_api_denied('inventory_import_preview')){
			header('HTTP/1.1 403 Forbidden');
			exit('Access denied.');
		}
		require_once('../classes/InventoryImport.php');
		$importer = new InventoryImport($this->conn);
		$importer->downloadExistingProducts();
	}
	function inventory_import_preview(){
		if(!isset($_SESSION['userdata']) || (int)$_SESSION['userdata']['login_type'] !== 1){
			return json_encode(array('status' => 'failed', 'msg' => 'Access denied.'));
		}
		if(admin_cashier_api_denied('inventory_import_preview')){
			return json_encode(array('status' => 'failed', 'msg' => 'Access denied.'));
		}
		require_once('../classes/InventoryImport.php');
		$importer = new InventoryImport($this->conn);
		if(!isset($_FILES['import_file'])){
			return json_encode(array('status' => 'failed', 'msg' => 'No file uploaded.'));
		}
		return json_encode($importer->handleUpload($_FILES['import_file']));
	}
	function inventory_import_commit(){
		if(!isset($_SESSION['userdata']) || (int)$_SESSION['userdata']['login_type'] !== 1){
			return json_encode(array('status' => 'failed', 'msg' => 'Access denied.'));
		}
		if(admin_cashier_api_denied('inventory_import_commit')){
			return json_encode(array('status' => 'failed', 'msg' => 'Access denied.'));
		}
		require_once('../classes/InventoryImport.php');
		$importer = new InventoryImport($this->conn);
		$token = isset($_POST['token']) ? trim($_POST['token']) : '';
		$result = $importer->commit($token);
		if(!empty($result['success'])){
			$this->settings->set_flashdata('success', $result['message']);
		}
		return json_encode($result);
	}
	private function sales_edit_require(){
		if($this->settings->userdata('login_type') != 1){
			return json_encode(['status'=>'failed','msg'=>'Access denied.']);
		}
		if(admin_cashier_api_denied('sales_update_order')){
			return json_encode(['status'=>'failed','msg'=>'Access denied.']);
		}
		return false;
	}
	private function get_inventory_stock_for_edit($inventory_id, $order_id){
		$inv = $this->get_inventory_stock($inventory_id);
		if(!$inv) return null;
		$oid = (int)$order_id;
		$iid = (int)$inventory_id;
		$q = $this->conn->query("SELECT COALESCE(SUM(quantity),0) AS qty FROM order_list WHERE order_id = '{$oid}' AND inventory_id = '{$iid}'");
		$extra = ($q && $q->num_rows) ? (float)$q->fetch_assoc()['qty'] : 0;
		$inv['stock'] = (float)$inv['stock'] + $extra;
		return $inv;
	}
	function sales_search_product(){
		if($denied = $this->sales_edit_require()) return $denied;
		$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
		$q = isset($_POST['q']) ? trim($_POST['q']) : '';
		if($q === ''){
			return json_encode(['status'=>'failed','msg'=>'Enter a barcode or product name.']);
		}
		$q_esc = $this->conn->real_escape_string($q);
		$like = '%'.$q_esc.'%';
		$sold_sub = inventory_sold_subquery_sql();
		$sql = "SELECT i.id AS inventory_id, i.variant, i.price, p.name, p.barcode, b.name AS bname,
			(i.quantity - IFNULL(sold.qty, 0)) AS stock
			FROM inventory i
			INNER JOIN products p ON p.id = i.product_id
			INNER JOIN brands b ON p.brand_id = b.id
			LEFT JOIN {$sold_sub} sold ON sold.inventory_id = i.id
			WHERE p.delete_flag = 0 AND p.status = 1".tenant_sql('i')."
			AND (p.barcode = '{$q_esc}' OR p.name LIKE '{$like}' OR p.barcode LIKE '{$like}')
			ORDER BY (p.barcode = '{$q_esc}') DESC, p.name ASC, i.variant ASC
			LIMIT 25";
		$qry = $this->conn->query($sql);
		if($this->capture_err()) return $this->capture_err();
		$items = [];
		while($qry && ($row = $qry->fetch_assoc())){
			$stock = (float)$row['stock'];
			if($order_id > 0){
				$inv_edit = $this->get_inventory_stock_for_edit((int)$row['inventory_id'], $order_id);
				if($inv_edit) $stock = (float)$inv_edit['stock'];
			}
			$items[] = [
				'inventory_id' => (int)$row['inventory_id'],
				'variant' => $row['variant'],
				'price' => (float)$row['price'],
				'name' => $row['name'],
				'barcode' => $row['barcode'],
				'bname' => $row['bname'],
				'stock' => $stock,
			];
		}
		return json_encode(['status'=>'success','items'=>$items]);
	}
	function sales_update_order(){
		if($denied = $this->sales_edit_require()) return $denied;
		$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
		if($order_id <= 0){
			return json_encode(['status'=>'failed','msg'=>'Invalid order.']);
		}
		$oq = $this->conn->query("SELECT o.*, s.id AS sale_id, s.date_created AS sale_date
			FROM orders o
			INNER JOIN sales s ON s.order_id = o.id
			WHERE o.id = '{$order_id}' AND o.status = 3
			LIMIT 1");
		if(!$oq || $oq->num_rows === 0){
			return json_encode(['status'=>'failed','msg'=>'Sale not found or cannot be edited.']);
		}
		$order = $oq->fetch_assoc();
		$ref_code = $order['ref_code'];
		$items_raw = isset($_POST['items']) ? $_POST['items'] : '';
		$items = is_string($items_raw) ? json_decode($items_raw, true) : $items_raw;
		if(!is_array($items)){
			return json_encode(['status'=>'failed','msg'=>'Invalid line items.']);
		}
		$payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
		$allowed_payments = ['Cash','M-Pesa','Mixed','Debt'];
		if(!in_array($payment_method, $allowed_payments)){
			return json_encode(['status'=>'failed','msg'=>'Invalid payment method.']);
		}
		$is_debt = ($payment_method === 'Debt');
		if($is_debt && admin_is_cashier() && !admin_cashier_has_permission('debt_sale')){
			return json_encode(['status'=>'failed','msg'=>'You do not have permission for credit sales.']);
		}
		$registered_client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
		$customer_name = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
		$client_id = 0;
		if($is_debt){
			if($registered_client_id <= 0 || CustomerDebtService::is_walkin_client($this->conn, $registered_client_id)){
				return json_encode(['status'=>'failed','msg'=>'Select a registered customer for credit sales.']);
			}
			$client_id = $registered_client_id;
		}else{
			if($registered_client_id > 0 && !CustomerDebtService::is_walkin_client($this->conn, $registered_client_id)){
				$client_id = $registered_client_id;
			}else{
				$client_id = $this->get_pos_client_id();
			}
		}
		if($client_id <= 0){
			return json_encode(['status'=>'failed','msg'=>'Unable to resolve customer.']);
		}
		$active_lines = [];
		$qty_by_inventory = [];
		foreach($items as $item){
			if(!empty($item['remove'])) continue;
			$order_list_id = isset($item['order_list_id']) ? (int)$item['order_list_id'] : 0;
			$inventory_id = isset($item['inventory_id']) ? (int)$item['inventory_id'] : 0;
			$quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;
			$price = isset($item['price']) ? (float)str_replace(',', '', $item['price']) : 0;
			if($inventory_id <= 0 || $quantity <= 0){
				return json_encode(['status'=>'failed','msg'=>'Quantity must be greater than zero.']);
			}
			if($price < 0){
				return json_encode(['status'=>'failed','msg'=>'Unit price cannot be negative.']);
			}
			if($order_list_id > 0){
				$chk = $this->conn->query("SELECT id FROM order_list WHERE id = '{$order_list_id}' AND order_id = '{$order_id}' LIMIT 1");
				if(!$chk || $chk->num_rows === 0){
					return json_encode(['status'=>'failed','msg'=>'Invalid order line.']);
				}
			}
			$inv = $this->get_inventory_stock_for_edit($inventory_id, $order_id);
			if(!$inv){
				return json_encode(['status'=>'failed','msg'=>'Product not found or unavailable.']);
			}
			if(!isset($qty_by_inventory[$inventory_id])) $qty_by_inventory[$inventory_id] = 0;
			$qty_by_inventory[$inventory_id] += $quantity;
			$line_total = round($price * $quantity, 2);
			$active_lines[] = [
				'order_list_id' => $order_list_id,
				'inventory_id' => $inventory_id,
				'quantity' => $quantity,
				'price' => $price,
				'total' => $line_total,
				'cost_price' => $this->snapshot_cost_price($inv),
				'name' => $inv['name'],
			];
		}
		if(count($active_lines) === 0){
			return json_encode(['status'=>'failed','msg'=>'At least one line item is required.']);
		}
		foreach($qty_by_inventory as $inventory_id => $total_qty){
			$inv = $this->get_inventory_stock_for_edit($inventory_id, $order_id);
			if(!$inv || $total_qty > $inv['stock']){
				$label = $inv ? $inv['name'].' ('.$inv['variant'].')' : 'Product';
				$avail = $inv ? format_num($inv['stock']) : '0';
				return json_encode(['status'=>'failed','msg'=>$label.' exceeds available stock ('.$avail.').']);
			}
		}
		$subtotal = 0;
		foreach($active_lines as $line){
			$subtotal += $line['total'];
		}
		$discount_percent = isset($_POST['discount_percent']) ? (float)$_POST['discount_percent'] : 0;
		$discount_ksh = isset($_POST['discount_ksh']) ? (float)str_replace(',', '', $_POST['discount_ksh']) : 0;
		if($discount_percent < 0) $discount_percent = 0;
		if($discount_percent > 100) $discount_percent = 100;
		if($discount_ksh < 0) $discount_ksh = 0;
		$discount_total = min($subtotal, ($subtotal * $discount_percent / 100) + $discount_ksh);
		$amount = isset($_POST['amount']) ? (float)str_replace(',', '', $_POST['amount']) : 0;
		$expected_amount = max(0, round($subtotal - $discount_total, 2));
		if(abs($expected_amount - $amount) > 0.01){
			return json_encode(['status'=>'failed','msg'=>'Total mismatch. Please refresh and try again.']);
		}
		$amount = $expected_amount;
		$cash_part = isset($_POST['cash_amount']) ? (float)str_replace(',', '', $_POST['cash_amount']) : 0;
		$mpesa_part = isset($_POST['mpesa_amount']) ? (float)str_replace(',', '', $_POST['mpesa_amount']) : 0;
		if($payment_method === 'Mixed'){
			if(abs(($cash_part + $mpesa_part) - $amount) > 0.01){
				return json_encode(['status'=>'failed','msg'=>'Mixed payment must equal the sale total.']);
			}
			if($cash_part <= 0 && $mpesa_part <= 0){
				return json_encode(['status'=>'failed','msg'=>'Enter cash and/or M-Pesa amounts.']);
			}
		}
		$delivery_address = 'In-Store POS';
		if($payment_method === 'Mixed'){
			$delivery_address .= ' | Mixed: Cash '.number_format($cash_part, 2).' + M-Pesa '.number_format($mpesa_part, 2);
		}elseif($customer_name !== '' && CustomerDebtService::is_walkin_client($this->conn, $client_id)){
			$delivery_address .= ' - Customer: '.$this->conn->real_escape_string($customer_name);
		}
		$payment_esc = $this->conn->real_escape_string($payment_method);
		$paid = $is_debt ? 0 : 1;
		$has_line_cost = db_table_has_column('order_list', 'cost_price');
		$has_discount_col = db_table_has_column('orders', 'discount_total');
		$this->conn->begin_transaction();
		$existing_ids = [];
		$lq = $this->conn->query("SELECT id FROM order_list WHERE order_id = '{$order_id}'");
		while($lq && ($lr = $lq->fetch_assoc())){
			$existing_ids[(int)$lr['id']] = true;
		}
		$kept_ids = [];
		foreach($items as $item){
			if(!empty($item['remove'])){
				$olid = isset($item['order_list_id']) ? (int)$item['order_list_id'] : 0;
				if($olid > 0 && isset($existing_ids[$olid])){
					if(!$this->conn->query("DELETE FROM order_list WHERE id = '{$olid}' AND order_id = '{$order_id}'")){
						$this->conn->rollback();
						return json_encode(['status'=>'failed','msg'=>'Failed to remove line item.','err'=>$this->conn->error]);
					}
				}
				continue;
			}
		}
		foreach($active_lines as $line){
			$olid = (int)$line['order_list_id'];
			$iid = (int)$line['inventory_id'];
			$qty = (int)$line['quantity'];
			$price = $line['price'];
			$total = $line['total'];
			$cost_sql = 'NULL';
			if($has_line_cost && $line['cost_price'] !== null){
				$cost_sql = "'".$this->conn->real_escape_string($line['cost_price'])."'";
			}
			if($olid > 0 && isset($existing_ids[$olid])){
				$kept_ids[$olid] = true;
				if($has_line_cost){
					$sql = "UPDATE order_list SET inventory_id = '{$iid}', quantity = '{$qty}', price = '{$price}', total = '{$total}', cost_price = {$cost_sql} WHERE id = '{$olid}' AND order_id = '{$order_id}'";
				}else{
					$sql = "UPDATE order_list SET inventory_id = '{$iid}', quantity = '{$qty}', price = '{$price}', total = '{$total}' WHERE id = '{$olid}' AND order_id = '{$order_id}'";
				}
			}else{
				if($has_line_cost){
					$sql = "INSERT INTO order_list SET order_id = '{$order_id}', inventory_id = '{$iid}', quantity = '{$qty}', price = '{$price}', total = '{$total}', cost_price = {$cost_sql}";
				}else{
					$sql = "INSERT INTO order_list SET order_id = '{$order_id}', inventory_id = '{$iid}', quantity = '{$qty}', price = '{$price}', total = '{$total}'";
				}
			}
			if(!$this->conn->query($sql)){
				$this->conn->rollback();
				return json_encode(['status'=>'failed','msg'=>'Failed to save line items.','err'=>$this->conn->error]);
			}
		}
		foreach(array_keys($existing_ids) as $eid){
			if(!isset($kept_ids[$eid])){
				$removed_in_payload = false;
				foreach($items as $item){
					if(!empty($item['remove']) && (int)$item['order_list_id'] === $eid){
						$removed_in_payload = true;
						break;
					}
				}
				if(!$removed_in_payload){
					if(!$this->conn->query("DELETE FROM order_list WHERE id = '{$eid}' AND order_id = '{$order_id}'")){
						$this->conn->rollback();
						return json_encode(['status'=>'failed','msg'=>'Failed to update line items.','err'=>$this->conn->error]);
					}
				}
			}
		}
		$discount_sql = $has_discount_col ? ", discount_total = '{$discount_total}'" : '';
		$order_sql = "UPDATE orders SET
			client_id = '{$client_id}',
			delivery_address = '".$this->conn->real_escape_string($delivery_address)."',
			payment_method = '{$payment_esc}',
			amount = '{$amount}',
			paid = '{$paid}'{$discount_sql}
			WHERE id = '{$order_id}'";
		if(!$this->conn->query($order_sql)){
			$this->conn->rollback();
			return json_encode(['status'=>'failed','msg'=>'Failed to update order.','err'=>$this->conn->error]);
		}
		if(!$this->conn->query("UPDATE sales SET total_amount = '{$amount}' WHERE order_id = '{$order_id}'")){
			$this->conn->rollback();
			return json_encode(['status'=>'failed','msg'=>'Failed to update sale record.','err'=>$this->conn->error]);
		}
		$debt_result = CustomerDebtService::sync_order_debt($this->conn, $order_id, $client_id, $amount, $payment_method);
		if($debt_result['status'] !== 'success'){
			$this->conn->rollback();
			return json_encode(['status'=>'failed','msg'=>isset($debt_result['msg']) ? $debt_result['msg'] : 'Failed to sync debt.']);
		}
		$this->conn->commit();
		$line_summary = [];
		foreach($active_lines as $line){
			$line_summary[] = $line['name'].' x'.$line['quantity'];
		}
		admin_activity_log('sale_updated', $ref_code.' | '.format_price($amount).' | '.$payment_method.' | '.implode('; ', $line_summary));
		return json_encode([
			'status' => 'success',
			'msg' => 'Sale updated successfully.',
			'ref_code' => $ref_code,
			'amount' => $amount,
		]);
	}
	function clean_business_data(){
		$resp = array('status' => 'failed', 'msg' => 'Data clean actions are disabled in multi-tenant mode. Contact Kalmoy Tech Solutions for assistance.');
		return json_encode($resp);
	}
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
$admin_only_actions = array(
	'save_brand','delete_brand','save_category','delete_category','save_sub_category','delete_sub_category',
	'save_product','delete_product','save_inventory','delete_inventory','delete_img',
	'inventory_import_preview','inventory_import_commit',
	'pay_order','update_order_status','delete_order','update_client','delete_client',	'save_cashier_permissions',
	'sales_search_product','sales_update_order',
	'create_backup','delete_backup','restore_backup','download_backup','profit_analytics_data',
	'clean_business_data',
	'save_staff_user','delete_staff_user','reset_staff_password','toggle_staff_status','save_user_permissions'
);
if(in_array($action, $admin_only_actions, true) && admin_cashier_api_denied($action)){
	echo json_encode(['status'=>'failed','msg'=>'Access denied.']);
	exit;
}
$public_api_actions = array('register','add_to_cart','update_cart_qty','delete_cart','empty_cart');
if(!in_array($action, $public_api_actions, true)){
	tenant_api_guard();
}
switch ($action) {
	case 'save_brand':
		echo $Master->save_brand();
	break;
	case 'delete_brand':
		echo $Master->delete_brand();
	break;
	case 'save_category':
		echo $Master->save_category();
	break;
	case 'delete_category':
		echo $Master->delete_category();
	break;
	case 'save_sub_category':
		echo $Master->save_sub_category();
	break;
	case 'delete_sub_category':
		echo $Master->delete_sub_category();
	break;
	case 'save_product':
		echo $Master->save_product();
	break;
	case 'delete_product':
		echo $Master->delete_product();
	break;
	
	case 'save_inventory':
		echo $Master->save_inventory();
	break;
	case 'delete_inventory':
		echo $Master->delete_inventory();
	break;
	case 'register':
		echo $Master->register();
	break;
	case 'add_to_cart':
		echo $Master->add_to_cart();
	break;
	case 'update_cart_qty':
		echo $Master->update_cart_qty();
	break;
	case 'delete_cart':
		echo $Master->delete_cart();
	break;
	case 'empty_cart':
		echo $Master->empty_cart();
	break;
	case 'delete_img':
		echo $Master->delete_img();
	break;
	case 'place_order':
		echo $Master->place_order();
	break;
	case 'update_order_status':
		echo $Master->update_order_status();
	break;
	case 'pay_order':
		echo $Master->pay_order();
	break;
	case 'update_account':
		echo $Master->update_account();
	break;
	case 'update_client':
		echo $Master->update_client();
	break;
	case 'delete_order':
		echo $Master->delete_order();
	break;
	case 'delete_client':
		echo $Master->delete_client();
	break;
	case 'pos_search_product':
		echo $Master->pos_search_product();
	break;
	case 'pos_complete_sale':
		echo $Master->pos_complete_sale();
	break;
	case 'pos_search_customers':
		echo $Master->pos_search_customers();
	break;
	case 'debt_receive_payment':
		echo $Master->debt_receive_payment();
	break;
	case 'debt_delete_payment':
		echo $Master->debt_delete_payment();
	break;
	case 'debt_client_summary':
		echo $Master->debt_client_summary();
	break;
	case 'save_cashier_permissions':
		echo $Master->save_cashier_permissions();
	break;
	case 'save_user_permissions':
		echo $Master->save_user_permissions();
	break;
	case 'save_staff_user':
		echo $Master->save_staff_user();
	break;
	case 'delete_staff_user':
		echo $Master->delete_staff_user();
	break;
	case 'reset_staff_password':
		echo $Master->reset_staff_password();
	break;
	case 'toggle_staff_status':
		echo $Master->toggle_staff_status();
	break;
	case 'dashboard_chart_data':
		echo $Master->dashboard_chart_data();
	break;
	case 'save_expense':
		echo $Master->save_expense();
	break;
	case 'delete_expense':
		echo $Master->delete_expense();
	break;
	case 'get_notifications':
		echo $Master->get_notifications();
	break;
	case 'mark_notification_read':
		echo $Master->mark_notification_read();
	break;
	case 'mark_all_notifications_read':
		echo $Master->mark_all_notifications_read();
	break;
	case 'delete_notification':
		echo $Master->delete_notification();
	break;
	case 'profit_analytics_data':
		echo $Master->profit_analytics_data();
	break;
	case 'create_backup':
		echo $Master->create_backup();
	break;
	case 'delete_backup':
		echo $Master->delete_backup();
	break;
	case 'restore_backup':
		echo $Master->restore_backup();
	break;
	case 'download_backup':
		$Master->download_backup();
	break;
	case 'inventory_import_template':
		$Master->inventory_import_template();
	break;
	case 'inventory_export_existing':
		$Master->inventory_export_existing();
	break;
	case 'inventory_import_preview':
		echo $Master->inventory_import_preview();
	break;
	case 'inventory_import_commit':
		echo $Master->inventory_import_commit();
	break;
	case 'clean_business_data':
		echo $Master->clean_business_data();
	break;
	case 'sales_search_product':
		echo $Master->sales_search_product();
	break;
	case 'sales_update_order':
		echo $Master->sales_update_order();
	break;
	default:
		// echo $sysset->index();
		break;
}