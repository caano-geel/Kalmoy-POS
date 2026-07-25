<?php
/**
 * Install multi-tenant SaaS schema locally.
 * Usage: php database/install_saas.php
 */
require_once __DIR__ . '/../config.php';

echo "Kalmoy POS SaaS installer\n";
echo "Database: " . DB_NAME . "\n";

$sqlFile = __DIR__ . '/saas_schema.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "Missing saas_schema.sql\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
}
if ($conn->error) {
    fwrite(STDERR, "Schema error: " . $conn->error . "\n");
    exit(1);
}
echo "Schema installed.\n";

require __DIR__ . '/saas_seed.php';
echo "Seed complete.\n";
echo "Done.\n";
