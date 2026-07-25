<?php
require_once __DIR__ . '/inc/bootstrap.php';
if (isset($_SESSION['platform_user'])) {
    platform_audit_log('logout', 'Platform logout');
    unset($_SESSION['platform_user']);
}
session_regenerate_id(true);
platform_redirect('login.php');
