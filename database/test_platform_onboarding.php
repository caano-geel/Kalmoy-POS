<?php
/**
 * Platform onboarding and validation tests.
 * Usage: php database/test_platform_onboarding.php
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../platform/inc/bootstrap.php';
define('PLATFORM_SKIP_DISPATCH', true);
require_once __DIR__ . '/../classes/Platform.php';

$pass = 0;
$fail = 0;

function ok($label, $cond, $detail = '')
{
    global $pass, $fail;
    echo ($cond ? '[PASS]' : '[FAIL]') . " {$label}";
    if (!$cond && $detail !== '') {
        echo " — {$detail}";
    }
    echo "\n";
    $cond ? $pass++ : $fail++;
}

$_SESSION['platform_user'] = array('id' => 1, 'name' => 'Test', 'username' => 'platform', 'email' => 't@test.com');
$conn->rollback();
$conn->autocommit(true);

// Fix existing demo inconsistencies first
include __DIR__ . '/fix_saas_data_consistency.php';

$api = new Platform();

$east = $conn->query("SELECT b.id, s.status AS sub_status, s.trial_ends_at, s.current_period_end, sp.name AS plan_name, sp.price_monthly
    FROM businesses b
    LEFT JOIN subscriptions s ON s.id = (SELECT MAX(id) FROM subscriptions WHERE business_id = b.id)
    LEFT JOIN subscription_plans sp ON sp.id = s.plan_id
    WHERE b.slug = 'eastleigh-pharmacy' LIMIT 1")->fetch_assoc();
$eastPay = $conn->query("SELECT COUNT(*) AS c FROM subscription_payments WHERE business_id = " . (int)$east['id'])->fetch_assoc()['c'];
ok('Eastleigh trial has no seed payment', (int)$eastPay === 0, "payments={$eastPay}");
ok('Eastleigh subscription is trial', ($east['sub_status'] ?? '') === 'trial');

$kalmoyPay = $conn->query("SELECT p.amount, sp.price_monthly FROM subscription_payments p
    INNER JOIN subscription_plans sp ON sp.id = p.plan_id
    INNER JOIN businesses b ON b.id = p.business_id
    WHERE b.slug = 'kalmoy-supermarket' LIMIT 1")->fetch_assoc();
ok('Kalmoy payment matches Business plan price', $kalmoyPay && (float)$kalmoyPay['amount'] === (float)$kalmoyPay['price_monthly']);

// Successful third business (before rollback test)
$testSlug = 'nairobi-electronics-' . time();
$testUser = 'nairobi.owner.' . time();
$_POST = array(
    'csrf' => tenant_csrf_token(),
    'name' => 'Nairobi Electronics Test',
    'slug' => $testSlug,
    'phone' => '0711000000',
    'email' => 'test@nairobi-electronics.local',
    'address' => 'Luthuli Avenue, Nairobi',
    'owner_firstname' => 'Abdirahman',
    'owner_lastname' => 'Yusuf',
    'owner_username' => $testUser,
    'owner_email' => 'owner@nairobi-electronics.local',
    'owner_password' => 'Owner@2026',
    'sub_mode' => 'trial',
    'plan_id' => 1,
);
$created = json_decode($api->create_business(), true);
$testBid = (int)($created['business_id'] ?? 0);
ok('Third business created', ($created['status'] ?? '') === 'success' && $testBid > 0, $created['msg'] ?? '');

if ($testBid > 0) {
    $biz = $conn->query("SELECT * FROM businesses WHERE id = {$testBid} LIMIT 1")->fetch_assoc();
    $sub = $conn->query("SELECT * FROM subscriptions WHERE business_id = {$testBid} ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $settings = $conn->query("SELECT COUNT(*) AS c FROM business_settings WHERE business_id = {$testBid}")->fetch_assoc()['c'];
    $owner = $conn->query("SELECT * FROM users WHERE business_id = {$testBid} AND type = 1 LIMIT 1")->fetch_assoc();
    $audit = $conn->query("SELECT id FROM platform_audit_log WHERE business_id = {$testBid} AND action = 'business_created' LIMIT 1")->fetch_assoc();
    $products = $conn->query("SELECT COUNT(*) AS c FROM products WHERE business_id = {$testBid}")->fetch_assoc()['c'];
    $otherProducts = $conn->query("SELECT COUNT(*) AS c FROM products WHERE business_id != {$testBid}")->fetch_assoc()['c'];

    ok('Business record exists', !empty($biz));
    ok('Subscription record exists', !empty($sub) && $sub['status'] === 'trial');
    ok('Default settings seeded', (int)$settings > 0, "settings={$settings}");
    ok('Owner user created', !empty($owner) && $owner['username'] === $testUser);
    ok('Owner password hashed', !empty($owner['password']) && password_verify('Owner@2026', $owner['password']));
    ok('Audit log entry created', !empty($audit));
    ok('New business has no products', (int)$products === 0);
    ok('Other businesses still have products', (int)$otherProducts > 0);

    $_SESSION['userdata'] = array(
        'login_type' => 1,
        'business_id' => $testBid,
        'id' => (int)$owner['id'],
        'type' => 1,
        'username' => $testUser,
    );
    $scoped = $conn->query("SELECT COUNT(*) AS c FROM products WHERE delete_flag = 0" . tenant_sql())->fetch_assoc()['c'];
    ok('Owner session sees only own empty product list', (int)$scoped === 0);

    echo "\nTest business credentials:\n";
    echo "  Username: {$testUser}\n";
    echo "  Password: Owner@2026\n";
    echo "  Slug: {$testSlug}\n";
    echo "  Business ID: {$testBid}\n";
}

// Rollback test
$conn->begin_transaction();
$slugRb = 'rollback-test-' . time();
$conn->query("INSERT INTO businesses (name, slug, currency, status) VALUES ('Rollback Test', '{$slugRb}', 'KES', 'trial')");
$rbId = (int)$conn->insert_id;
$conn->rollback();
$rbCheck = $conn->query("SELECT id FROM businesses WHERE slug = '{$slugRb}' LIMIT 1");
ok('Transaction rollback removes uncommitted business', !$rbCheck || $rbCheck->num_rows === 0);

// Duplicate slug
$_POST = array(
    'csrf' => tenant_csrf_token(),
    'name' => 'Dup Slug Test',
    'slug' => 'kalmoy-supermarket',
    'owner_username' => 'dup.slug.test',
    'owner_password' => 'Test@123',
    'sub_mode' => 'trial',
    'plan_id' => 1,
);
$dupSlug = json_decode($api->create_business(), true);
ok('Duplicate slug rejected', ($dupSlug['status'] ?? '') === 'failed' && stripos($dupSlug['msg'] ?? '', 'slug') !== false, $dupSlug['msg'] ?? '');

// Duplicate username
$_POST = array(
    'csrf' => tenant_csrf_token(),
    'name' => 'Dup User Test',
    'slug' => 'dup-user-test-' . time(),
    'owner_username' => 'ahmed.owner',
    'owner_password' => 'Test@123',
    'sub_mode' => 'trial',
    'plan_id' => 1,
);
$dupUser = json_decode($api->create_business(), true);
ok('Duplicate username rejected', ($dupUser['status'] ?? '') === 'failed' && stripos($dupUser['msg'] ?? '', 'username') !== false, $dupUser['msg'] ?? '');

echo "\nResults: {$pass} passed, {$fail} failed\n";
exit($fail > 0 ? 1 : 0);
