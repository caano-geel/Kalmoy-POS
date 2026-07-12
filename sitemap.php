<?php
require_once __DIR__ . '/initialize.php';
require_once __DIR__ . '/classes/DBConnection.php';

$db = new DBConnection();
$conn = $db->conn;

require_once __DIR__ . '/inc/seo.php';

while (ob_get_level() > 0) {
    ob_end_clean();
}

if (!headers_sent()) {
    header('Content-Type: application/xml; charset=UTF-8');
    header('X-Robots-Tag: noindex', true);
}

seo_render_sitemap_xml();
exit;
