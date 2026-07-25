<?php
require_once(__DIR__ . '/../libs/AshSimpleXlsx.php');
require_once(__DIR__ . '/../libs/AshInventoryTemplateXlsx.php');

class InventoryImport {
    const SESSION_KEY = 'inventory_import_batch';
    const TEMP_SUBDIR = 'uploads/temp/inventory_import';
    const SESSION_TTL = 1800;

    private $conn;
    private $can_set_cost;

    public static function templateHeaders(){
        return array(
            'Product Name',
            'Barcode',
            'Brand',
            'Category',
            'Variant',
            'Retail Price',
            'Unit Cost',
            'Quantity',
            'Low Stock Alert',
            'Status',
        );
    }

    public function __construct($conn){
        $this->conn = $conn;
        $this->can_set_cost = function_exists('admin_can_view_profit') ? admin_can_view_profit() : true;
    }

    public function tempDir(){
        $dir = base_app . self::TEMP_SUBDIR;
        if(!is_dir($dir)){
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public function downloadSimpleTemplate(){
        require_once __DIR__ . '/ModuleExportService.php';
        $svc = new ModuleExportService($this->conn);
        $svc->exportInventoryTemplate('xlsx');
    }

    public function downloadExistingProducts(){
        require_once __DIR__ . '/ModuleExportService.php';
        $svc = new ModuleExportService($this->conn);
        $svc->exportInventory('xlsx');
    }

    /** @deprecated Use downloadSimpleTemplate() */
    public function downloadTemplate(){
        $this->downloadSimpleTemplate();
    }

    /** Normalize barcode as string; detect Excel corruption. */
    public static function normalizeBarcode($raw, &$warning = null){
        $warning = null;
        $raw = trim((string)$raw);
        if($raw === ''){
            return '';
        }
        $original = $raw;

        if(preg_match('/^[\d.]+[eE][+\-]?\d+$/', $raw)){
            $warning = 'Barcode appears as scientific notation ('.$original.'). Excel likely corrupted the value. Format the Barcode column as Text (@) and re-enter the full code.';
            $num = (float)$raw;
            if(abs($num) >= 1){
                if(function_exists('number_format')){
                    $raw = number_format($num, 0, '', '');
                }else{
                    $raw = sprintf('%.0f', $num);
                }
            }
        }elseif(preg_match('/^\d+\.\d+$/', $raw)){
            $f = (float)$raw;
            if($f == floor($f)){
                $raw = sprintf('%.0f', $f);
                if($original !== $raw){
                    $warning = 'Barcode was read as a decimal number. Verify the full barcode is correct.';
                }
            }
        }elseif(is_numeric($raw) && strpos($raw, '.') === false && stripos($raw, 'e') === false){
            $raw = preg_replace('/\D/', '', $raw) === $raw ? $raw : $raw;
        }

        $raw = trim($raw);
        if($raw === ''){
            return '';
        }

        if(preg_match('/^\d+$/', $raw)){
            if(strlen($raw) < 4){
                $warning = ($warning ? $warning.' ' : '').'Barcode is unusually short ('.strlen($raw).' digits). Confirm Excel did not truncate it.';
            }elseif(strlen($raw) > 8 && strlen($raw) < 12 && preg_match('/^[\d.]+[eE]/', $original)){
                $warning = ($warning ? $warning.' ' : '').'Long numeric barcode may have lost precision due to Excel number formatting.';
            }
        }

        return $raw;
    }

    private function barcodeConflictReason($parsed, $product, $type = 'name'){
        $existingName = stripslashes($product['name']);
        $existingBc = !empty($product['barcode']) ? stripslashes($product['barcode']) : '(none)';
        $importedBc = $parsed['barcode'] !== '' ? $parsed['barcode'] : '(none)';
        if($type === 'barcode_owner'){
            return 'Skipped: imported barcode "'.$importedBc.'" already belongs to "'.$existingName.'" (barcode: '.$existingBc.'). Imported product name was "'.$parsed['product_name'].'".';
        }
        return 'Skipped: product name "'.$existingName.'" already exists with barcode '.$existingBc.'. Imported barcode: '.$importedBc.'. Reason: same product name but different barcode — row not imported to prevent duplicates.';
    }

    private function skipEntry($rowNum, $parsed, $reason, $extra = array()){
        $entry = array_merge(array(
            'row' => $rowNum,
            'action_type' => 'skip',
            'product_name' => isset($parsed['product_name']) ? $parsed['product_name'] : '',
            'imported_barcode' => (isset($parsed['barcode']) && $parsed['barcode'] !== '') ? $parsed['barcode'] : '—',
            'existing_product' => isset($extra['existing_product']) ? $extra['existing_product'] : '',
            'existing_barcode' => isset($extra['existing_barcode']) ? $extra['existing_barcode'] : '',
            'reason' => $reason,
        ), $extra);
        return $entry;
    }

    public function handleUpload($file){
        $resp = array('status' => 'failed', 'msg' => 'Upload failed.');
        if(!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])){
            $resp['msg'] = 'No file uploaded.';
            return $resp;
        }
        $orig = isset($file['name']) ? $file['name'] : '';
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if(!in_array($ext, array('csv', 'xlsx'), true)){
            $resp['msg'] = 'Only .csv and .xlsx files are allowed.';
            return $resp;
        }
        $blocked = array('php','phtml','php3','php4','php5','phar','js','exe','bat','sh','cmd','com','dll');
        if(in_array($ext, $blocked, true)){
            $resp['msg'] = 'File type is not allowed.';
            return $resp;
        }
        if(function_exists('finfo_open')){
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $allowed = array(
                'text/plain','text/csv','application/csv','application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip','application/octet-stream'
            );
            if($mime && !in_array($mime, $allowed, true)){
                $resp['msg'] = 'Invalid file content type.';
                return $resp;
            }
        }
        $token = bin2hex(random_bytes(16));
        $safeName = $token . '.' . $ext;
        $dest = $this->tempDir() . '/' . $safeName;
        if(!move_uploaded_file($file['tmp_name'], $dest)){
            $resp['msg'] = 'Could not save uploaded file.';
            return $resp;
        }
        try{
            $rows = $this->parseFile($dest, $ext);
            @unlink($dest);
            if($rows === false){
                $resp['msg'] = 'Could not read spreadsheet. Ensure the file is a valid CSV or Excel workbook.';
                return $resp;
            }
            $result = $this->buildPreview($rows);
            $result['status'] = 'success';
            $result['token'] = $token;
            $_SESSION[self::SESSION_KEY] = array(
                'token' => $token,
                'rows' => $result['commit_rows'],
                'skipped' => $result['skipped'],
                'summary' => $result['stats'],
                'created_at' => time(),
            );
            unset($result['commit_rows']);
            return $result;
        }catch(Exception $e){
            @unlink($dest);
            $resp['msg'] = $e->getMessage();
            return $resp;
        }
    }

    public function commit($token){
        $resp = array(
            'success' => false,
            'status' => 'failed',
            'message' => 'Import session expired or invalid.',
            'msg' => 'Import session expired or invalid.',
        );
        if(empty($token) || empty($_SESSION[self::SESSION_KEY])){
            return $resp;
        }
        $batch = $_SESSION[self::SESSION_KEY];
        if($batch['token'] !== $token){
            return $resp;
        }
        if((time() - (int)$batch['created_at']) > self::SESSION_TTL){
            unset($_SESSION[self::SESSION_KEY]);
            return $resp;
        }
        $summary = array(
            'total_read' => (int)(isset($batch['summary']['rows_read']) ? $batch['summary']['rows_read'] : $batch['summary']['total_read']),
            'products_created' => 0,
            'products_updated' => 0,
            'inventory_created' => 0,
            'inventory_updated' => 0,
            'rows_skipped' => count($batch['skipped']),
            'errors' => array(),
        );
        foreach($batch['skipped'] as $skip){
            $summary['errors'][] = 'Row '.$skip['row'].': '.$skip['reason'];
        }
        $this->conn->begin_transaction();
        try{
            foreach($batch['rows'] as $row){
                $commit = $this->commitRow($row);
                if($commit['product_created']) $summary['products_created']++;
                if($commit['product_updated']) $summary['products_updated']++;
                if($commit['inventory_created']) $summary['inventory_created']++;
                if($commit['inventory_updated']) $summary['inventory_updated']++;
            }
            $this->conn->commit();
            if(function_exists('notifications_sync_system')){
                notifications_sync_system();
            }
            $detail = $summary['products_created'].' created, '.$summary['products_updated'].' products updated, '
                .$summary['inventory_created'].' inventory created, '.$summary['inventory_updated'].' inventory updated, '
                .$summary['rows_skipped'].' skipped';
            admin_activity_log('inventory_import_completed', $detail);
            unset($_SESSION[self::SESSION_KEY]);
            return array(
                'success' => true,
                'status' => 'success',
                'message' => 'Inventory import completed successfully.',
                'msg' => 'Inventory import completed successfully.',
                'redirect' => 'admin/?page=inventory',
                'summary' => $summary,
            );
        }catch(Exception $e){
            $this->conn->rollback();
            $resp['success'] = false;
            $resp['status'] = 'failed';
            $resp['message'] = $e->getMessage();
            $resp['msg'] = $e->getMessage();
            return $resp;
        }
    }

    private function parseFile($path, $ext){
        if($ext === 'csv'){
            return $this->parseCsv($path);
        }
        return AshSimpleXlsx::readRows($path);
    }

    private function parseCsv($path){
        $rows = array();
        $fh = fopen($path, 'r');
        if(!$fh) return false;
        while(($data = fgetcsv($fh)) !== false){
            $rows[] = $data;
        }
        fclose($fh);
        return $rows;
    }

    private function normalizeHeader($h){
        $h = strtolower(trim(preg_replace('/\s+/', ' ', $h)));
        $map = array(
            'product name' => 'product_name',
            'barcode' => 'barcode',
            'brand' => 'brand',
            'category' => 'category',
            'variant' => 'variant',
            'retail price' => 'retail_price',
            'unit cost' => 'unit_cost',
            'quantity' => 'quantity',
            'low stock alert' => 'low_stock_alert',
            'status' => 'status',
        );
        return isset($map[$h]) ? $map[$h] : null;
    }

    private function buildPreview($rows){
        if(empty($rows)){
            throw new Exception('File is empty.');
        }
        $headerRow = array_shift($rows);
        $colMap = array();
        foreach($headerRow as $idx => $label){
            $key = $this->normalizeHeader($label);
            if($key){
                $colMap[$key] = $idx;
            }
        }
        if(!isset($colMap['product_name'])){
            throw new Exception('Missing required column: Product Name');
        }
        $preview = array();
        $commit_rows = array();
        $skipped = array();
        $seenBarcodes = array();
        $rowNum = 1;
        $total_read = 0;
        $newBrands = array();
        $newCategories = array();
        foreach($rows as $raw){
            $rowNum++;
            if($this->isEmptyRow($raw)){
                continue;
            }
            $parsed = $this->extractRow($raw, $colMap);
            if($this->isInstructionRow($parsed)){
                continue;
            }
            $total_read++;
            $errors = $this->validateRow($parsed, $seenBarcodes);
            if(!empty($errors)){
                $skipped[] = $this->skipEntry($rowNum, $parsed, implode('; ', $errors));
                continue;
            }
            if($parsed['barcode'] !== ''){
                $seenBarcodes[strtolower($parsed['barcode'])] = $rowNum;
            }
            $plan = $this->planRow($parsed, $rowNum);
            if(!empty($plan['errors'])){
                $skip = isset($plan['skip']) ? $plan['skip'] : $this->skipEntry($rowNum, $parsed, implode('; ', $plan['errors']));
                $skipped[] = $skip;
                continue;
            }
            $preview[] = $plan['preview'];
            $commit_rows[] = $plan['commit'];
            if($plan['commit']['brand_will_create']){
                $newBrands[strtolower($parsed['brand'])] = $parsed['brand'];
            }
            if($plan['commit']['category_will_create']){
                $newCategories[strtolower($parsed['category'])] = $parsed['category'];
            }
        }
        $newProducts = 0;
        $updatedProducts = 0;
        foreach($commit_rows as $c){
            if($c['product_action'] === 'create'){
                $newProducts++;
            }else{
                $updatedProducts++;
            }
        }
        $stats = array(
            'rows_read' => $total_read,
            'new_products' => $newProducts,
            'updated_products' => $updatedProducts,
            'skipped_rows' => count($skipped),
            'new_brands' => count($newBrands),
            'new_categories' => count($newCategories),
            'ready_to_import' => count($commit_rows),
        );
        return array(
            'total_read' => $total_read,
            'stats' => $stats,
            'preview' => $preview,
            'skipped' => $skipped,
            'commit_rows' => $commit_rows,
        );
    }

    private function isInstructionRow($parsed){
        $name = isset($parsed['product_name']) ? strtoupper(trim($parsed['product_name'])) : '';
        return (strpos($name, 'IMPORTANT:') === 0 || strpos($name, 'BARCODE COLUMN') !== false);
    }

    private function isEmptyRow($raw){
        if(!is_array($raw)) return true;
        foreach($raw as $cell){
            if(trim((string)$cell) !== '') return false;
        }
        return true;
    }

    private function extractRow($raw, $colMap){
        $get = function($key) use ($raw, $colMap){
            if(!isset($colMap[$key])) return '';
            $idx = $colMap[$key];
            return isset($raw[$idx]) ? trim((string)$raw[$idx]) : '';
        };
        $barcodeWarning = null;
        $barcode = self::normalizeBarcode($get('barcode'), $barcodeWarning);
        return array(
            'product_name' => $get('product_name'),
            'barcode' => $barcode,
            'barcode_warning' => $barcodeWarning,
            'brand' => $get('brand'),
            'category' => $get('category'),
            'variant' => $get('variant') !== '' ? $get('variant') : 'Default',
            'retail_price' => $get('retail_price'),
            'unit_cost' => $get('unit_cost'),
            'quantity' => $get('quantity'),
            'low_stock_alert' => $get('low_stock_alert'),
            'status' => $get('status'),
        );
    }

    private function validateRow($parsed, $seenBarcodes){
        $errors = array();
        if($parsed['product_name'] === ''){
            $errors[] = 'Product Name is required';
        }
        if($parsed['quantity'] === '' || !is_numeric($parsed['quantity'])){
            $errors[] = 'Quantity must be numeric';
        }elseif((float)$parsed['quantity'] < 0){
            $errors[] = 'Quantity cannot be negative';
        }
        if($parsed['retail_price'] === '' || !is_numeric($parsed['retail_price'])){
            $errors[] = 'Retail Price must be numeric';
        }elseif((float)$parsed['retail_price'] < 0){
            $errors[] = 'Retail Price cannot be negative';
        }
        if($parsed['unit_cost'] !== '' && !is_numeric($parsed['unit_cost'])){
            $errors[] = 'Unit Cost must be numeric';
        }elseif($parsed['unit_cost'] !== '' && (float)$parsed['unit_cost'] < 0){
            $errors[] = 'Unit Cost cannot be negative';
        }
        if($parsed['low_stock_alert'] !== '' && !is_numeric($parsed['low_stock_alert'])){
            $errors[] = 'Low Stock Alert must be numeric';
        }
        if($parsed['barcode'] !== ''){
            $bc = strtolower($parsed['barcode']);
            if(isset($seenBarcodes[$bc])){
                $errors[] = 'Duplicate barcode in file (row '.$seenBarcodes[$bc].'): '.$parsed['barcode'];
            }
        }
        if($parsed['brand'] === ''){
            $errors[] = 'Brand is required';
        }
        if($parsed['category'] === ''){
            $errors[] = 'Category is required';
        }
        return $errors;
    }

    private function parseStatus($status){
        $s = strtolower(trim($status));
        if($s === '' || $s === 'active' || $s === '1' || $s === 'yes'){
            return 1;
        }
        if($s === 'inactive' || $s === '0' || $s === 'no'){
            return 0;
        }
        return 1;
    }

    private function esc($v){
        return $this->conn->real_escape_string($v);
    }

    private function tenantBid(){
        return function_exists('tenant_id') ? (int)tenant_id() : 0;
    }

    private function tenantScope($alias = ''){
        return function_exists('tenant_sql') ? tenant_sql($alias) : '';
    }

    private function findBrand($name){
        $name = trim($name);
        if($name === '') return null;
        $q = $this->conn->query("SELECT id, name FROM brands WHERE delete_flag = 0 AND LOWER(name) = LOWER('".$this->esc($name)."')".$this->tenantScope()." LIMIT 1");
        return ($q && $q->num_rows) ? $q->fetch_assoc() : null;
    }

    private function findCategory($name){
        $name = trim($name);
        if($name === '') return null;
        $q = $this->conn->query("SELECT id, category FROM categories WHERE delete_flag = 0 AND LOWER(category) = LOWER('".$this->esc($name)."')".$this->tenantScope()." LIMIT 1");
        return ($q && $q->num_rows) ? $q->fetch_assoc() : null;
    }

    private function findProductByBarcode($barcode){
        if($barcode === '') return null;
        $q = $this->conn->query("SELECT * FROM products WHERE delete_flag = 0 AND barcode = '".$this->esc($barcode)."'".$this->tenantScope()." LIMIT 1");
        return ($q && $q->num_rows) ? $q->fetch_assoc() : null;
    }

	private function findProductByName($name){
		$q = $this->conn->query("SELECT * FROM products WHERE delete_flag = 0 AND LOWER(name) = LOWER('".$this->esc($name)."')".$this->tenantScope()." LIMIT 1");
		return ($q && $q->num_rows) ? $q->fetch_assoc() : null;
	}

    private function findInventory($product_id, $variant){
        $q = $this->conn->query("SELECT * FROM inventory WHERE product_id = '".(int)$product_id."' AND variant = '".$this->esc($variant)."'".$this->tenantScope()." LIMIT 1");
        return ($q && $q->num_rows) ? $q->fetch_assoc() : null;
    }

    private function soldQty($inventory_id){
        $q = $this->conn->query("SELECT COALESCE(SUM(ol.quantity),0) AS sold
            FROM order_list ol
            INNER JOIN orders o ON o.id = ol.order_id
            WHERE o.status != 4 AND ol.inventory_id = '".(int)$inventory_id."'");
        if($q && ($row = $q->fetch_assoc())){
            return (float)$row['sold'];
        }
        return 0;
    }

    private function planRow($parsed, $rowNum){
        $errors = array();
        $status = $this->parseStatus($parsed['status']);
        $availQty = (float)$parsed['quantity'];
        $retail = (float)$parsed['retail_price'];
        $cost = $parsed['unit_cost'] === '' ? 0 : (float)$parsed['unit_cost'];

        $brand = $this->findBrand($parsed['brand']);
        $category = $this->findCategory($parsed['category']);
        $brandWillCreate = !$brand;
        $categoryWillCreate = !$category;

        $product = null;
        $productAction = 'create';
        $productChanges = array();

        if($parsed['barcode'] !== ''){
            $product = $this->findProductByBarcode($parsed['barcode']);
            if($product && strcasecmp($product['name'], $parsed['product_name']) !== 0){
                $reason = $this->barcodeConflictReason($parsed, $product, 'barcode_owner');
                return array(
                    'errors' => array($reason),
                    'skip' => $this->skipEntry($rowNum, $parsed, $reason, array(
                        'existing_product' => stripslashes($product['name']),
                        'existing_barcode' => !empty($product['barcode']) ? stripslashes($product['barcode']) : '(none)',
                    )),
                );
            }
        }
		if(empty($errors) && !$product){
			$product = $this->findProductByName($parsed['product_name']);
		}
		if(empty($errors) && $product && $parsed['barcode'] !== '' && !empty($product['barcode']) && $product['barcode'] !== $parsed['barcode']){
			$reason = $this->barcodeConflictReason($parsed, $product, 'name');
			return array(
				'errors' => array($reason),
				'skip' => $this->skipEntry($rowNum, $parsed, $reason, array(
					'existing_product' => stripslashes($product['name']),
					'existing_barcode' => stripslashes($product['barcode']),
				)),
			);
		}
		if(empty($errors) && $product){
            $productAction = 'update';
            if($parsed['barcode'] !== '' && empty($product['barcode'])){
                $productChanges[] = array('field' => 'Barcode', 'from' => '—', 'to' => $parsed['barcode']);
            }elseif($parsed['barcode'] !== '' && $product['barcode'] !== $parsed['barcode']){
                $errors[] = 'Barcode conflict with existing product';
            }
            $brandId = $brand ? (int)$brand['id'] : null;
            $catId = $category ? (int)$category['id'] : null;
            if($brandId && (int)$product['brand_id'] !== $brandId){
                $productChanges[] = array('field' => 'Brand', 'from' => $this->brandName((int)$product['brand_id']), 'to' => $parsed['brand']);
            }
            if($catId && (int)$product['category_id'] !== $catId){
                $productChanges[] = array('field' => 'Category', 'from' => $this->categoryName((int)$product['category_id']), 'to' => $parsed['category']);
            }
            if((int)$product['status'] !== $status){
                $productChanges[] = array('field' => 'Status', 'from' => ((int)$product['status'] ? 'Active' : 'Inactive'), 'to' => ($status ? 'Active' : 'Inactive'));
            }
        }

        $inventory = null;
        $inventoryAction = 'create';
        $inventoryChanges = array();
        if(empty($errors) && $product){
            $inventory = $this->findInventory($product['id'], $parsed['variant']);
            if(!$inventory && $parsed['variant'] !== 'Default'){
                $inventory = $this->findInventory($product['id'], 'Default');
                if($inventory){
                    $inventoryChanges[] = array('field' => 'Variant', 'from' => $inventory['variant'], 'to' => $parsed['variant']);
                }
            }
        }
        if(empty($errors) && $inventory){
            $inventoryAction = 'update';
            $sold = $this->soldQty($inventory['id']);
            $oldAvail = (float)$inventory['quantity'] - $sold;
            if(abs($oldAvail - $availQty) > 0.0001){
                $inventoryChanges[] = array('field' => 'Available Qty', 'from' => format_num($oldAvail), 'to' => format_num($availQty));
            }
            if((float)$inventory['price'] != $retail){
                $inventoryChanges[] = array('field' => 'Retail Price', 'from' => format_price($inventory['price']), 'to' => format_price($retail));
            }
            if($this->can_set_cost && (float)$inventory['cost_price'] != $cost){
                $inventoryChanges[] = array('field' => 'Unit Cost', 'from' => format_price($inventory['cost_price']), 'to' => format_price($cost));
            }
        }

        $actionLabel = $productAction === 'create' ? 'Create product & inventory' : ($inventoryAction === 'create' ? 'Add inventory variant' : 'Update existing');
        $actionType = $productAction === 'create' ? 'create' : 'update';
        if(!empty($parsed['barcode_warning'])){
            $actionType = 'warning';
        }

        $preview = array(
            'row' => $rowNum,
            'product_name' => $parsed['product_name'],
            'barcode' => $parsed['barcode'] !== '' ? $parsed['barcode'] : '',
            'barcode_display' => $parsed['barcode'] !== '' ? $parsed['barcode'] : '—',
            'barcode_warning' => !empty($parsed['barcode_warning']) ? $parsed['barcode_warning'] : '',
            'brand' => $parsed['brand'].($brandWillCreate ? ' (new)' : ''),
            'category' => $parsed['category'].($categoryWillCreate ? ' (new)' : ''),
            'variant' => $parsed['variant'],
            'retail_price' => format_price($retail),
            'unit_cost' => $this->can_set_cost ? format_price($cost) : '—',
            'quantity' => format_num($availQty),
            'low_stock_alert' => $parsed['low_stock_alert'] !== '' ? $parsed['low_stock_alert'].' (info only)' : '—',
            'status' => $status ? 'Active' : 'Inactive',
            'action' => $actionLabel,
            'action_type' => $actionType,
            'product_action' => $productAction,
            'inventory_action' => $inventoryAction,
            'changes' => array_merge($productChanges, $inventoryChanges),
        );

        $commit = array(
            'row' => $rowNum,
            'parsed' => $parsed,
            'status' => $status,
            'retail' => $retail,
            'cost' => $cost,
            'avail_qty' => $availQty,
            'product_id' => $product ? (int)$product['id'] : 0,
            'inventory_id' => $inventory ? (int)$inventory['id'] : 0,
            'product_action' => $productAction,
            'inventory_action' => $inventoryAction,
            'brand_will_create' => $brandWillCreate,
            'category_will_create' => $categoryWillCreate,
            'brand_id' => $brand ? (int)$brand['id'] : 0,
            'category_id' => $category ? (int)$category['id'] : 0,
        );

        return array('preview' => $preview, 'commit' => $commit, 'errors' => $errors);
    }

    private function brandName($id){
        $q = $this->conn->query("SELECT name FROM brands WHERE id = '".(int)$id."' LIMIT 1");
        return ($q && $q->num_rows) ? stripslashes($q->fetch_assoc()['name']) : '—';
    }

    private function categoryName($id){
        $q = $this->conn->query("SELECT category FROM categories WHERE id = '".(int)$id."' LIMIT 1");
        return ($q && $q->num_rows) ? stripslashes($q->fetch_assoc()['category']) : '—';
    }

    private function ensureBrand($name){
        $existing = $this->findBrand($name);
        if($existing) return (int)$existing['id'];
        $name = trim($name);
        $sql = "INSERT INTO brands SET business_id = '".$this->tenantBid()."', name = '".$this->esc($name)."', description = '', status = 1";
        if(!$this->conn->query($sql)){
            throw new Exception('Could not create brand: '.$name);
        }
        return (int)$this->conn->insert_id;
    }

    private function ensureCategory($name){
        $existing = $this->findCategory($name);
        if($existing) return (int)$existing['id'];
        $name = trim($name);
        $sql = "INSERT INTO categories SET business_id = '".$this->tenantBid()."', category = '".$this->esc($name)."', description = '', status = 1";
        if(!$this->conn->query($sql)){
            throw new Exception('Could not create category: '.$name);
        }
        return (int)$this->conn->insert_id;
    }

    private function commitRow($row){
        $parsed = $row['parsed'];
        $result = array(
            'product_created' => false,
            'product_updated' => false,
            'inventory_created' => false,
            'inventory_updated' => false,
        );
        $brand_id = $row['brand_id'] > 0 ? $row['brand_id'] : $this->ensureBrand($parsed['brand']);
        $category_id = $row['category_id'] > 0 ? $row['category_id'] : $this->ensureCategory($parsed['category']);
        $product_id = $row['product_id'];
        $barcodeSql = $parsed['barcode'] !== '' ? "'".$this->esc($parsed['barcode'])."'" : 'NULL';

        if($row['product_action'] === 'create'){
            $specs = htmlentities('<p>Imported via inventory Excel import.</p>');
            $sql = "INSERT INTO products SET
                business_id = '".$this->tenantBid()."',
                brand_id = '".(int)$brand_id."',
                category_id = '".(int)$category_id."',
                name = '".$this->esc($parsed['product_name'])."',
                barcode = {$barcodeSql},
                specs = '".$this->esc($specs)."',
                status = '".(int)$row['status']."'";
            if(!$this->conn->query($sql)){
                throw new Exception('Failed to create product: '.$parsed['product_name']);
            }
            $product_id = (int)$this->conn->insert_id;
            $result['product_created'] = true;
        }else{
            $sets = array();
            if((int)$brand_id > 0) $sets[] = "brand_id = '".(int)$brand_id."'";
            if((int)$category_id > 0) $sets[] = "category_id = '".(int)$category_id."'";
            $sets[] = "status = '".(int)$row['status']."'";
            if($parsed['barcode'] !== '' && $row['product_id'] > 0){
                $sets[] = "barcode = ".$barcodeSql;
            }
            if(!empty($sets)){
                $sql = "UPDATE products SET ".implode(', ', $sets)." WHERE id = '".(int)$product_id."'".$this->tenantScope();
                if(!$this->conn->query($sql)){
                    throw new Exception('Failed to update product: '.$parsed['product_name']);
                }
                $result['product_updated'] = true;
            }
        }

        $sold = 0;
        if($row['inventory_id'] > 0){
            $sold = $this->soldQty($row['inventory_id']);
        }
        $storedQty = $row['avail_qty'] + $sold;

        if($row['inventory_action'] === 'create'){
            $costSql = $this->can_set_cost ? "'".(float)$row['cost']."'" : "'0'";
            $sql = "INSERT INTO inventory SET
                business_id = '".$this->tenantBid()."',
                product_id = '".(int)$product_id."',
                variant = '".$this->esc($parsed['variant'])."',
                quantity = '".(float)$storedQty."',
                price = '".(float)$row['retail']."',
                cost_price = {$costSql}";
            if(!$this->conn->query($sql)){
                throw new Exception('Failed to create inventory for: '.$parsed['product_name']);
            }
            $result['inventory_created'] = true;
        }else{
            $sets = array(
                "variant = '".$this->esc($parsed['variant'])."'",
                "quantity = '".(float)$storedQty."'",
                "price = '".(float)$row['retail']."'",
            );
            if($this->can_set_cost){
                $sets[] = "cost_price = '".(float)$row['cost']."'";
            }
            $invId = $row['inventory_id'] > 0 ? $row['inventory_id'] : 0;
            if($invId <= 0){
                $inv = $this->findInventory($product_id, $parsed['variant']);
                $invId = $inv ? (int)$inv['id'] : 0;
            }
            if($invId <= 0){
                throw new Exception('Inventory row not found for: '.$parsed['product_name']);
            }
            $sql = "UPDATE inventory SET ".implode(', ', $sets)." WHERE id = '".(int)$invId."'".$this->tenantScope();
            if(!$this->conn->query($sql)){
                throw new Exception('Failed to update inventory for: '.$parsed['product_name']);
            }
            $result['inventory_updated'] = true;
        }
        return $result;
    }
}
