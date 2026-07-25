<?php
/** @var mysqli $conn */
$rows = array();
$q = $conn->query("SELECT s.*, b.name AS business_name, sp.name AS plan_name
    FROM subscriptions s
    INNER JOIN businesses b ON b.id = s.business_id
    LEFT JOIN subscription_plans sp ON sp.id = s.plan_id
    ORDER BY s.id DESC LIMIT 100");
if ($q) {
    while ($r = $q->fetch_assoc()) {
        $rows[] = $r;
    }
}
?>
<h4 class="mb-3">Subscriptions</h4>
<div class="card"><div class="card-body table-responsive p-0">
<table class="table table-sm mb-0">
<thead><tr><th>Business</th><th>Plan</th><th>Status</th><th>Cycle</th><th>Trial Ends</th><th>Period End</th></tr></thead>
<tbody>
<?php foreach ($rows as $r):
    $subDisp = platform_subscription_display($r);
?>
<tr>
    <td><?php echo htmlspecialchars($r['business_name']) ?></td>
    <td><?php echo htmlspecialchars($r['plan_name'] ?? '') ?></td>
    <td><?php echo htmlspecialchars($r['status']) ?></td>
    <td><?php echo htmlspecialchars($r['billing_cycle']) ?></td>
    <td><?php echo htmlspecialchars($subDisp['trial_end']) ?></td>
    <td><?php echo htmlspecialchars($subDisp['period_end']) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
