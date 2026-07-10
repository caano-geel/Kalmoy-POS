<?php
/**
 * Apply users management schema (safe to run multiple times).
 * Usage: php database/migrate_users_management.php
 */
require_once __DIR__ . '/../config.php';

if (!isset($conn) || !$conn) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$columns = array(
    'email' => "ADD COLUMN `email` varchar(250) DEFAULT NULL AFTER `username`",
    'phone' => "ADD COLUMN `phone` varchar(50) DEFAULT NULL AFTER `email`",
    'status' => "ADD COLUMN `status` tinyint(1) NOT NULL DEFAULT 1 AFTER `type`",
    'permissions' => "ADD COLUMN `permissions` text DEFAULT NULL AFTER `status`",
);

foreach ($columns as $col => $ddl) {
    if (db_table_has_column('users', $col)) {
        echo "OK: users.{$col} exists\n";
        continue;
    }
    $sql = "ALTER TABLE `users` {$ddl}";
    if ($conn->query($sql)) {
        echo "Added: users.{$col}\n";
    } else {
        fwrite(STDERR, "Failed users.{$col}: {$conn->error}\n");
        exit(1);
    }
}

$conn->query("UPDATE `users` SET `status` = 1 WHERE `status` IS NULL");
echo "Migration complete.\n";
