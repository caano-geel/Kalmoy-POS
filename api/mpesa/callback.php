<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json; charset=utf-8');
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_array($data)) throw new RuntimeException('Invalid callback request.');
    PaymentService::callback($conn, $data);
    echo json_encode(array('ResultCode' => 0, 'ResultDesc' => 'Accepted'));
} catch (Throwable $e) {
    error_log('M-PESA callback processing failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(array('ResultCode' => 1, 'ResultDesc' => 'Callback processing failed'));
}
