<?php
require_once __DIR__ . '/inc/bootstrap.php';
platform_require_auth();
$page = isset($_GET['page']) ? trim($_GET['page']) : 'dashboard';
$allowed = array('dashboard', 'businesses', 'business_create', 'business_edit', 'subscriptions', 'payments', 'audit');
if (!in_array($page, $allowed, true)) {
    $page = 'dashboard';
}
$file = __DIR__ . '/pages/' . $page . '.php';
if (!is_file($file)) {
    http_response_code(404);
    echo 'Page not found';
    exit;
}
platform_layout(ucfirst(str_replace('_', ' ', $page)), $file);
