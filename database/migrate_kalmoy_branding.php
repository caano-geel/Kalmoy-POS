<?php
/**
 * Update system_info branding to Kalmoy POS.
 * Usage: php database/migrate_kalmoy_branding.php
 */
require_once __DIR__ . '/../config.php';

if (!isset($conn) || !$conn) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$updates = array(
    'name' => 'Kalmoy POS',
    'short_name' => 'Kalmoy',
);

foreach ($updates as $field => $value) {
    $esc = $conn->real_escape_string($value);
    $check = $conn->query("SELECT meta_field FROM system_info WHERE meta_field = '{$field}' LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $conn->query("UPDATE system_info SET meta_value = '{$esc}' WHERE meta_field = '{$field}'");
    } else {
        $conn->query("INSERT INTO system_info SET meta_field = '{$field}', meta_value = '{$esc}'");
    }
}

if (isset($_SESSION['system_info'])) {
    unset($_SESSION['system_info']);
}
if (isset($GLOBALS['_settings']) && is_object($GLOBALS['_settings'])) {
    $GLOBALS['_settings']->load_system_info();
}

echo "Branding updated in system_info:\n";
foreach ($updates as $field => $value) {
    echo "  {$field} = {$value}\n";
}
