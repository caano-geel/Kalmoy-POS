<?php
/**
 * One-time InfinityFree deploy check. Upload to htdocs/tools/, open in browser, then delete.
 */
header('Content-Type: text/plain; charset=utf-8');

require_once dirname(__DIR__) . '/initialize.php';

echo "=== InfinityFree deploy check ===\n\n";
echo 'Host: ' . ($_SERVER['HTTP_HOST'] ?? '?') . "\n";
echo 'APP_ENV: ' . (defined('APP_ENV') ? APP_ENV : '?') . "\n";
echo 'Timezone: ' . date_default_timezone_get() . ' | ' . date('Y-m-d H:i:s T') . "\n\n";

echo "DB config (password hidden):\n";
echo '  DB_SERVER: ' . (defined('DB_SERVER') ? DB_SERVER : '(not set)') . "\n";
echo '  DB_NAME: ' . (defined('DB_NAME') ? DB_NAME : '(not set)') . "\n";
echo '  DB_USERNAME: ' . (defined('DB_USERNAME') ? DB_USERNAME : '(not set)') . "\n";
echo '  DB_PASSWORD: ' . (defined('DB_PASSWORD') && DB_PASSWORD !== '' ? '(set, len ' . strlen(DB_PASSWORD) . ')' : '(EMPTY — this will fail)') . "\n";
if (defined('DB_PASSWORD') && defined('DB_USERNAME') && DB_PASSWORD === DB_USERNAME) {
    echo "  WARNING: DB_PASSWORD equals DB_USERNAME — this is wrong on InfinityFree.\n";
    echo "           Use your vPanel login password (infinityfree.com sign-in), not the if0_ username.\n";
}
echo "\n";

echo "Files:\n";
$files = array(
    'config.production.php' => 'Required on InfinityFree (preferred)',
    'initialize.production.php' => 'Legacy fallback if config.production.php missing',
    'config.local.php' => 'DELETE on production if present',
    'initialize.local.php' => 'DELETE on production if present',
    '.env.infinityfree' => 'Optional; prefer config.production.php',
    '.env' => 'DELETE on InfinityFree if it contains DB_* settings',
);
foreach ($files as $file => $note) {
    $path = base_app . $file;
    echo '  ' . $file . ': ' . (is_file($path) ? 'FOUND' : 'missing') . ' — ' . $note . "\n";
}

echo "\nConnection test:\n";
mysqli_report(MYSQLI_REPORT_OFF);
$port = defined('DB_PORT') ? (int) DB_PORT : 3306;
$user = trim(DB_USERNAME);
$pass = trim(DB_PASSWORD);
$name = trim(DB_NAME);
$hosts = array(trim(DB_SERVER));
if (stripos($hosts[0], 'infinityfree.com') !== false) {
    $hosts[] = str_ireplace('infinityfree.com', 'byetcluster.com', $hosts[0]);
}
$connected = false;
$errno = 0;
foreach ($hosts as $host) {
    $test = mysqli_init();
    if (!$test) {
        continue;
    }
    if (@mysqli_real_connect($test, $host, $user, $pass, $name, $port)) {
        echo "  OK — connected to MySQL via {$host}.\n";
        mysqli_close($test);
        $connected = true;
        break;
    }
    $errno = mysqli_connect_errno();
    $error = mysqli_connect_error();
    echo "  {$host} — (#{$errno}) {$error}\n";
}
if (!$connected) {
    echo "\n  Note: phpMyAdmin opening from the control panel does NOT prove this password.\n";
    echo "  It often auto-logs in. PHP must connect with the password in initialize.production.php.\n";
    if ($errno === 1045) {
        echo "  Fix: In File Manager, rewrite initialize.production.php and type the password by hand.\n";
        echo "  If it contains = or special characters, reset vPanel password to letters+numbers only.\n";
    }
}

echo "\nDelete this file after checking.\n";
