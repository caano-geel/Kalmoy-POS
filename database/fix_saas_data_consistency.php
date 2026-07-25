<?php
/**
 * Fix inconsistent SaaS seed/demo subscription and payment data.
 * Usage: php database/fix_saas_data_consistency.php
 */
require_once __DIR__ . '/../config.php';

echo "Fixing SaaS data consistency...\n";

$east = $conn->query("SELECT id FROM businesses WHERE slug = 'eastleigh-pharmacy' LIMIT 1")->fetch_assoc();
if ($east) {
    $bid = (int)$east['id'];
    $subRow = $conn->query("SELECT id FROM subscriptions WHERE business_id = {$bid} ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $trialEnd = date('Y-m-d H:i:s', strtotime('+14 days'));
    if ($subRow) {
        $sid = (int)$subRow['id'];
        $conn->query("UPDATE subscriptions SET status = 'trial', billing_cycle = 'trial', trial_ends_at = '{$trialEnd}', current_period_end = '{$trialEnd}' WHERE id = {$sid}");
    }
    $conn->query("UPDATE businesses SET status = 'trial' WHERE id = {$bid}");
    $conn->query("DELETE FROM subscription_payments WHERE business_id = {$bid}");
    echo "Eastleigh Pharmacy: restored trial subscription, removed payments, business status trial.\n";
}

$kalmoy = $conn->query("SELECT id FROM businesses WHERE slug = 'kalmoy-supermarket' LIMIT 1")->fetch_assoc();
if ($kalmoy) {
    $bid = (int)$kalmoy['id'];
    $plan = $conn->query("SELECT sp.price_monthly FROM subscriptions s INNER JOIN subscription_plans sp ON sp.id = s.plan_id WHERE s.business_id = {$bid} ORDER BY s.id DESC LIMIT 1")->fetch_assoc();
    if ($plan) {
        $amount = (float)$plan['price_monthly'];
        $conn->query("UPDATE subscription_payments SET amount = {$amount} WHERE business_id = {$bid} AND reference = 'SEED-kalmoy-supermarket'");
        echo "Kalmoy Supermarket: payment amount set to Ksh {$amount}.\n";
    }
}

echo "Done.\n";
