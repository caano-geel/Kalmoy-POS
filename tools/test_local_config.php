<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/..') ?: 'C:/xampp7/htdocs';
$_SERVER['SERVER_PORT'] = 80;
require __DIR__ . '/../initialize.php';
echo 'APP_ENV=' . APP_ENV . PHP_EOL;
echo 'DB_NAME=' . DB_NAME . PHP_EOL;
echo 'DB_SERVER=' . DB_SERVER . PHP_EOL;
echo 'DB_USER=' . DB_USERNAME . PHP_EOL;
echo 'base_url=' . base_url . PHP_EOL;
$db = @new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, (int) DB_PORT);
if ($db->connect_error) {
    echo 'DB_FAIL=' . $db->connect_error . PHP_EOL;
    exit(1);
}
echo 'DB_OK' . PHP_EOL;
$r = $db->query("SHOW TABLES LIKE 'users'");
echo 'users_table=' . ($r && $r->num_rows ? 'yes' : 'no') . PHP_EOL;
