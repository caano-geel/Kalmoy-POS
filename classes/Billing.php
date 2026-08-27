<?php
require_once __DIR__ . '/../config.php';

function billing_require_tenant()
{
    if (!isset($_SESSION['userdata']['login_type']) || (int) $_SESSION['userdata']['login_type'] !== 1 || tenant_id() <= 0 || !admin_is_owner()) {
        http_response_code(401);
        echo json_encode(array('status' => 'failed', 'msg' => 'Authentication required.'));
        exit;
    }
}

function billing_json($data, $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function billing_csrf()
{
    if (!tenant_verify_csrf($_POST['csrf'] ?? '')) billing_json(array('status' => 'failed', 'msg' => 'Invalid security token.'), 419);
}

function billing_start_mpesa()
{
    global $conn;
    billing_csrf();
    $planId = (int) ($_POST['plan_id'] ?? 0);
    $cycle = $_POST['billing_cycle'] ?? 'monthly';
    $phone = MpesaService::normalizePhone($_POST['phone'] ?? '');
    if (!$phone) billing_json(array('status' => 'failed', 'msg' => 'Enter a valid Kenyan M-PESA number.'), 422);
    $plan = SubscriptionService::plan($conn, $planId, $cycle);
    if (!$plan) billing_json(array('status' => 'failed', 'msg' => 'The selected plan is unavailable.'), 422);
    $bid = tenant_id();
    try {
        $payment = PaymentService::createPending($conn, $bid, $plan, $phone);
        $reference = 'KALMOY-' . $bid . '-' . $payment['id'];
        $response = (new MpesaService())->initiateStkPush($phone, $plan['amount'], $reference, 'Kalmoy POS subscription');
        PaymentService::setStkResponse($conn, $payment['id'], $response);
        billing_json(array('status' => 'success', 'payment_id' => $payment['id'], 'payment_status' => 'pending', 'message' => 'STK Push sent. Check your phone and enter your M-PESA PIN.'));
    } catch (Throwable $e) {
        if (!empty($payment['id'])) PaymentService::fail($conn, $payment['id'], 'Provider request failed.');
        billing_json(array('status' => 'failed', 'msg' => $e->getMessage()), 502);
    }
}

function billing_status()
{
    global $conn;
    $id = (int) ($_GET['id'] ?? 0);
    $stmt = $conn->prepare('SELECT id, amount, currency, billing_cycle, payment_method, phone_number, status, mpesa_receipt, provider_result_description, payment_date, created_at FROM subscription_payments WHERE id = ? AND business_id = ? LIMIT 1');
    $bid = tenant_id();
    $stmt->bind_param('ii', $id, $bid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) billing_json(array('status' => 'failed', 'msg' => 'Payment not found.'), 404);
    billing_json(array('status' => 'success', 'payment' => $row));
}

function billing_plans()
{
    global $conn;
    $rows = array();
    $q = $conn->query('SELECT id, name, description, price_monthly, price_yearly, trial_days, max_users, max_products, features_json FROM subscription_plans WHERE status = 1 ORDER BY sort_order, id');
    while ($q && ($row = $q->fetch_assoc())) $rows[] = $row;
    billing_json(array('status' => 'success', 'plans' => $rows));
}

function billing_history()
{
    global $conn;
    $rows = array();
    $stmt = $conn->prepare('SELECT id, amount, currency, billing_cycle, payment_method, status, mpesa_receipt, payment_date, created_at FROM subscription_payments WHERE business_id = ? ORDER BY id DESC LIMIT 100');
    $bid = tenant_id();
    $stmt->bind_param('i', $bid);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    billing_json(array('status' => 'success', 'payments' => $rows));
}

billing_require_tenant();
$action = strtolower($_GET['f'] ?? '');
switch ($action) {
    case 'plans': billing_plans(); break;
    case 'start_mpesa': billing_start_mpesa(); break;
    case 'status': billing_status(); break;
    case 'history': billing_history(); break;
    default: billing_json(array('status' => 'failed', 'msg' => 'Unknown billing action.'), 404);
}
