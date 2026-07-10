<?php
/**
 * Create customer debt tables.
 * Usage: php database/migrate_customer_debt.php
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/CustomerDebtService.php';

if (!isset($conn) || !$conn) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

if (CustomerDebtService::ensure_schema($conn)) {
    echo "Customer debt tables ready.\n";
    exit(0);
}
fwrite(STDERR, "Migration failed: {$conn->error}\n");
exit(1);
