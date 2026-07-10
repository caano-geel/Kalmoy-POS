<?php require_once('../config.php') ?>
<!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
 <?php require_once('inc/header.php') ?>
<body class="hold-transition login-page">
  <script>
    start_loader()
  </script>
  <style>
    body.login-page{
      background-image: url("<?php echo validate_image($_settings->info('cover')) ?>");
      background-size:cover;
      background-repeat:no-repeat;
      background-position:center;
      min-height:100vh;
    }
    #page-title{
      text-shadow: 0 2px 12px rgba(0,0,0,.35);
      font-size: clamp(1.35rem, 4vw, 2.25rem) !important;
      color: #fff !important;
      background: transparent;
      margin-bottom: 0.5rem !important;
      padding: 0.25rem 1rem !important;
    }
  </style>
  <h1 class="text-center text-white px-4 py-3" id="page-title"><b><?php echo $_settings->info('name') ?></b></h1>
<div class="login-box">
  <div class="text-center mb-3">
    <span class="system-logo-wrapper system-logo-login system-favicon-preview">
      <img src="<?php echo validate_image($_settings->info('logo')) ?>" alt="<?php echo htmlspecialchars($_settings->info('short_name')) ?>">
    </span>
  </div>
  <div class="card ash-login-glass border-0 my-2">
    <div class="card-body">
      <p class="login-box-msg text-center mb-3">Sign in to your account</p>
      <form id="login-frm" action="" method="post">
        <div class="input-group mb-3">
          <input type="text" class="form-control" name="username" id="login-username" autofocus placeholder="Username" autocomplete="username">
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-user"></span></div>
          </div>
        </div>
        <div class="input-group mb-2">
          <input type="password" class="form-control" name="password" id="login-password" placeholder="Password" autocomplete="current-password">
          <div class="input-group-append">
            <button type="button" class="input-group-text btn" id="toggle-password" tabindex="-1" title="Show password"><span class="fas fa-eye"></span></button>
          </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="remember-me">
            <label class="custom-control-label" for="remember-me">Remember me</label>
          </div>
          <a href="javascript:void(0)" class="ash-forgot-link" id="forgot-password-link">Forgot password?</a>
        </div>
        <div class="ash-login-footer">
          <a href="<?php echo base_url ?>" class="ash-home-link" title="Go to website"><i class="fas fa-home"></i></a>
          <button type="submit" class="btn btn-primary btn-login-submit px-4" id="login-submit-btn">
            <span class="btn-label">Sign In</span>
            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="<?php echo base_url ?>dist/js/adminlte.min.js"></script>
<?php echo ash_swal_render_flash(); ?>

<script>
  $(document).ready(function(){
    end_loader();
    var $user = $('#login-username');
    var $pass = $('#login-password');
    var saved = localStorage.getItem('ash_remember_user');
    if(saved){
      $user.val(saved);
      $('#remember-me').prop('checked', true);
    }
    $('#remember-me').change(function(){
      if($(this).is(':checked') && $user.val()){
        localStorage.setItem('ash_remember_user', $user.val());
      }else{
        localStorage.removeItem('ash_remember_user');
      }
    });
    $user.on('blur', function(){
      if($('#remember-me').is(':checked')) localStorage.setItem('ash_remember_user', $(this).val());
    });
    $('#toggle-password').click(function(){
      var $p = $('#login-password');
      var show = $p.attr('type') === 'password';
      $p.attr('type', show ? 'text' : 'password');
      $(this).find('i').toggleClass('fa-eye fa-eye-slash');
      $(this).attr('title', show ? 'Hide password' : 'Show password');
    });
    $('#forgot-password-link').click(function(){
      if(typeof alert_toast === 'function'){
        alert_toast('Please contact your store administrator to reset your password.', 'info');
      } else if(typeof ashAlert === 'function'){
        ashAlert('Please contact your store administrator to reset your password.', 'info', 'Password Reset');
      }
    });
    $('#login-frm').on('submit', function(){
      var $btn = $('#login-submit-btn');
      $btn.prop('disabled', true);
      $btn.find('.btn-label').addClass('d-none');
      $btn.find('.spinner-border').removeClass('d-none');
    });
  });
</script>
</body>
</html>
