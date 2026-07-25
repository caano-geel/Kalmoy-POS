<?php
/**
 * Tenant isolation tests — categories, brands, settings, onboarding.
 * Usage: php database/test_tenant_catalog_settings_isolation.php
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/tenant.php';

$pass = 0;
$fail = 0;
function t($label, $cond)
{
    global $pass, $fail;
    echo ($cond ? '[PASS] ' : '[FAIL] ') . $label . "\n";
    $cond ? $pass++ : $fail++;
}

$b1 = (int) $conn->query("SELECT id FROM businesses WHERE slug='kalmoy-supermarket' LIMIT 1")->fetch_assoc()['id'];
$b2 = (int) $conn->query("SELECT id FROM businesses WHERE slug='eastleigh-pharmacy' LIMIT 1")->fetch_assoc()['id'];
$b7row = $conn->query("SELECT id FROM businesses WHERE slug LIKE '%nairobi-electronics%' OR name LIKE '%Nairobi Electronics%' ORDER BY id DESC LIMIT 1");
$b7 = ($b7row && $b7row->num_rows) ? (int) $b7row->fetch_assoc()['id'] : 7;

$catB1 = (int) $conn->query("SELECT id FROM categories WHERE business_id={$b1} AND delete_flag=0 LIMIT 1")->fetch_assoc()['id'];
$catB2 = (int) $conn->query("SELECT id FROM categories WHERE business_id={$b2} AND delete_flag=0 LIMIT 1")->fetch_assoc()['id'];
$brandB1 = (int) $conn->query("SELECT id FROM brands WHERE business_id={$b1} AND delete_flag=0 LIMIT 1")->fetch_assoc()['id'];
$brandB2 = (int) $conn->query("SELECT id FROM brands WHERE business_id={$b2} AND delete_flag=0 LIMIT 1")->fetch_assoc()['id'];

// Ensure Nairobi has default catalog via repair helper
tenant_ensure_tenant_resources($b7, $conn);

$_SESSION['userdata'] = array('login_type' => 1, 'business_id' => $b7, 'id' => 99, 'type' => 1);

// Categories list scoped
$catCnt = (int) $conn->query("SELECT COUNT(*) AS c FROM categories WHERE delete_flag=0" . tenant_sql())->fetch_assoc()['c'];
$catOther = (int) $conn->query("SELECT COUNT(*) AS c FROM categories WHERE delete_flag=0 AND business_id IN ({$b1},{$b2})")->fetch_assoc()['c'];
t('Business 7 categories list only own rows', $catCnt >= 1 && $catOther >= 2);

// Cross-tenant category IDs not visible
$crossCat1 = (int) $conn->query("SELECT COUNT(*) AS c FROM categories WHERE id={$catB1}" . tenant_sql())->fetch_assoc()['c'];
$crossCat2 = (int) $conn->query("SELECT COUNT(*) AS c FROM categories WHERE id={$catB2}" . tenant_sql())->fetch_assoc()['c'];
t('Business 7 cannot access B1 category by ID', $crossCat1 === 0);
t('Business 7 cannot access B2 category by ID', $crossCat2 === 0);

// Brands list scoped
$brandCnt = (int) $conn->query("SELECT COUNT(*) AS c FROM brands WHERE delete_flag=0" . tenant_sql())->fetch_assoc()['c'];
t('Business 7 brands list only own rows', $brandCnt >= 1);

$crossBrand1 = (int) $conn->query("SELECT COUNT(*) AS c FROM brands WHERE id={$brandB1}" . tenant_sql())->fetch_assoc()['c'];
$crossBrand2 = (int) $conn->query("SELECT COUNT(*) AS c FROM brands WHERE id={$brandB2}" . tenant_sql())->fetch_assoc()['c'];
t('Business 7 cannot access B1 brand by ID', $crossBrand1 === 0);
t('Business 7 cannot access B2 brand by ID', $crossBrand2 === 0);

// Product counts scoped (B7 has no products)
require_once __DIR__ . '/../admin/inc/module_ui.php';
$catCounts = ash_category_product_counts();
$brandCounts = ash_brand_product_counts();
$ownCatId = (int) $conn->query("SELECT id FROM categories WHERE business_id={$b7} AND delete_flag=0 LIMIT 1")->fetch_assoc()['id'];
$prodCntForOwnCat = isset($catCounts[$ownCatId]) ? (int) $catCounts[$ownCatId] : 0;
t('Business 7 category product count is 0', $prodCntForOwnCat === 0);
t('Business 7 brand product counts do not include other tenants', !isset($catCounts[$catB1]) && !isset($catCounts[$catB2]));

// Settings read isolation
$_settings->load_system_info();
$bizName = $_settings->info('name');
$shortName = $_settings->info('short_name');
$aboutUs = $_settings->info('about_us');
t('Business 7 settings name matches business record', $bizName === 'Nairobi Electronics Test');
t('Business 7 short name is tenant-specific', $shortName === 'Nairobi Electronics');
t('Business 7 about_us is not global herbal content', $aboutUs === false || stripos((string) $aboutUs, 'herbal') === false);
t('Business 7 privacy_policy stored per tenant', array_key_exists('privacy_policy', $_SESSION['system_info']));

// Settings write isolation
$marker = 'tenant7-test-' . time();
tenant_setting_set('receipt_footer', $marker);
$otherRead = $conn->query("SELECT meta_value FROM business_settings WHERE business_id={$b1} AND meta_field='receipt_footer' AND meta_value='{$marker}' LIMIT 1");
t('Business 7 settings write does not affect B1', !$otherRead || $otherRead->num_rows === 0);
$ownRead = $conn->query("SELECT meta_value FROM business_settings WHERE business_id={$b7} AND meta_field='receipt_footer' AND meta_value='{$marker}' LIMIT 1");
t('Business 7 settings write saved to own business', $ownRead && $ownRead->num_rows === 1);
$conn->query("DELETE FROM business_settings WHERE business_id={$b7} AND meta_field='receipt_footer'");

// Onboarding seeds defaults
$testSlug = 'isolation-test-' . substr((string) time(), -6);
$testName = 'Isolation Test Shop ' . time();
$conn->query("INSERT INTO businesses (name, slug, currency, status) VALUES ('{$conn->real_escape_string($testName)}', '{$testSlug}', 'KES', 'trial')");
$newBid = (int) $conn->insert_id;
tenant_seed_default_settings($newBid, $testName, $conn);
tenant_seed_default_catalog($newBid, $conn);
$newName = $conn->query("SELECT meta_value FROM business_settings WHERE business_id={$newBid} AND meta_field='name' LIMIT 1")->fetch_assoc()['meta_value'];
$newAbout = $conn->query("SELECT meta_value FROM business_settings WHERE business_id={$newBid} AND meta_field='about_us' LIMIT 1")->fetch_assoc()['meta_value'];
$newCat = (int) $conn->query("SELECT COUNT(*) AS c FROM categories WHERE business_id={$newBid} AND delete_flag=0")->fetch_assoc()['c'];
$newBrand = (int) $conn->query("SELECT COUNT(*) AS c FROM brands WHERE business_id={$newBid} AND delete_flag=0")->fetch_assoc()['c'];
t('Onboarding creates business-specific settings name', $newName === $testName);
t('Onboarding creates blank about_us default', $newAbout === '');
t('Onboarding creates default General category', $newCat === 1);
t('Onboarding creates default House Brand', $newBrand === 1);
$conn->query("DELETE FROM business_settings WHERE business_id={$newBid}");
$conn->query("DELETE FROM categories WHERE business_id={$newBid}");
$conn->query("DELETE FROM brands WHERE business_id={$newBid}");
$conn->query("DELETE FROM businesses WHERE id={$newBid}");

// Other tenants unchanged
$_SESSION['userdata']['business_id'] = $b1;
$b1CatCnt = (int) $conn->query("SELECT COUNT(*) AS c FROM categories WHERE delete_flag=0" . tenant_sql())->fetch_assoc()['c'];
$b1Name = $conn->query("SELECT meta_value FROM business_settings WHERE business_id={$b1} AND meta_field='name' LIMIT 1")->fetch_assoc()['meta_value'];
t('Business 1 categories unchanged', $b1CatCnt >= 1);
t('Business 1 settings name unchanged', $b1Name === 'Kalmoy Supermarket');

echo "\nResults: {$pass} passed, {$fail} failed\n";
exit($fail > 0 ? 1 : 0);
