<?php
/**
 * Demo seed for SaaS MVP — platform owner + 2 businesses.
 */
if (!isset($conn) || !$conn) {
    require_once __DIR__ . '/../config.php';
}

function seed_hash($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

// Plans
$plans = array(
    array('starter', 'Starter', 'Essential POS for small shops', 1500, 15000, 14, 3, 200),
    array('business', 'Business', 'Full retail operations for growing stores', 3500, 35000, 14, 10, 1000),
    array('premium', 'Premium', 'Advanced features and higher limits', 6500, 65000, 14, 25, null),
);
foreach ($plans as $p) {
    $features = json_encode(array('pos' => true, 'debt' => $p[0] !== 'starter', 'analytics' => $p[0] === 'premium', 'backup' => true));
    $maxUsers = $p[6] === null ? 'NULL' : (int) $p[6];
    $maxProducts = $p[7] === null ? 'NULL' : (int) $p[7];
    $conn->query("INSERT INTO subscription_plans (code, name, description, price_monthly, price_yearly, trial_days, max_users, max_products, features_json, sort_order)
        VALUES ('{$p[0]}', '{$conn->real_escape_string($p[1])}', '{$conn->real_escape_string($p[2])}', {$p[3]}, {$p[4]}, {$p[5]}, {$maxUsers}, {$maxProducts}, '{$conn->real_escape_string($features)}', 0)");
}

// Platform owner
$platformPass = seed_hash('Platform@2026');
$conn->query("INSERT INTO platform_users (name, email, username, password, status) VALUES
    ('Kalmoy Platform Admin', 'platform@kalmoypos.com', 'platform', '{$platformPass}', 1)");

function seed_business($conn, $slug, $name, $address, $phone, $email, $owner, $cashier, $planCode, $subStatus, $products)
{
    $conn->query("INSERT INTO businesses (name, slug, phone, email, address, currency, status)
        VALUES ('{$conn->real_escape_string($name)}', '{$slug}', '{$phone}', '{$email}', '{$conn->real_escape_string($address)}', 'KES', 'active')");
    $bid = (int) $conn->insert_id;

    tenant_seed_default_settings($bid, $name);
    tenant_seed_default_catalog($bid, $conn);

    $brandId = (int) $conn->query("SELECT id FROM brands WHERE business_id = {$bid} AND delete_flag = 0 ORDER BY id ASC LIMIT 1")->fetch_assoc()['id'];
    $catId = (int) $conn->query("SELECT id FROM categories WHERE business_id = {$bid} AND delete_flag = 0 ORDER BY id ASC LIMIT 1")->fetch_assoc()['id'];

    $planId = (int) $conn->query("SELECT id FROM subscription_plans WHERE code = '{$planCode}' LIMIT 1")->fetch_assoc()['id'];
    $trialEnd = date('Y-m-d H:i:s', strtotime('+14 days'));
    $periodEnd = date('Y-m-d H:i:s', strtotime('+1 month'));
    if ($subStatus === 'trial') {
        $conn->query("INSERT INTO subscriptions (business_id, plan_id, status, billing_cycle, trial_ends_at, current_period_start, current_period_end)
            VALUES ({$bid}, {$planId}, 'trial', 'trial', '{$trialEnd}', NOW(), '{$trialEnd}')");
        $conn->query("UPDATE businesses SET status = 'trial' WHERE id = {$bid}");
    } else {
        $conn->query("INSERT INTO subscriptions (business_id, plan_id, status, billing_cycle, current_period_start, current_period_end)
            VALUES ({$bid}, {$planId}, 'active', 'monthly', NOW(), '{$periodEnd}')");
    }
    $subId = (int) $conn->insert_id;
    $planRow = $conn->query("SELECT price_monthly FROM subscription_plans WHERE id = {$planId} LIMIT 1")->fetch_assoc();
    $planAmount = $planRow ? (float) $planRow['price_monthly'] : 0;
    if ($subStatus === 'active') {
        $conn->query("INSERT INTO subscription_payments (business_id, subscription_id, plan_id, amount, payment_method, reference, status, notes)
            VALUES ({$bid}, {$subId}, {$planId}, {$planAmount}, 'manual', 'SEED-{$slug}', 'paid', 'Initial seed payment')");
    }

    $ownerPass = seed_hash($owner['pass']);
    $conn->query("INSERT INTO users (business_id, firstname, lastname, username, email, phone, password, type, status)
        VALUES ({$bid}, '{$owner['fn']}', '{$owner['ln']}', '{$owner['user']}', '{$owner['email']}', '{$phone}', '{$ownerPass}', 1, 1)");
    $ownerId = (int) $conn->insert_id;
    $conn->query("UPDATE businesses SET owner_user_id = {$ownerId} WHERE id = {$bid}");

    $cashPass = seed_hash($cashier['pass']);
    $conn->query("INSERT INTO users (business_id, firstname, lastname, username, email, password, type, status)
        VALUES ({$bid}, '{$cashier['fn']}', '{$cashier['ln']}', '{$cashier['user']}', '{$cashier['email']}', '{$cashPass}', 2, 1)");

    $conn->query("INSERT INTO clients (business_id, firstname, lastname, contact, email, status) VALUES
        ({$bid}, 'Walk-in', 'Customer', '0700000000', 'walkin+{$slug}@local', 1)");
    $walkinId = (int) $conn->insert_id;
    $conn->query("INSERT INTO clients (business_id, firstname, lastname, contact, email, status) VALUES
        ({$bid}, 'Abdi', 'Hassan', '0712345678', 'abdi.{$slug}@example.com', 1)");

    foreach ($products as $i => $prod) {
        $barcode = $slug . '-BC' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
        $conn->query("INSERT INTO products (business_id, brand_id, category_id, name, barcode, specs, status)
            VALUES ({$bid}, {$brandId}, {$catId}, '{$conn->real_escape_string($prod['name'])}', '{$barcode}', '<p>{$conn->real_escape_string($prod['name'])}</p>', 1)");
        $pid = (int) $conn->insert_id;
        $conn->query("INSERT INTO inventory (business_id, variant, product_id, quantity, price, cost_price)
            VALUES ({$bid}, 'Default', {$pid}, {$prod['qty']}, {$prod['price']}, {$prod['cost']})");
    }

    $conn->query("INSERT INTO expenses (business_id, expense_date, category, description, amount, payment_method, created_by_name)
        VALUES ({$bid}, CURDATE(), 'Utilities', 'Electricity bill', 4500, 'Cash', '{$owner['fn']} {$owner['ln']}')");

    $conn->query("INSERT INTO notifications (business_id, type, title, message, ref_key, is_read)
        VALUES ({$bid}, 'info', 'Welcome to Kalmoy POS', 'Your business account is ready.', 'welcome_{$slug}', 0)");

    $inv = $conn->query("SELECT id, price FROM inventory WHERE business_id = {$bid} LIMIT 1")->fetch_assoc();
    $client = $conn->query("SELECT id FROM clients WHERE business_id = {$bid} AND firstname != 'Walk-in' LIMIT 1")->fetch_assoc();
    if ($inv && $client) {
        $ref = strtoupper(substr($slug, 0, 3)) . '-0001';
        $qty = 2;
        $price = (float)$inv['price'];
        $total = $qty * $price;
        $conn->query("INSERT INTO orders SET business_id={$bid}, ref_code='{$ref}', client_id=" . (int)$client['id'] . ", payment_method='Cash', amount={$total}, status=1, paid=1");
        $oid = (int)$conn->insert_id;
        $conn->query("INSERT INTO order_list SET business_id={$bid}, order_id={$oid}, inventory_id=" . (int)$inv['id'] . ", quantity={$qty}, price={$price}, total={$total}");
        $conn->query("INSERT INTO sales SET business_id={$bid}, order_id={$oid}, total_amount={$total}");
    }

    return $bid;
}

require_once __DIR__ . '/../inc/tenant.php';

seed_business($conn, 'kalmoy-supermarket', 'Kalmoy Supermarket', 'Tom Mboya Street, Nairobi CBD', '0722111222', 'info@kalmoysuper.co.ke',
    array('fn' => 'Ahmed', 'ln' => 'Mohamed', 'user' => 'ahmed.owner', 'email' => 'ahmed@kalmoysuper.co.ke', 'pass' => 'Owner@2026'),
    array('fn' => 'Fatima', 'ln' => 'Ali', 'user' => 'fatima.cashier', 'email' => 'fatima@kalmoysuper.co.ke', 'pass' => 'Cashier@2026'),
    'business', 'active',
    array(
        array('name' => 'Basmati Rice 5kg', 'qty' => 40, 'price' => 850, 'cost' => 720),
        array('name' => 'Cooking Oil 2L', 'qty' => 30, 'price' => 520, 'cost' => 430),
        array('name' => 'Fresh Milk 1L', 'qty' => 25, 'price' => 120, 'cost' => 95),
    )
);

seed_business($conn, 'eastleigh-pharmacy', 'Eastleigh Pharmacy', '1st Avenue, Eastleigh, Nairobi', '0733444555', 'care@eastleighpharm.co.ke',
    array('fn' => 'Hodan', 'ln' => 'Abdi', 'user' => 'hodan.owner', 'email' => 'hodan@eastleighpharm.co.ke', 'pass' => 'Owner@2026'),
    array('fn' => 'Yusuf', 'ln' => 'Noor', 'user' => 'yusuf.cashier', 'email' => 'yusuf@eastleighpharm.co.ke', 'pass' => 'Cashier@2026'),
    'starter', 'trial',
    array(
        array('name' => 'Paracetamol 500mg', 'qty' => 200, 'price' => 50, 'cost' => 30),
        array('name' => 'Amoxicillin 250mg', 'qty' => 80, 'price' => 180, 'cost' => 120),
        array('name' => 'Bandage Roll', 'qty' => 60, 'price' => 75, 'cost' => 45),
    )
);

echo "Platform login: platform / Platform@2026\n";
echo "Business 1 owner: ahmed.owner / Owner@2026\n";
echo "Business 2 owner: hodan.owner / Owner@2026\n";
