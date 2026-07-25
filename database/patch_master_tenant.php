<?php
/**
 * Apply tenant scoping patches to Master.php (run once).
 */
$file = __DIR__ . '/../classes/Master.php';
$c = file_get_contents($file);
if ($c === false) {
    fwrite(STDERR, "Cannot read Master.php\n");
    exit(1);
}

$helpers = <<<'PHP'
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
PHP;

if (strpos($c, 'function _bid()') === false) {
    $c = str_replace(
        "public function __destruct(){\n\t\tparent::__destruct();\n\t}",
        "public function __destruct(){\n\t\tparent::__destruct();\n\t}\n" . $helpers,
        $c
    );
}

if (strpos($c, 'tenant_api_guard') === false) {
    $guard = "\n\$public_api_actions = array('register','add_to_cart','update_cart_qty','delete_cart','empty_cart');\nif(!in_array(\$action, \$public_api_actions, true)){\n\ttenant_api_guard();\n}\n";
    $c = str_replace('$switch ($action) {', $guard . '$switch ($action) {', $c);
}

$c = preg_replace(
    '/INSERT INTO `(\w+)` set \{\$data\}/',
    'INSERT INTO `$1` set business_id = \'{$this->_bid()}\', {$data}',
    $c
);

$c = preg_replace(
    '/UPDATE `(\w+)` set \{\$data\} where id = \'\{\$id\}\'/',
    'UPDATE `$1` set {$data} where id = \'{$id}\'{$this->_ts()}',
    $c
);

$c = preg_replace(
    '/DELETE FROM `(\w+)` where id = \'\{\$id\}\'/',
    'DELETE FROM `$1` where id = \'{$id}\'{$this->_ts()}',
    $c
);

$c = preg_replace(
    '/UPDATE `(\w+)` set delete_flag = 1 where id = \'\{\$id\}\'/',
    'UPDATE `$1` set delete_flag = 1 where id = \'{$id}\'{$this->_ts()}',
    $c
);

$tables = array('brands', 'categories', 'products', 'inventory', 'clients', 'orders', 'users', 'expenses', 'notifications');
foreach ($tables as $t) {
    $c = preg_replace(
        '/FROM `' . $t . '` where(?!.*business_id)(?!.*_ts\(\))/i',
        'FROM `' . $t . '` where business_id = \'"\'.$this->_bid().\'"\' and ',
        $c,
        -1,
        $count
    );
}

$deleteFuncs = array(
    'function delete_brand()' => 'brands',
    'function delete_category()' => 'categories',
    'function delete_product()' => 'products',
    'function delete_inventory()' => 'inventory',
    'function delete_order()' => 'orders',
    'function delete_client()' => 'clients',
    'function delete_expense()' => 'expenses',
    'function delete_notification()' => 'notifications',
    'function delete_staff_user()' => 'users',
);
foreach ($deleteFuncs as $fn => $table) {
    $needle = "\t" . $fn . "{\n\t\textract(\$_POST);";
    $replace = "\t" . $fn . "{\n\t\textract(\$_POST);\n\t\t\$this->_own('{$table}', \$id);";
    if (strpos($c, $replace) === false) {
        $c = str_replace($needle, $replace, $c);
    }
}

file_put_contents($file, $c);
echo "Master.php patched.\n";
