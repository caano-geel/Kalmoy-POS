<?php
require __DIR__ . '/../config.php';
$today = date('Y-m-d');
echo "PHP today: {$today} " . date('H:i') . " (tz: " . date_default_timezone_get() . ")\n";

$r = $conn->query("SELECT MIN(DATE(date_created)) as mn, MAX(DATE(date_created)) as mx, COUNT(*) as c FROM orders WHERE status != 4");
$row = $r->fetch_assoc();
echo "Orders range: {$row['mn']} to {$row['mx']} ({$row['c']} orders)\n";

$r2 = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM orders WHERE DATE(date_created) = '{$today}' AND status != 4");
echo "Sales today (orders.date_created): " . $r2->fetch_assoc()['t'] . "\n";

$r3 = $conn->query("SELECT DATE(date_created) as d, COUNT(*) as c, SUM(amount) as amt FROM orders WHERE status != 4 GROUP BY DATE(date_created) ORDER BY d DESC LIMIT 8");
echo "Recent order dates:\n";
while ($x = $r3->fetch_assoc()) {
    echo "  {$x['d']} => {$x['c']} orders, Ksh {$x['amt']}\n";
}

$r4 = $conn->query("SELECT MIN(DATE(date_created)) as mn, MAX(DATE(date_created)) as mx FROM sales");
$row4 = $r4->fetch_assoc();
echo "Sales table range: {$row4['mn']} to {$row4['mx']}\n";

if (expenses_table_enabled()) {
    $r5 = $conn->query("SELECT MIN(expense_date) as mn, MAX(expense_date) as mx FROM expenses");
    $row5 = $r5->fetch_assoc();
    echo "Expenses range: {$row5['mn']} to {$row5['mx']}\n";
    echo "Expenses today: " . dashboard_expenses_today() . "\n";
    echo "Expenses MTD: " . dashboard_expenses_month() . "\n";
}
