<?php
/** @var mysqli $conn */
$stats = array(
    'businesses' => 0,
    'active_subs' => 0,
    'trial_subs' => 0,
    'expired_subs' => 0,
);
$q = $conn->query("SELECT COUNT(*) AS c FROM businesses");
if ($q) $stats['businesses'] = (int)$q->fetch_assoc()['c'];
$q = $conn->query("SELECT status, COUNT(*) AS c FROM subscriptions GROUP BY status");
if ($q) {
    while ($r = $q->fetch_assoc()) {
        if ($r['status'] === 'active') $stats['active_subs'] = (int)$r['c'];
        if ($r['status'] === 'trial') $stats['trial_subs'] = (int)$r['c'];
        if ($r['status'] === 'expired') $stats['expired_subs'] = (int)$r['c'];
    }
}
$recent = array();
$q = $conn->query("SELECT b.id, b.name, b.slug, b.status, s.status AS sub_status, sp.name AS plan_name
    FROM businesses b
    LEFT JOIN subscriptions s ON s.business_id = b.id AND s.id = (SELECT MAX(id) FROM subscriptions WHERE business_id = b.id)
    LEFT JOIN subscription_plans sp ON sp.id = s.plan_id
    ORDER BY b.id DESC LIMIT 8");
if ($q) {
    while ($r = $q->fetch_assoc()) $recent[] = $r;
}
?>
<h4 class="mb-3">Platform Dashboard</h4>
<div class="row">
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><h5><?php echo $stats['businesses'] ?></h5><p class="mb-0 text-muted">Total Businesses</p></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><h5><?php echo $stats['active_subs'] ?></h5><p class="mb-0 text-muted">Active Subscriptions</p></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><h5><?php echo $stats['trial_subs'] ?></h5><p class="mb-0 text-muted">Trial</p></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><h5><?php echo $stats['expired_subs'] ?></h5><p class="mb-0 text-muted">Expired</p></div></div></div>
</div>
<div class="card mt-3">
    <div class="card-header"><h3 class="card-title">Recent Businesses</h3></div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th>Business</th><th>Status</th><th>Subscription</th><th>Plan</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($recent as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['name']) ?><br><small class="text-muted"><?php echo htmlspecialchars($r['slug']) ?></small></td>
                    <td><span class="badge badge-<?php echo $r['status'] === 'active' ? 'success' : 'secondary' ?>"><?php echo htmlspecialchars($r['status']) ?></span></td>
                    <td><?php echo htmlspecialchars($r['sub_status'] ?? '—') ?></td>
                    <td><?php echo htmlspecialchars($r['plan_name'] ?? '—') ?></td>
                    <td><a href="<?php echo PLATFORM_BASE ?>?page=business_edit&id=<?php echo (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary">Manage</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
