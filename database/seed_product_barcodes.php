<?php
/**
 * Assign unique EAN-13 barcodes to products missing barcodes.
 * Usage: php database/seed_product_barcodes.php
 */
$outFile = __DIR__ . '/seed_product_barcodes.sql';

function ean13_check_digit($twelveDigits)
{
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += (int) $twelveDigits[$i] * ($i % 2 === 0 ? 1 : 3);
    }
    return (string) ((10 - ($sum % 10)) % 10);
}

function ash_ean13_for_product($productId)
{
    $base = '628' . str_pad((string) $productId, 9, '0', STR_PAD_LEFT);
    return $base . ean13_check_digit($base);
}

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'ash_pos_db';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    fwrite(STDERR, "DB connect failed: {$mysqli->connect_error}\n");
    exit(1);
}

$res = $mysqli->query("SELECT id, name, barcode FROM products WHERE barcode IS NULL OR barcode = '' ORDER BY id");
if (!$res) {
    fwrite(STDERR, $mysqli->error . "\n");
    exit(1);
}

$fh = fopen($outFile, 'w');
fwrite($fh, "-- Product barcodes (EAN-13, Kenya prefix 628)\n");
fwrite($fh, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");

$used = [];
$existing = $mysqli->query("SELECT barcode FROM products WHERE barcode IS NOT NULL AND barcode != ''");
while ($row = $existing->fetch_assoc()) {
    $used[$row['barcode']] = true;
}

$updates = [];
while ($row = $res->fetch_assoc()) {
    $id = (int) $row['id'];
    $barcode = ash_ean13_for_product($id);
    while (isset($used[$barcode])) {
        $barcode = ash_ean13_for_product($id + 10000);
    }
    $used[$barcode] = true;
    $name = str_replace("'", "''", $row['name']);
    $updates[] = "UPDATE `products` SET `barcode` = '{$barcode}' WHERE `id` = {$id}; -- {$name}";
}

fwrite($fh, implode("\n", $updates) . "\n");
fclose($fh);

foreach ($updates as $sql) {
    $stmt = strtok($sql, ';');
    if (!$mysqli->query($stmt)) {
        fwrite(STDERR, "Error: {$mysqli->error}\n{$stmt}\n");
        exit(1);
    }
}

$count = count($updates);
echo "Updated {$count} products with barcodes.\n";
echo "SQL saved to: {$outFile}\n";

$verify = $mysqli->query("SELECT COUNT(*) AS c FROM products WHERE barcode IS NULL OR barcode = ''");
$row = $verify->fetch_assoc();
echo "Products still without barcode: {$row['c']}\n";

$mysqli->close();
