<?php

class SubscriptionService
{
    public static function plan(mysqli $conn, $planId, $cycle)
    {
        $cycle = strtolower(trim((string) $cycle));
        if (!in_array($cycle, array('monthly', 'yearly'), true)) return null;
        $stmt = $conn->prepare('SELECT * FROM subscription_plans WHERE id = ? AND status = 1 LIMIT 1');
        $stmt->bind_param('i', $planId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) return null;
        $row['billing_cycle'] = $cycle;
        $row['amount'] = number_format((float) ($cycle === 'yearly' ? $row['price_yearly'] : $row['price_monthly']), 2, '.', '');
        if ((float) $row['amount'] <= 0 || (float) $row['amount'] != (float) round($row['amount'])) return null;
        return $row;
    }

    public static function effective(mysqli $conn, $businessId)
    {
        $stmt = $conn->prepare('SELECT s.*, p.name AS plan_name FROM subscriptions s LEFT JOIN subscription_plans p ON p.id = s.plan_id WHERE s.business_id = ? ORDER BY s.id DESC LIMIT 1');
        $stmt->bind_param('i', $businessId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) return array('allowed' => false, 'status' => 'none');
        $end = $row['status'] === 'trial' ? $row['trial_ends_at'] : $row['current_period_end'];
        if (in_array($row['status'], array('active', 'trial'), true) && (!$end || strtotime($end) <= time())) {
            return array('allowed' => false, 'status' => 'expired', 'subscription' => $row);
        }
        return array('allowed' => $row['status'] === 'active' || $row['status'] === 'trial', 'status' => $row['status'], 'subscription' => $row);
    }

    public static function applySuccessfulPayment(mysqli $conn, $paymentId)
    {
        $stmt = $conn->prepare('SELECT * FROM subscription_payments WHERE id = ? FOR UPDATE');
        $stmt->bind_param('i', $paymentId);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        if (!$payment) throw new RuntimeException('Payment not found.');
        if ($payment['status'] === 'paid' && !empty($payment['subscription_action_applied_at'])) return false;
        if ($payment['status'] !== 'paid') throw new RuntimeException('Payment is not marked paid.');

        $subStmt = $conn->prepare('SELECT * FROM subscriptions WHERE business_id = ? ORDER BY id DESC LIMIT 1 FOR UPDATE');
        $subStmt->bind_param('i', $payment['business_id']);
        $subStmt->execute();
        $sub = $subStmt->get_result()->fetch_assoc();
        $now = new DateTimeImmutable('now', new DateTimeZone('Africa/Nairobi'));
        $cycle = isset($payment['billing_cycle']) && $payment['billing_cycle'] === 'yearly' ? 'yearly' : 'monthly';
        $unit = $cycle === 'yearly' ? 'year' : 'month';
        if ($sub && $sub['status'] === 'active' && !empty($sub['current_period_end']) && strtotime($sub['current_period_end']) > $now->getTimestamp()) {
            $start = new DateTimeImmutable($sub['current_period_end'], new DateTimeZone('Africa/Nairobi'));
        } else {
            $start = $now;
        }
        $end = $start->modify('+1 ' . $unit);
        if ($sub) {
            $update = $conn->prepare("UPDATE subscriptions SET plan_id = ?, amount = ?, status = 'active', billing_cycle = ?, current_period_start = ?, current_period_end = ?, trial_ends_at = NULL WHERE id = ?");
            $planId = (int) $payment['plan_id'];
            $amount = (float) $payment['amount'];
            $startSql = $start->format('Y-m-d H:i:s');
            $endSql = $end->format('Y-m-d H:i:s');
            $subIdValue = (int) $sub['id'];
            $update->bind_param('idsssi', $planId, $amount, $cycle, $startSql, $endSql, $subIdValue);
            if (!$update->execute()) throw new RuntimeException('Subscription activation failed.');
            $subId = $subIdValue;
        } else {
            $insert = $conn->prepare("INSERT INTO subscriptions (business_id, plan_id, amount, status, billing_cycle, current_period_start, current_period_end) VALUES (?, ?, ?, 'active', ?, ?, ?)");
            $businessId = (int) $payment['business_id'];
            $planId = (int) $payment['plan_id'];
            $amount = (float) $payment['amount'];
            $startSql = $start->format('Y-m-d H:i:s');
            $endSql = $end->format('Y-m-d H:i:s');
            $insert->bind_param('iidsss', $businessId, $planId, $amount, $cycle, $startSql, $endSql);
            if (!$insert->execute()) throw new RuntimeException('Subscription activation failed.');
            $subId = $conn->insert_id;
        }
        $link = $conn->prepare('UPDATE subscription_payments SET subscription_id = ?, subscription_action_applied_at = NOW() WHERE id = ? AND subscription_action_applied_at IS NULL');
        $link->bind_param('ii', $subId, $paymentId);
        if (!$link->execute() || $link->affected_rows !== 1) return false;
        $eventKey = 'subscription-action:' . $paymentId;
        $event = $conn->prepare("INSERT INTO payment_events (payment_id, event_type, provider_event_key, payload_json) VALUES (?, 'subscription_activated', ?, ?)");
        $eventPayload = json_encode(array('subscription_id' => $subId, 'billing_cycle' => $cycle));
        $event->bind_param('iss', $paymentId, $eventKey, $eventPayload);
        if (!$event->execute()) throw new RuntimeException('Subscription event could not be recorded.');
        $business = $conn->prepare("UPDATE businesses SET status = 'active' WHERE id = ?");
        $business->bind_param('i', $payment['business_id']);
        $business->execute();
        return true;
    }
}
