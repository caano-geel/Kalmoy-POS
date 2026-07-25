<?php
/**
 * Apply tenant_sql() to admin page queries (run once).
 */
$root = __DIR__ . '/../admin';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$patched = 0;
foreach ($rii as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $c = file_get_contents($path);
    if ($c === false || strpos($c, '$conn->query') === false) {
        continue;
    }
    $orig = $c;
    if (strpos($c, 'tenant_sql') !== false) {
        continue;
    }
    $c = preg_replace_callback(
        '/(\$conn->query\("(?:[^"\\\\]|\\\\.)*"\))/',
        function ($m) {
            $q = $m[1];
            if (strpos($q, 'tenant_sql') !== false || strpos($q, 'SHOW ') !== false || strpos($q, 'system_info') !== false) {
                return $q;
            }
            if (preg_match('/FROM `(products|inventory|orders|clients|brands|categories|sales|expenses|notifications|users|backup_logs|admin_activity_log|order_list|customer_debts|debt_payments)`/i', $q)) {
                return preg_replace('/"\);$/', '" . tenant_sql() . ");', $q);
            }
            if (preg_match('/FROM `(products|inventory|orders|clients|brands|categories|sales|expenses|notifications|users)` ([a-z])/i', $q, $am)) {
                $alias = $am[2];
                return preg_replace('/"\);$/', '" . tenant_sql(\'' . $alias . '\') . ");', $q);
            }
            if (preg_match('/inner join `(products|inventory|orders|clients|brands|categories|sales|expenses|notifications|users)` ([a-z])/i', $q, $am)) {
                $alias = $am[2];
                if (strpos($q, 'tenant_sql') === false && preg_match('/where /i', $q)) {
                    return preg_replace('/"\);$/', '" . tenant_sql(\'' . $alias . '\') . ");', $q);
                }
            }
            return $q;
        },
        $c
    );
    if ($c !== $orig) {
        file_put_contents($path, $c);
        $patched++;
        echo "Patched: {$path}\n";
    }
}
echo "Admin files patched: {$patched}\n";
