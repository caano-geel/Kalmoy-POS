<?php
require_once __DIR__ . '/../config.php';
if(file_exists(__DIR__ . '/../vendor/autoload.php')){
    require_once __DIR__ . '/../vendor/autoload.php';
}
require_once __DIR__ . '/ModuleExportService.php';

if(!isset($_SESSION['userdata']) || (int)$_SESSION['userdata']['login_type'] !== 1){
    while(ob_get_level() > 0) ob_end_clean();
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied.');
}

if(!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')){
    while(ob_get_level() > 0) ob_end_clean();
    header('HTTP/1.1 500 Internal Server Error');
    exit('PhpSpreadsheet is not installed. Run: composer require phpoffice/phpspreadsheet');
}

$module = isset($_GET['module']) ? $_GET['module'] : '';
$format = isset($_GET['format']) ? $_GET['format'] : 'xlsx';
$params = $_GET;
unset($params['module'], $params['format']);

$service = new ModuleExportService($conn, isset($_settings) ? $_settings : null);
$service->handle($module, $format, $params);
