<?php
/**
 * Subscription access tests.
 * Usage: php database/test_subscription.php
 */
require_once __DIR__ . '/../config.php';

$pass = 0; $fail = 0;
function t($label, $cond){ global $pass,$fail; echo ($cond?"[PASS]":"[FAIL]")." {$label}\n"; $cond?$pass++:$fail++; }

$b2 = (int)$conn->query("SELECT id FROM businesses WHERE slug='eastleigh-pharmacy' LIMIT 1")->fetch_assoc()['id'];
$conn->query("UPDATE subscriptions SET status='expired', current_period_end='2020-01-01 00:00:00' WHERE business_id={$b2} ORDER BY id DESC LIMIT 1");
$sub = tenant_subscription_status($b2);
t('Expired subscription blocks access', empty($sub['allowed']) && $sub['status'] === 'expired');

$conn->query("UPDATE subscriptions SET status='active', billing_cycle='monthly', current_period_end='" . date('Y-m-d H:i:s', strtotime('+30 days')) . "' WHERE business_id={$b2} ORDER BY id DESC LIMIT 1");
$conn->query("UPDATE businesses SET status='active' WHERE id={$b2}");
$sub2 = tenant_subscription_status($b2);
t('Renewed subscription restores access', !empty($sub2['allowed']));

$conn->query("UPDATE businesses SET status='suspended' WHERE id={$b2}");
$sub3 = tenant_subscription_status($b2);
t('Suspended business blocks access', empty($sub3['allowed']));
$conn->query("UPDATE businesses SET status='active' WHERE id={$b2}");

echo "\nResults: {$pass} passed, {$fail} failed\n";
exit($fail > 0 ? 1 : 0);
