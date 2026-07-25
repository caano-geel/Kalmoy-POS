<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Platform') ?> | Kalmoy Platform</title>
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo base_url ?>dist/css/adminlte.min.css">
    <style>
        .platform-sidebar { background: #0f172a !important; }
        .platform-sidebar .nav-link { color: rgba(255,255,255,.85) !important; }
        .platform-sidebar .nav-link.active { background: rgba(59,130,246,.35) !important; }
        .stat-card { border-left: 4px solid #3b82f6; }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom">
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
            <li class="nav-item d-none d-sm-inline-block"><span class="nav-link font-weight-bold">Kalmoy Platform Admin</span></li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item"><span class="nav-link text-muted"><?php echo htmlspecialchars(platform_user('name')) ?></span></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo PLATFORM_BASE ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    <aside class="main-sidebar platform-sidebar elevation-4">
        <div class="brand-link text-white border-bottom-0 px-3 py-3">
            <i class="fas fa-cloud mr-2"></i> <span class="font-weight-light">Kalmoy Tech</span>
        </div>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column">
                    <?php
                    $nav = array(
                        'dashboard' => array('Dashboard', 'tachometer-alt'),
                        'businesses' => array('Businesses', 'store'),
                        'business_create' => array('Create Business', 'plus-circle'),
                        'subscriptions' => array('Subscriptions', 'credit-card'),
                        'payments' => array('Payments', 'receipt'),
                        'audit' => array('Audit Log', 'history'),
                    );
                    $cur = $_GET['page'] ?? 'dashboard';
                    foreach ($nav as $key => $meta):
                    ?>
                    <li class="nav-item">
                        <a href="<?php echo PLATFORM_BASE ?>?page=<?php echo $key ?>" class="nav-link <?php echo ($cur === $key ? 'active' : '') ?>">
                            <i class="nav-icon fas fa-<?php echo $meta[1] ?>"></i>
                            <p><?php echo $meta[0] ?></p>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    </aside>
    <div class="content-wrapper p-3">
        <?php include $contentFile; ?>
    </div>
</div>
<script src="<?php echo base_url ?>plugins/jquery/jquery.min.js"></script>
<script src="<?php echo base_url ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo base_url ?>dist/js/adminlte.min.js"></script>
<script>
function postPlatform(action, data, cb) {
    $.ajax({
        url: '<?php echo base_url ?>classes/Platform.php?f=' + action,
        method: 'POST',
        data: data,
        dataType: 'json',
        success: function(resp) {
            if (typeof cb === 'function') cb(resp);
        },
        error: function(xhr) {
            var msg = 'Request failed. Please try again.';
            if (xhr.responseText) {
                try {
                    var parsed = JSON.parse(xhr.responseText);
                    if (parsed.msg) msg = parsed.msg;
                } catch (e) {
                    msg = xhr.responseText.substring(0, 180);
                }
            }
            alert(msg);
        }
    });
}
</script>
<?php if (!empty($pageScripts)) { echo $pageScripts; } ?>
</body>
</html>
