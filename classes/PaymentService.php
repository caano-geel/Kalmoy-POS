<?php

class PaymentService
{
    public static function createPending(mysqli $conn, $businessId, $plan, $phone)
    {
        $key = bin2hex(random_bytes(24));
        $stmt = $conn->prepare("INSERT INTO subscription_payments (business_id, subscription_id, plan_id, amount, currency, billing_cycle, payment_method, phone_number, idempotency_key, status, notes) VALUES (?, NULL, ?, ?, 'KES', ?, 'M-Pesa', ?, ?, 'pending', 'Daraja STK Push')");
        $planId = (int) $plan['id'];
        $amount = (float) $plan['amount'];
        $cycle = (string) $plan['billing_cycle'];
        $stmt->bind_param('iidsss', $businessId, $planId, $amount, $cycle, $phone, $key);
        if (!$stmt->execute()) throw new RuntimeException('Unable to create pending payment.');
        $paymentId = (int) $conn->insert_id;
        $event = $conn->prepare("INSERT INTO payment_events (payment_id, event_type, provider_event_key, payload_json) VALUES (?, 'initiated', ?, NULL)");
        $eventKey = 'payment-initiated:' . $paymentId;
        $event->bind_param('is', $paymentId, $eventKey);
        $event->execute();
        return array('id' => $paymentId, 'idempotency_key' => $key);
    }

    public static function setStkResponse(mysqli $conn, $paymentId, array $response)
    {
        $stmt = $conn->prepare('UPDATE subscription_payments SET merchant_request_id = ?, checkout_request_id = ? WHERE id = ? AND status = \'pending\'');
        $merchant = (string) ($response['MerchantRequestID'] ?? '');
        $checkout = (string) ($response['CheckoutRequestID'] ?? '');
        $stmt->bind_param('ssi', $merchant, $checkout, $paymentId);
        if (!$stmt->execute()) throw new RuntimeException('Unable to store M-PESA request.');
    }

    public static function fail(mysqli $conn, $paymentId, $description)
    {
        $stmt = $conn->prepare("UPDATE subscription_payments SET status = 'failed', provider_result_description = ? WHERE id = ? AND status = 'pending'");
        $stmt->bind_param('si', $description, $paymentId);
        $stmt->execute();
    }

    public static function callback(mysqli $conn, array $callback)
    {
        $body = $callback['Body']['stkCallback'] ?? null;
        if (!is_array($body) || empty($body['CheckoutRequestID'])) throw new RuntimeException('Invalid callback.');
        $checkout = (string) $body['CheckoutRequestID'];
        $stmt = $conn->prepare('SELECT * FROM subscription_payments WHERE checkout_request_id = ? LIMIT 1');
        $stmt->bind_param('s', $checkout);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        if (!$payment) throw new RuntimeException('Payment not found.');
        $eventKey = 'callback:' . $checkout;
        $payload = json_encode($callback);
        $conn->begin_transaction();
        try {
            $event = $conn->prepare('INSERT IGNORE INTO payment_events (payment_id, event_type, provider_event_key, payload_json) VALUES (?, \'callback\', ?, ?)');
            $event->bind_param('iss', $payment['id'], $eventKey, $payload);
            $event->execute();
            if ($event->affected_rows === 0) {
                $conn->commit();
                return $payment;
            }
            $resultCode = (int) ($body['ResultCode'] ?? -1);
            $resultDesc = (string) ($body['ResultDesc'] ?? '');
            if ($resultCode !== 0) {
                $update = $conn->prepare("UPDATE subscription_payments SET status = 'failed', provider_result_code = ?, provider_result_description = ? WHERE id = ? AND status = 'pending'");
                $code = (string) $resultCode;
                $update->bind_param('ssi', $code, $resultDesc, $payment['id']);
                $update->execute();
            } else {
                $items = $body['CallbackMetadata']['Item'] ?? array();
                $metadata = array();
                foreach ($items as $item) if (isset($item['Name'])) $metadata[$item['Name']] = $item['Value'] ?? null;
                if (isset($metadata['Amount']) && abs((float) $metadata['Amount'] - (float) $payment['amount']) > 0.01) throw new RuntimeException('Payment amount mismatch.');
                $receipt = (string) ($metadata['MpesaReceiptNumber'] ?? '');
                if ($receipt === '') throw new RuntimeException('M-PESA receipt was not returned.');
                $date = (string) ($metadata['TransactionDate'] ?? '');
                $transactionDate = $date !== '' ? DateTime::createFromFormat('YmdHis', $date) : false;
                $update = $conn->prepare("UPDATE subscription_payments SET status = 'paid', mpesa_receipt = ?, provider_result_code = '0', provider_result_description = ?, transaction_date = ? WHERE id = ? AND status = 'pending'");
                $dateSql = $transactionDate ? $transactionDate->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');
                $update->bind_param('sssi', $receipt, $resultDesc, $dateSql, $payment['id']);
                if (!$update->execute()) throw new RuntimeException('Unable to record payment.');
                SubscriptionService::applySuccessfulPayment($conn, $payment['id']);
            }
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
        return $payment;
    }
}
