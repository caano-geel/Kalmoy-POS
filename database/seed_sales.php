<?php
require_once __DIR__ . '/../config.php';
foreach (array('kalmoy-supermarket', 'eastleigh-pharmacy') as $slug) {
    $bid = (int)$conn->query("SELECT id FROM businesses WHERE slug='{$slug}' LIMIT 1")->fetch_assoc()['id'];
    $has = (int)$conn->query("SELECT COUNT(*) AS c FROM sales WHERE business_id={$bid}")->fetch_assoc()['c'];
    if ($has > 0) {
        echo "Sales exist for {$slug}\n";
        continue;
    }
    $inv = $conn->query("SELECT id, price FROM inventory WHERE business_id = {$bid} LIMIT 1")->fetch_assoc();
    $client = $conn->query("SELECT id FROM clients WHERE business_id = {$bid} AND firstname != 'Walk-in' LIMIT 1")->fetch_assoc();
    if (!$inv || !$client) continue;
    $ref = strtoupper(substr($slug, 0, 3)) . '-0001';
    $qty = 2; $price = (float)$inv['price']; $total = $qty * $price;
    $conn->query("INSERT INTO orders SET business_id={$bid}, ref_code='{$ref}', client_id=".(int)$client['id'].", payment_method='Cash', amount={$total}, status=1, paid=1");
    $oid = (int)$conn->insert_id;
    $conn->query("INSERT INTO order_list SET business_id={$bid}, order_id={$oid}, inventory_id=".(int)$inv['id'].", quantity={$qty}, price={$price}, total={$total}");
    $conn->query("INSERT INTO sales SET business_id={$bid}, order_id={$oid}, total_amount={$total}");
    echo "Seeded sale for {$slug}\n";
}
