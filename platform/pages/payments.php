<?php
/** @var mysqli $conn */
$businesses = array();
$plans = array();
$q = $conn->query('SELECT id, name FROM businesses ORDER BY name');
if ($q) { while ($r = $q->fetch_assoc()) { $businesses[] = $r; } }
$q = $conn->query('SELECT id, name, price_monthly FROM subscription_plans WHERE status = 1');
if ($q) { while ($r = $q->fetch_assoc()) { $plans[] = $r; } }
$rows = array();
$q = $conn->query("SELECT p.*, b.name AS business_name, sp.name AS plan_name FROM subscription_payments p
    INNER JOIN businesses b ON b.id = p.business_id LEFT JOIN subscription_plans sp ON sp.id = p.plan_id ORDER BY p.id DESC LIMIT 50");
if ($q) { while ($r = $q->fetch_assoc()) { $rows[] = $r; } }
$csrf = tenant_csrf_token();
?>
<h4 class="mb-3">Record Subscription Payment</h4>
<div class="card mb-3"><div class="card-body">
<form id="record-payment-frm">
<input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf) ?>">
<div class="row">
<div class="col-md-4 form-group"><label>Business</label><select class="form-control" name="business_id" required><?php foreach ($businesses as $b): ?><option value="<?php echo (int)$b['id'] ?>"><?php echo htmlspecialchars($b['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4 form-group"><label>Plan</label><select class="form-control" name="plan_id"><?php foreach ($plans as $p): ?><option value="<?php echo (int)$p['id'] ?>"><?php echo htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4 form-group"><label>Amount (Ksh)</label><input class="form-control" name="amount" type="number" step="0.01" required></div>
<div class="col-md-4 form-group"><label>Reference</label><input class="form-control" name="reference" placeholder="M-Pesa / manual ref"></div>
<div class="col-md-4 form-group"><label>Method</label><select class="form-control" name="payment_method"><option>manual</option><option>M-Pesa</option><option>Bank</option></select></div>
<div class="col-md-4 form-group"><label>Notes</label><input class="form-control" name="notes"></div>
</div>
<button class="btn btn-primary" type="submit">Record & Activate</button>
</form>
</div></div>
<h5>Recent Payments</h5>
<div class="card"><div class="card-body table-responsive p-0">
<table class="table table-sm mb-0"><thead><tr><th>Date</th><th>Business</th><th>Plan</th><th>Amount</th><th>Ref</th><th>Status</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?php echo htmlspecialchars($r['payment_date']) ?></td><td><?php echo htmlspecialchars($r['business_name']) ?></td><td><?php echo htmlspecialchars($r['plan_name'] ?? '') ?></td><td>Ksh <?php echo number_format($r['amount'], 2) ?></td><td><?php echo htmlspecialchars($r['reference'] ?? '') ?></td><td><?php echo htmlspecialchars($r['status']) ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?php
$pageScripts = <<<'HTML'
<script>
$(function(){
    $('#record-payment-frm').submit(function(e){
        e.preventDefault();
        postPlatform('record_payment', $(this).serialize(), function(r){
            alert(r.msg);
            if(r.status==='success') location.reload();
        });
    });
});
</script>
HTML;
?>
