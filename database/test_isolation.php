<?php
/**
 * Isolation tests for multi-tenant SaaS MVP.
 * Usage: php database/test_isolation.php
 */
require_once __DIR__ . '/../config.php';

$pass = 0;
$fail = 0;

function test_assert($label, $cond)
{
    global $pass, $fail;
    if ($cond) {
        echo "[PASS] {$label}\n";
        $pass++;
    } else {
        echo "[FAIL] {$label}\n";
        $fail++;
    }
}

$b1 = $conn->query("SELECT id FROM businesses WHERE slug = 'kalmoy-supermarket' LIMIT 1")->fetch_assoc();
$b2 = $conn->query("SELECT id FROM businesses WHERE slug = 'eastleigh-pharmacy' LIMIT 1")->fetch_assoc();
if (!$b1 || !$b2) {
    echo "Run install_saas.php first.\n";
    exit(1);
}
$bid1 = (int)$b1['id'];
$bid2 = (int)$b2['id'];

$p1 = $conn->query("SELECT id, barcode FROM products WHERE business_id = {$bid1} LIMIT 1")->fetch_assoc();
$p2 = $conn->query("SELECT id, barcode FROM products WHERE business_id = {$bid2} LIMIT 1")->fetch_assoc();
test_assert('Business 1 has products', !empty($p1));
test_assert('Business 2 has products', !empty($p2));

$_SESSION['userdata'] = array('login_type' => 1, 'business_id' => $bid1, 'id' => 1, 'type' => 1);
$cnt1 = $conn->query("SELECT COUNT(*) AS c FROM products WHERE delete_flag = 0" . tenant_sql())->fetch_assoc()['c'];
test_assert('B1 product list scoped', (int)$cnt1 > 0);
$cross = $conn->query("SELECT id FROM products WHERE id = " . (int)$p2['id'] . tenant_sql())->num_rows;
test_assert('B1 cannot see B2 product by ID', $cross === 0);

$bc2 = $conn->real_escape_string($p2['barcode']);
$barCross = $conn->query("SELECT id FROM products WHERE barcode = '{$bc2}'" . tenant_sql())->num_rows;
test_assert('B1 cannot find B2 barcode', $barCross === 0);

$o2 = $conn->query("SELECT o.id FROM orders o INNER JOIN sales s ON s.order_id = o.id WHERE o.business_id = {$bid2} LIMIT 1")->fetch_assoc();
if ($o2) {
    $urlCross = $conn->query("SELECT o.id FROM orders o WHERE o.id = " . (int)$o2['id'] . tenant_sql('o'))->num_rows;
    test_assert('B1 cannot view B2 order by URL id', $urlCross === 0);
}

$_SESSION['userdata']['business_id'] = $bid2;
$bc1 = $conn->real_escape_string($p1['barcode']);
$barCross2 = $conn->query("SELECT id FROM products WHERE barcode = '{$bc1}'" . tenant_sql())->num_rows;
test_assert('B2 cannot find B1 barcode', $barCross2 === 0);

$plat = $conn->query("SELECT COUNT(*) AS c FROM businesses")->fetch_assoc()['c'];
test_assert('Platform can see all businesses', (int)$plat >= 2);

$sub = tenant_subscription_status($bid1);
test_assert('B1 subscription active', !empty($sub['allowed']));

echo "\nResults: {$pass} passed, {$fail} failed\n";
exit($fail > 0 ? 1 : 0);
