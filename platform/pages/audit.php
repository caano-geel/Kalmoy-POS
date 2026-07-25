<?php
/** @var mysqli $conn */
$rows = array();
$q = $conn->query("SELECT * FROM platform_audit_log ORDER BY id DESC LIMIT 100");
if ($q) { while ($r = $q->fetch_assoc()) { $rows[] = $r; } }
?>
<h4 class="mb-3">Platform Audit Log</h4>
<div class="card"><div class="card-body table-responsive p-0">
<table class="table table-sm mb-0"><thead><tr><th>Date</th><th>User</th><th>Action</th><th>Business</th><th>Details</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?php echo htmlspecialchars($r['date_created']) ?></td><td><?php echo htmlspecialchars($r['username'] ?? '') ?></td><td><?php echo htmlspecialchars($r['action']) ?></td><td><?php echo $r['business_id'] ? (int)$r['business_id'] : '—' ?></td><td><?php echo htmlspecialchars($r['details'] ?? '') ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
