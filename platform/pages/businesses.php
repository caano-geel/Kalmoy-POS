<?php
/** @var mysqli $conn */
$rows = array();
$q = $conn->query("SELECT b.*, s.status AS sub_status, sp.name AS plan_name,
    (SELECT username FROM users WHERE id = b.owner_user_id LIMIT 1) AS owner_username
    FROM businesses b
    LEFT JOIN subscriptions s ON s.id = (SELECT MAX(id) FROM subscriptions WHERE business_id = b.id)
    LEFT JOIN subscription_plans sp ON sp.id = s.plan_id
    ORDER BY b.id DESC");
if ($q) {
    while ($r = $q->fetch_assoc()) {
        $rows[] = $r;
    }
}
$orphanCount = 0;
foreach ($rows as $r) {
    if (empty($r['owner_username'])) {
        $orphanCount++;
    }
}
?>
<?php if ($orphanCount > 0): ?>
<div class="alert alert-warning">
    <strong><?php echo (int)$orphanCount ?> business(es) have no owner account.</strong>
    This usually happens if the <code>users</code> table was cleared in phpMyAdmin while <code>businesses</code> rows remained.
    Open each business → <em>Delete Business Permanently</em>, or create a fresh business with a new slug and username.
</div>
<?php endif; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Registered Businesses</h4>
    <a href="<?php echo PLATFORM_BASE ?>?page=business_create" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Create Business</a>
</div>
<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>ID</th><th>Business</th><th>Owner</th><th>Status</th><th>Subscription</th><th>Plan</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?php echo (int)$r['id'] ?></td>
                    <td><?php echo htmlspecialchars($r['name']) ?><br><small class="text-muted"><?php echo htmlspecialchars($r['slug']) ?></small></td>
                    <td><?php echo htmlspecialchars($r['owner_username'] ?? '— (no owner account)') ?></td>
                    <td><span class="badge badge-secondary"><?php echo htmlspecialchars($r['status']) ?></span></td>
                    <td><?php echo htmlspecialchars($r['sub_status'] ?? '—') ?></td>
                    <td><?php echo htmlspecialchars($r['plan_name'] ?? '—') ?></td>
                    <td><a class="btn btn-sm btn-outline-primary" href="<?php echo PLATFORM_BASE ?>?page=business_edit&id=<?php echo (int)$r['id'] ?>">Manage</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
