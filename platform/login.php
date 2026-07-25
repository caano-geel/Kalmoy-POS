<?php
require_once __DIR__ . '/inc/bootstrap.php';
if (platform_logged_in()) {
    platform_redirect('');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Platform Login | Kalmoy Tech Solutions</title>
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo base_url ?>dist/css/adminlte.min.css">
    <style>
        body { background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); min-height: 100vh; }
        .platform-login-card { max-width: 420px; margin: 8vh auto; }
        .platform-brand { color: #fff; text-align: center; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
<div class="platform-brand">
    <h2><i class="fas fa-cloud"></i> Kalmoy Platform</h2>
    <p class="text-light mb-0">Kalmoy Tech Solutions — SaaS Administration</p>
</div>
<div class="platform-login-card">
    <div class="card card-outline card-primary">
        <div class="card-body">
            <p class="login-box-msg">Platform administrator sign in</p>
            <form id="platform-login-frm">
                <div class="input-group mb-3">
                    <input type="text" name="username" class="form-control" placeholder="Username" required autofocus>
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-user"></span></div></div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                    <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
                </div>
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(tenant_csrf_token()) ?>">
                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>
            <p class="text-center mt-3 mb-0"><a href="<?php echo base_url ?>">← Back to website</a></p>
        </div>
    </div>
</div>
<script src="<?php echo base_url ?>plugins/jquery/jquery.min.js"></script>
<script>
$('#platform-login-frm').submit(function(e){
    e.preventDefault();
    $.ajax({
        url: '<?php echo base_url ?>classes/Platform.php?f=login',
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(resp){
            if(resp.status === 'success'){
                location.href = resp.redirect || '<?php echo PLATFORM_BASE ?>';
            } else {
                alert(resp.msg || 'Login failed');
            }
        },
        error: function(){ alert('Login request failed'); }
    });
});
</script>
</body>
</html>
