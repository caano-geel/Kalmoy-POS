<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config.php';

// Daraja callbacks remain authoritative; this only closes abandoned pending records.
$conn->query("UPDATE subscription_payments SET status = 'timeout', provider_result_description = 'No callback received within the payment window' WHERE status = 'pending' AND created_at < (NOW() - INTERVAL 30 MINUTE)");
$conn->query("UPDATE subscriptions SET status = 'expired' WHERE status IN ('active','trial') AND ((status = 'trial' AND trial_ends_at IS NOT NULL AND trial_ends_at <= NOW()) OR (status = 'active' AND current_period_end IS NOT NULL AND current_period_end <= NOW()))");
$conn->query("UPDATE businesses b LEFT JOIN subscriptions s ON s.business_id = b.id AND s.id = (SELECT MAX(id) FROM subscriptions WHERE business_id = b.id) SET b.status = 'expired' WHERE b.status IN ('active','trial') AND s.status = 'expired'");
echo "Billing processing complete\n";
