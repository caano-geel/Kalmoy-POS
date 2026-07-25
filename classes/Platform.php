<?php
require_once __DIR__ . '/../config.php';

class Platform extends DBConnection
{
    public function login()
    {
        if (!tenant_verify_csrf($_POST['csrf'] ?? '')) {
            return json_encode(array('status' => 'failed', 'msg' => 'Invalid security token.'));
        }
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username === '' || $password === '') {
            return json_encode(array('status' => 'failed', 'msg' => 'Username and password required.'));
        }
        $stmt = $this->conn->prepare('SELECT * FROM platform_users WHERE username = ? AND status = 1 LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res || !$res->num_rows) {
            return json_encode(array('status' => 'failed', 'msg' => 'Invalid credentials.'));
        }
        $user = $res->fetch_assoc();
        if (!password_verify($password, $user['password'])) {
            return json_encode(array('status' => 'failed', 'msg' => 'Invalid credentials.'));
        }
        if (isset($_SESSION['userdata'])) {
            unset($_SESSION['userdata']);
        }
        session_regenerate_id(true);
        $_SESSION['platform_user'] = array(
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'username' => $user['username'],
            'email' => $user['email'],
        );
        $this->conn->query('UPDATE platform_users SET last_login = NOW() WHERE id = ' . (int)$user['id']);
        platform_audit_log('login', 'Platform login');
        return json_encode(array('status' => 'success', 'redirect' => base_url . 'platform/'));
    }

    public function create_business()
    {
        if (!platform_logged_in()) {
            return json_encode(array('status' => 'failed', 'msg' => 'Unauthorized'));
        }
        if (!tenant_verify_csrf($_POST['csrf'] ?? '')) {
            return json_encode(array('status' => 'failed', 'msg' => 'Invalid CSRF token'));
        }

        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $plan_id = (int)($_POST['plan_id'] ?? 0);
        $sub_mode = strtolower(trim($_POST['sub_mode'] ?? 'trial'));
        $owner_fn = trim($_POST['owner_firstname'] ?? '');
        $owner_ln = trim($_POST['owner_lastname'] ?? '');
        $owner_user = trim($_POST['owner_username'] ?? '');
        $owner_email = trim($_POST['owner_email'] ?? '');
        $owner_pass = (string)($_POST['owner_password'] ?? '');

        if ($name === '' || $slug === '' || $owner_user === '' || $owner_pass === '') {
            return json_encode(array('status' => 'failed', 'msg' => 'Required fields missing.'));
        }
        if (strlen($owner_pass) < 6) {
            return json_encode(array('status' => 'failed', 'msg' => 'Owner password must be at least 6 characters.'));
        }
        if (!in_array($sub_mode, array('trial', 'active'), true)) {
            return json_encode(array('status' => 'failed', 'msg' => 'Invalid subscription mode.'));
        }

        $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower($slug));
        $slug = trim(preg_replace('/-+/', '-', $slug), '-');
        if ($slug === '') {
            return json_encode(array('status' => 'failed', 'msg' => 'Invalid business slug.'));
        }

        $slugStmt = $this->conn->prepare('SELECT id FROM businesses WHERE slug = ? LIMIT 1');
        $slugStmt->bind_param('s', $slug);
        $slugStmt->execute();
        if ($slugStmt->get_result()->num_rows > 0) {
            return json_encode(array('status' => 'failed', 'msg' => 'Business slug already exists.'));
        }

        $userStmt = $this->conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $userStmt->bind_param('s', $owner_user);
        $userStmt->execute();
        if ($userStmt->get_result()->num_rows > 0) {
            return json_encode(array('status' => 'failed', 'msg' => 'Owner username already exists.'));
        }

        if ($plan_id <= 0) {
            $defaultPlan = $this->conn->query('SELECT id FROM subscription_plans WHERE status = 1 ORDER BY sort_order, id LIMIT 1');
            if (!$defaultPlan || !$defaultPlan->num_rows) {
                return json_encode(array('status' => 'failed', 'msg' => 'No active subscription plan available.'));
            }
            $plan_id = (int)$defaultPlan->fetch_assoc()['id'];
        }

        $planStmt = $this->conn->prepare('SELECT id, trial_days FROM subscription_plans WHERE id = ? AND status = 1 LIMIT 1');
        $planStmt->bind_param('i', $plan_id);
        $planStmt->execute();
        $planRes = $planStmt->get_result();
        if (!$planRes || !$planRes->num_rows) {
            return json_encode(array('status' => 'failed', 'msg' => 'Invalid or inactive subscription plan.'));
        }
        $plan = $planRes->fetch_assoc();
        $trialDays = max(1, (int)($plan['trial_days'] ?? 14));

        $bizStatus = ($sub_mode === 'trial') ? 'trial' : 'active';
        $this->conn->begin_transaction();
        try {
            $bizStmt = $this->conn->prepare('INSERT INTO businesses (name, slug, phone, email, address, currency, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $currency = 'KES';
            $bizStmt->bind_param('sssssss', $name, $slug, $phone, $email, $address, $currency, $bizStatus);
            if (!$bizStmt->execute()) {
                throw new Exception('Failed to create business record.');
            }
            $bid = (int)$this->conn->insert_id;
            if ($bid <= 0) {
                throw new Exception('Failed to create business record.');
            }

            if (!tenant_seed_default_settings($bid, $name, $this->conn)) {
                throw new Exception('Failed to seed default business settings.');
            }
            if (!tenant_seed_default_catalog($bid, $this->conn)) {
                throw new Exception('Failed to seed default catalog.');
            }

            if ($sub_mode === 'active') {
                $periodEnd = date('Y-m-d H:i:s', strtotime('+1 month'));
                $subStmt = $this->conn->prepare("INSERT INTO subscriptions (business_id, plan_id, status, billing_cycle, current_period_start, current_period_end) VALUES (?, ?, 'active', 'monthly', NOW(), ?)");
                $subStmt->bind_param('iis', $bid, $plan_id, $periodEnd);
            } else {
                $trialEnd = date('Y-m-d H:i:s', strtotime('+' . $trialDays . ' days'));
                $subStmt = $this->conn->prepare("INSERT INTO subscriptions (business_id, plan_id, status, billing_cycle, trial_ends_at, current_period_start, current_period_end) VALUES (?, ?, 'trial', 'trial', ?, NOW(), ?)");
                $subStmt->bind_param('iiss', $bid, $plan_id, $trialEnd, $trialEnd);
            }
            if (!$subStmt->execute()) {
                throw new Exception('Failed to create subscription.');
            }
            $subId = (int)$this->conn->insert_id;

            $hash = password_hash($owner_pass, PASSWORD_DEFAULT);
            $ownerType = 1;
            $ownerStatus = 1;
            $ownerStmt = $this->conn->prepare('INSERT INTO users (business_id, firstname, lastname, username, email, password, type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $ownerStmt->bind_param('isssssii', $bid, $owner_fn, $owner_ln, $owner_user, $owner_email, $hash, $ownerType, $ownerStatus);
            if (!$ownerStmt->execute()) {
                throw new Exception('Failed to create owner account.');
            }
            $ownerId = (int)$this->conn->insert_id;

            $ownerLink = $this->conn->prepare('UPDATE businesses SET owner_user_id = ? WHERE id = ?');
            $ownerLink->bind_param('ii', $ownerId, $bid);
            if (!$ownerLink->execute()) {
                throw new Exception('Failed to link business owner.');
            }

            $this->conn->commit();
            platform_audit_log('business_created', 'Created business ' . $name, $bid);

            return json_encode(array(
                'status' => 'success',
                'msg' => 'Business created.',
                'business_id' => $bid,
                'subscription_id' => $subId,
            ));
        } catch (Throwable $e) {
            $this->conn->rollback();
            return json_encode(array('status' => 'failed', 'msg' => $e->getMessage()));
        }
    }

    public function update_business()
    {
        if (!platform_logged_in()) {
            return json_encode(array('status' => 'failed', 'msg' => 'Unauthorized'));
        }
        if (!tenant_verify_csrf($_POST['csrf'] ?? '')) {
            return json_encode(array('status' => 'failed', 'msg' => 'Invalid CSRF token'));
        }
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        $allowed = array('active', 'trial', 'suspended', 'expired', 'cancelled', 'inactive');
        if ($id <= 0 || !in_array($status, $allowed, true)) {
            return json_encode(array('status' => 'failed', 'msg' => 'Invalid request'));
        }
        $fields = array('name', 'phone', 'email', 'address');
        $sets = array("status='" . $this->conn->real_escape_string($status) . "'");
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $sets[] = "`{$f}`='" . $this->conn->real_escape_string(trim($_POST[$f])) . "'";
            }
        }
        $this->conn->query('UPDATE businesses SET ' . implode(', ', $sets) . " WHERE id={$id}");
        platform_audit_log('business_updated', "Updated business #{$id} status={$status}", $id);
        return json_encode(array('status' => 'success', 'msg' => 'Business updated.'));
    }

    public function assign_plan()
    {
        if (!platform_logged_in()) {
            return json_encode(array('status' => 'failed', 'msg' => 'Unauthorized'));
        }
        if (!tenant_verify_csrf($_POST['csrf'] ?? '')) {
            return json_encode(array('status' => 'failed', 'msg' => 'Invalid CSRF token'));
        }
        $bid = (int)($_POST['business_id'] ?? 0);
        $plan_id = (int)($_POST['plan_id'] ?? 0);
        $mode = $_POST['mode'] ?? 'active';
        if ($bid <= 0 || $plan_id <= 0) {
            return json_encode(array('status' => 'failed', 'msg' => 'Invalid business or plan'));
        }
        if ($mode === 'trial') {
            $end = date('Y-m-d H:i:s', strtotime('+14 days'));
            $this->conn->query("INSERT INTO subscriptions SET business_id={$bid}, plan_id={$plan_id}, status='trial', billing_cycle='trial', trial_ends_at='{$end}', current_period_start=NOW(), current_period_end='{$end}'");
        } else {
            $end = date('Y-m-d H:i:s', strtotime('+1 month'));
            $this->conn->query("INSERT INTO subscriptions SET business_id={$bid}, plan_id={$plan_id}, status='active', billing_cycle='monthly', current_period_start=NOW(), current_period_end='{$end}'");
        }
        $this->conn->query("UPDATE businesses SET status='active' WHERE id={$bid}");
        platform_audit_log('subscription_assigned', "Assigned plan #{$plan_id} ({$mode})", $bid);
        return json_encode(array('status' => 'success', 'msg' => 'Subscription assigned.'));
    }

    public function extend_subscription()
    {
        if (!platform_logged_in()) {
            return json_encode(array('status' => 'failed', 'msg' => 'Unauthorized'));
        }
        if (!tenant_verify_csrf($_POST['csrf'] ?? '')) {
            return json_encode(array('status' => 'failed', 'msg' => 'Invalid CSRF token'));
        }
        $bid = (int)($_POST['business_id'] ?? 0);
        $days = max(1, (int)($_POST['days'] ?? 30));
        if ($bid <= 0) {
            return json_encode(array('status' => 'failed', 'msg' => 'Invalid business'));
        }
        $sub = $this->conn->query("SELECT * FROM subscriptions WHERE business_id={$bid} ORDER BY id DESC LIMIT 1")->fetch_assoc();
        if (!$sub) {
            return json_encode(array('status' => 'failed', 'msg' => 'No subscription found'));
        }
        $end = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        $this->conn->query("UPDATE subscriptions SET status='active', billing_cycle='monthly', current_period_end='{$end}' WHERE id=" . (int)$sub['id']);
        $this->conn->query("UPDATE businesses SET status='active' WHERE id={$bid}");
        platform_audit_log('subscription_extended', "Extended {$days} days", $bid);
        return json_encode(array('status' => 'success', 'msg' => 'Subscription extended.'));
    }

    public function record_payment()
    {
        if (!platform_logged_in()) {
            return json_encode(array('status' => 'failed', 'msg' => 'Unauthorized'));
        }
        if (!tenant_verify_csrf($_POST['csrf'] ?? '')) {
            return json_encode(array('status' => 'failed', 'msg' => 'Invalid CSRF token'));
        }
        $bid = (int)($_POST['business_id'] ?? 0);
        $plan_id = (int)($_POST['plan_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $reference = trim($_POST['reference'] ?? '');
        $method = trim($_POST['payment_method'] ?? 'manual');
        $notes = trim($_POST['notes'] ?? '');
        if ($bid <= 0 || $amount <= 0) {
            return json_encode(array('status' => 'failed', 'msg' => 'Invalid payment data'));
        }
        $sub = $this->conn->query("SELECT id FROM subscriptions WHERE business_id={$bid} ORDER BY id DESC LIMIT 1")->fetch_assoc();
        $subId = $sub ? (int)$sub['id'] : 0;
        if ($plan_id <= 0 && $subId) {
            $plan_id = (int)$this->conn->query("SELECT plan_id FROM subscriptions WHERE id={$subId}")->fetch_assoc()['plan_id'];
        }
        $esc = function ($v) {
            return $this->conn->real_escape_string($v);
        };
        $uid = (int)platform_user('id');
        $this->conn->query("INSERT INTO subscription_payments SET business_id={$bid}, subscription_id={$subId}, plan_id={$plan_id}, amount={$amount}, payment_method='{$esc($method)}', reference='{$esc($reference)}', status='paid', notes='{$esc($notes)}', created_by_platform_user_id={$uid}");
        $end = date('Y-m-d H:i:s', strtotime('+1 month'));
        if ($subId) {
            $this->conn->query("UPDATE subscriptions SET status='active', billing_cycle='monthly', current_period_end='{$end}' WHERE id={$subId}");
        }
        $this->conn->query("UPDATE businesses SET status='active' WHERE id={$bid}");
        platform_audit_log('payment_recorded', "Payment Ksh {$amount} ref {$reference}", $bid);
        return json_encode(array('status' => 'success', 'msg' => 'Payment recorded and subscription activated.'));
    }

    public function delete_business()
    {
        if (!platform_logged_in()) {
            return json_encode(array('status' => 'failed', 'msg' => 'Unauthorized'));
        }
        if (!tenant_verify_csrf($_POST['csrf'] ?? '')) {
            return json_encode(array('status' => 'failed', 'msg' => 'Invalid CSRF token'));
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            return json_encode(array('status' => 'failed', 'msg' => 'Invalid business ID.'));
        }
        $row = $this->conn->query("SELECT name FROM businesses WHERE id = {$id} LIMIT 1")->fetch_assoc();
        if (!$row) {
            return json_encode(array('status' => 'failed', 'msg' => 'Business not found.'));
        }
        if (!$this->conn->query("DELETE FROM businesses WHERE id = {$id} LIMIT 1")) {
            return json_encode(array('status' => 'failed', 'msg' => 'Failed to delete business: ' . $this->conn->error));
        }
        platform_audit_log('business_deleted', 'Deleted business ' . $row['name'], $id);
        return json_encode(array('status' => 'success', 'msg' => 'Business deleted.'));
    }

    public function reset_owner_password()
    {
        if (!platform_logged_in()) {
            return json_encode(array('status' => 'failed', 'msg' => 'Unauthorized'));
        }
        if (!tenant_verify_csrf($_POST['csrf'] ?? '')) {
            return json_encode(array('status' => 'failed', 'msg' => 'Invalid CSRF token'));
        }
        $bid = (int)($_POST['business_id'] ?? 0);
        $pass = $_POST['new_password'] ?? '';
        if ($bid <= 0 || strlen($pass) < 6) {
            return json_encode(array('status' => 'failed', 'msg' => 'Invalid password reset request'));
        }
        $b = $this->conn->query("SELECT owner_user_id FROM businesses WHERE id={$bid} LIMIT 1")->fetch_assoc();
        if (!$b || !(int)$b['owner_user_id']) {
            return json_encode(array('status' => 'failed', 'msg' => 'Owner not found'));
        }
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $this->conn->query("UPDATE users SET password='{$hash}' WHERE id=" . (int)$b['owner_user_id']);
        platform_audit_log('owner_password_reset', 'Owner password reset', $bid);
        return json_encode(array('status' => 'success', 'msg' => 'Owner password reset.'));
    }
}

$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$api = new Platform();
if (defined('PLATFORM_SKIP_DISPATCH') && PLATFORM_SKIP_DISPATCH) {
    return;
}
switch ($action) {
    case 'login':
        echo $api->login();
        break;
    case 'create_business':
        echo $api->create_business();
        break;
    case 'update_business':
        echo $api->update_business();
        break;
    case 'assign_plan':
        echo $api->assign_plan();
        break;
    case 'extend_subscription':
        echo $api->extend_subscription();
        break;
    case 'record_payment':
        echo $api->record_payment();
        break;
    case 'delete_business':
        echo $api->delete_business();
        break;
    case 'reset_owner_password':
        echo $api->reset_owner_password();
        break;
    default:
        echo json_encode(array('status' => 'failed', 'msg' => 'Unknown action'));
}
