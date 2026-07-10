<?php
require dirname(__DIR__) . '/config.php';

$y1 = date('Y-01-01');
$today = date('Y-m-d');
$rev = profit_analytics_sales_total($y1, $today);
$cogs = profit_analytics_cost_total($y1, $today);
$gp = dashboard_profit_total($y1, $today);
$exp = expenses_total($y1, $today);
$net = dashboard_net_profit($y1, $today);

echo "YTD from {$y1} to {$today}\n";
echo "Revenue:            " . number_format($rev, 2) . "\n";
echo "COGS:               " . number_format($cogs, 2) . "\n";
echo "Gross Profit:       " . number_format($gp, 2) . "\n";
echo "Operating Expenses: " . number_format($exp, 2) . "\n";
echo "Net Profit:         " . number_format($net, 2) . "\n";
echo "GP - Expenses:      " . number_format($gp - $exp, 2) . "\n";

$q = $conn->query("SELECT DATE_FORMAT(expense_date, '%Y-%m') as ym, SUM(amount) as total FROM expenses WHERE delete_flag=0 AND expense_date BETWEEN '{$y1}' AND '{$today}' GROUP BY ym ORDER BY ym");
echo "\nExpenses by month (YTD):\n";
while ($r = $q->fetch_assoc()) {
    echo $r['ym'] . ': ' . number_format($r['total'], 2) . "\n";
}

$q2 = $conn->query("SELECT DATE_FORMAT(s.date_created, '%Y-%m') as ym, SUM(s.total_amount) as rev FROM sales s WHERE DATE(s.date_created) BETWEEN '{$y1}' AND '{$today}' GROUP BY ym ORDER BY ym");
echo "\nRevenue by month (YTD):\n";
while ($r = $q2->fetch_assoc()) {
    echo $r['ym'] . ': ' . number_format($r['rev'], 2) . "\n";
}
