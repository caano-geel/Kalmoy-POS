<?php
require_once __DIR__ . '/../initialize.php';
$port = defined('DB_PORT') ? (int) DB_PORT : 3306;
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, $port);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$sql = file_get_contents(__DIR__ . '/update_new_modules.sql');
$sql = preg_replace('/--[^\n]*\n/', "\n", $sql);
$parts = preg_split('/;\s*[\r\n]+/', $sql);
foreach ($parts as $q) {
    $q = trim($q);
    if ($q === '' || strpos($q, '--') === 0) continue;
    if (!$conn->query($q)) {
        echo "Error: " . $conn->error . "\nQuery: " . substr($q, 0, 120) . "...\n";
    }
}
if (!db_table_has_column_helper($conn, 'inventory', 'expiry_date')) {
    @$conn->query("ALTER TABLE `inventory` ADD COLUMN `expiry_date` date DEFAULT NULL AFTER `cost_price`");
}
echo "Migration complete.\n";
function db_table_has_column_helper($conn, $table, $column) {
    $table = preg_replace('/[^a-z0-9_]/i', '', $table);
    $column = preg_replace('/[^a-z0-9_]/i', '', $column);
    $q = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return $q && $q->num_rows > 0;
}
