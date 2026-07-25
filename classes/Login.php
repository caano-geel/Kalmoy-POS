<?php
require_once '../config.php';
class Login extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;

		parent::__construct();
		if (defined('APP_ENV') && APP_ENV === 'local') {
			ini_set('display_errors', '1');
		}
	}
	public function __destruct(){
		parent::__destruct();
	}
	public function index(){
		echo "<h1>Access Denied</h1> <a href='".base_url."'>Go Back.</a>";
	}
	public function login(){
		extract($_POST);
		$username = isset($username) ? trim($username) : '';
		$password = isset($password) ? $password : '';
		if($username === '' || $password === ''){
			return json_encode(array('status'=>'incorrect','msg'=>'Username and password are required.'));
		}

		$stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
		$stmt->bind_param('s', $username);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows > 0){
			$user = $result->fetch_assoc();
			if(function_exists('users_table_has_status') && users_table_has_status() && (int)$user['status'] !== 1){
				return json_encode(array('status'=>'incorrect','msg'=>'Your account is inactive. Contact the administrator.'));
			}
			if(!app_verify_password($password, $user['password'])){
				return json_encode(array('status'=>'incorrect','msg'=>'Incorrect username or password.'));
			}
			$business_id = (int)($user['business_id'] ?? 0);
			if($business_id <= 0){
				return json_encode(array('status'=>'incorrect','msg'=>'Account is not linked to a business.'));
			}
			$bq = $this->conn->query("SELECT status FROM businesses WHERE id = '{$business_id}' LIMIT 1");
			if(!$bq || !$bq->num_rows){
				return json_encode(array('status'=>'incorrect','msg'=>'Business account not found.'));
			}
			$bstatus = $bq->fetch_assoc()['status'];
			if(in_array($bstatus, array('suspended','inactive','cancelled'), true)){
				return json_encode(array('status'=>'incorrect','msg'=>'This business account is suspended. Contact support.'));
			}
			if(isset($_SESSION['platform_user'])){
				unset($_SESSION['platform_user']);
			}
			session_regenerate_id(true);
			if(strlen($user['password']) === 32 && ctype_xdigit($user['password'])){
				app_upgrade_password_hash((int)$user['id'], $password);
			}
			foreach($user as $k => $v){
				if(!is_numeric($k) && $k != 'password' && $k != 'permissions'){
					$this->settings->set_userdata($k,$v);
				}
			}
			$this->settings->set_userdata('business_id', $business_id);
			if((int)$user['type'] === 2 && function_exists('admin_decode_user_permissions')){
				$perms = admin_decode_user_permissions($user['permissions'] ?? '');
				if($perms !== null){
					$this->settings->set_userdata('permissions', $perms);
				}
			}
			$this->settings->set_userdata('login_type',1);
			$this->settings->load_system_info();
			$sub = tenant_subscription_status($business_id);
			if(!$sub['allowed']){
				$this->settings->set_userdata('subscription_blocked', 1);
			} else {
				unset($_SESSION['userdata']['subscription_blocked']);
			}
			$this->conn->query("UPDATE users SET last_login = NOW() WHERE id = '".(int)$user['id']."'");
			$_SESSION['admin_post_login_redirect'] = 1;
			admin_activity_log('login', 'Signed in to admin panel', (int)$user['id'], $user['username']);
			if(!$sub['allowed']){
				return json_encode(array('status'=>'success','redirect'=>'admin/?page=subscription_expired'));
			}
			$redirect = admin_login_landing_path();
			return json_encode(array(
				'status' => 'success',
				'redirect' => $redirect !== null ? $redirect : 'admin/'
			));
		}
		return json_encode(array('status'=>'incorrect','msg'=>'Incorrect username or password.'));
	}
	public function logout(){
		if(isset($_SESSION['userdata']['username'])){
			admin_activity_log('logout', 'Signed out of admin panel');
		}
		if($this->settings->sess_des()){
			redirect('admin/login.php');
		}
	}
	function login_user(){
		extract($_POST);
		$stmt = $this->conn->prepare("SELECT * from clients where email = ? and `password` = ? and delete_flag = 0 ");
		$password = md5($password);
		$stmt->bind_param('ss',$email,$password);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows > 0){
			$res = $result->fetch_array();
			if($res['status'] == 1){
				foreach($res as $k => $v){
					$this->settings->set_userdata($k,$v);
				}
				$this->settings->set_userdata('login_type',2);
				$resp['status'] = 'success';
			}else{
				$resp['status'] = 'failed';
				$resp['msg'] = 'Your Account has been blocked.';
			}
		}else{
		$resp['status'] = 'failed';
		$resp['msg'] = 'Incorrect Email or Password';
		}
		if($this->conn->error){
			$resp['status'] = 'failed';
			$resp['_error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
}
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$auth = new Login();
switch ($action) {
	case 'login':
		echo $auth->login();
		break;
	case 'login_user':
		echo $auth->login_user();
		break;
	case 'logout':
		echo $auth->logout();
		break;
	default:
		echo $auth->index();
		break;
}

