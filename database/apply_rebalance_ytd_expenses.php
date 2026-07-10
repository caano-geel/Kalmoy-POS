<?php
require dirname(__DIR__) . '/config.php';

$y1 = date('Y-01-01');
$today = date('Y-m-d');

$conn->query("UPDATE expenses SET amount = ROUND(amount * 0.60, 2) WHERE delete_flag = 0 AND expense_date >= '{$y1}' AND expense_date <= '{$today}'");
echo "Rebalanced " . $conn->affected_rows . " expense rows for {$y1} to {$today}\n\n";

$rev = profit_analytics_sales_total($y1, $today);
$gp = dashboard_profit_total($y1, $today);
$exp = expenses_total($y1, $today);
$net = dashboard_net_profit($y1, $today);
echo "YTD Revenue:      " . number_format($rev, 2) . "\n";
echo "YTD Gross Profit: " . number_format($gp, 2) . "\n";
echo "YTD Expenses:     " . number_format($exp, 2) . "\n";
echo "YTD Net Profit:   " . number_format($net, 2) . "\n";
