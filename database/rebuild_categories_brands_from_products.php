<?php
/**
 * Rebuild categories and brands from the current products table only.
 *
 * Usage:
 *   php database/rebuild_categories_brands_from_products.php
 *   php database/rebuild_categories_brands_from_products.php --dry-run
 */
require_once __DIR__ . '/../config.php';

$dry_run = in_array('--dry-run', $argv ?? array(), true);

if (!isset($conn) || !$conn) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

const FALLBACK_CATEGORY = 'Uncategorized';
const FALLBACK_BRAND = 'Generic';

function esc_sql($conn, $value)
{
    return $conn->real_escape_string($value);
}

function title_case_words($text)
{
    $text = strtolower(trim($text));
    return preg_replace_callback('/\b[a-z]/', function ($m) {
        return strtoupper($m[0]);
    }, $text);
}

function normalize_product_name($name)
{
    $name = strtoupper(trim($name));
    $name = preg_replace('/\([^)]*\)/', ' ', $name);
    $name = preg_replace('/[^A-Z0-9\s\*X]/', ' ', $name);
    $name = preg_replace('/\s+/', ' ', trim($name));
    return $name;
}

function tokenize_name($name)
{
    $normalized = normalize_product_name($name);
    if ($normalized === '') {
        return array();
    }
    return preg_split('/\s+/', $normalized);
}

function pack_or_size_tokens()
{
    return array(
        'STT', 'CT', 'CTN', 'BX', 'BG', 'CNT', 'PCS', 'SB', 'SB3', 'KSL', 'GX', 'NO',
        'ML', 'LTR', 'L', 'KG', 'G', 'GM', 'GR', 'UNCLEAR',
    );
}

function is_size_or_pack_token($token)
{
    if ($token === '') {
        return true;
    }
    if (preg_match('/^\d+([xX\*]\d+)*([A-Z]{0,3})?$/', $token)) {
        return true;
    }
    if (preg_match('/^\d+[A-Z]{1,4}$/', $token)) {
        return true;
    }
    if (preg_match('/^\d+$/', $token)) {
        return true;
    }
    return in_array($token, pack_or_size_tokens(), true);
}

/**
 * Product-type substrings found inside names (longest first).
 * Category label is always derived from the matched text in the product name.
 */
function type_patterns_in_name()
{
    return array(
        'PEANUT BUTTER',
        'MACARONI',
        'BISCUITS',
        'BISCUIT',
        'SEASONING',
        'CHARCOAL',
        'NUTELLA',
        'COLGATE',
        'SNICKERS',
        'INDOMI',
        'HARPIC',
        'NESCAFE',
        'COFFEE',
        'MASALA',
        'SEGMETTES',
        'RICE',
        'MILK',
        'TUNA',
        'BREAD',
        'SUGAR',
        'WATER',
        'JUICE',
        'SODA',
        'WOSH',
        'WASH',
        'WIPES',
        'HERBAL',
        'DONUT',
        'PILAU',
        'CHICKEN',
        'OIL',
    );
}

function confident_type_labels()
{
    return array(
        'Peanut Butter', 'Macaroni', 'Biscuits', 'Biscuit', 'Seasoning', 'Charcoal',
        'Nutella', 'Colgate', 'Snickers', 'Indomi', 'Harpic', 'Coffee', 'Masala',
        'Segmettes', 'Rice', 'Milk', 'Tuna', 'Bread', 'Sugar', 'Water', 'Juice',
        'Soda', 'Wosh', 'Wash', 'Wipes', 'Herbal', 'Donut', 'Pilau', 'Chicken', 'Oil',
    );
}

function type_token_set()
{
    static $set = null;
    if ($set === null) {
        $set = array();
        foreach (type_patterns_in_name() as $needle) {
            foreach (preg_split('/\s+/', $needle) as $part) {
                $set[$part] = true;
            }
        }
    }
    return $set;
}

function token_is_type_word($token)
{
    return isset(type_token_set()[$token]);
}

function infer_category_from_name($name)
{
    $upper = normalize_product_name($name);
    if ($upper === '' || strpos($upper, 'UNCLEAR') !== false) {
        return array('name' => FALLBACK_CATEGORY, 'confident' => false);
    }

    foreach (type_patterns_in_name() as $needle) {
        if (strpos($upper, $needle) !== false) {
            $label = title_case_words($needle);
            return array(
                'name' => $label,
                'confident' => in_array($label, confident_type_labels(), true),
            );
        }
    }

    return array('name' => FALLBACK_CATEGORY, 'confident' => false);
}

function infer_brand_from_name($name, $category_label)
{
    $raw = strtoupper(trim($name));
    if ($raw === '' || strpos($raw, 'UNCLEAR') !== false) {
        return array('name' => FALLBACK_BRAND, 'confident' => false);
    }

    $tokens = tokenize_name($name);
    if (empty($tokens)) {
        return array('name' => FALLBACK_BRAND, 'confident' => false);
    }

    $category_words = array_filter(explode(' ', strtoupper($category_label)));
    $brand_tokens = array();

    foreach ($tokens as $token) {
        if (is_size_or_pack_token($token)) {
            break;
        }
        if (in_array($token, $category_words, true)) {
            break;
        }
        if (token_is_type_word($token)) {
            break;
        }
        if (strlen($token) < 2) {
            continue;
        }
        $brand_tokens[] = $token;
        if (count($brand_tokens) >= 3) {
            break;
        }
    }

    if (empty($brand_tokens)) {
        return array('name' => FALLBACK_BRAND, 'confident' => false);
    }

    return array(
        'name' => title_case_words(implode(' ', $brand_tokens)),
        'confident' => true,
    );
}

function ensure_lookup_id($conn, $table, $name_column, $name, $dry_run, &$created_count, &$lookup)
{
    $key = strtolower(trim($name));
    if (isset($lookup[$key])) {
        return $lookup[$key];
    }

    if ($dry_run) {
        $created_count++;
        $lookup[$key] = 1000 + count($lookup);
        return $lookup[$key];
    }

    $sql = "INSERT INTO `{$table}` SET `{$name_column}` = '" . esc_sql($conn, $name) . "', status = 1, delete_flag = 0";
    if (!$conn->query($sql)) {
        throw new RuntimeException("Failed to insert into {$table}: {$conn->error}");
    }

    $lookup[$key] = (int)$conn->insert_id;
    $created_count++;
    return $lookup[$key];
}

$report = array(
    'categories_created' => 0,
    'brands_created' => 0,
    'products_assigned' => 0,
    'uncategorized_products' => 0,
    'generic_brand_products' => 0,
);

$products = array();
$q = $conn->query("SELECT id, name FROM products ORDER BY id");
if (!$q) {
    fwrite(STDERR, "Could not read products: {$conn->error}\n");
    exit(1);
}
while ($row = $q->fetch_assoc()) {
    $products[] = $row;
}

if (empty($products)) {
    echo "No products found. Nothing to do.\n";
    exit(0);
}

$assignments = array();
foreach ($products as $product) {
    $category = infer_category_from_name($product['name']);
    if (!$category['confident']) {
        $category = array('name' => FALLBACK_CATEGORY, 'confident' => false);
    }

    $brand = infer_brand_from_name($product['name'], $category['name']);
    if (!$brand['confident']) {
        $brand = array('name' => FALLBACK_BRAND, 'confident' => false);
    }

    $assignments[(int)$product['id']] = array(
        'category' => $category['name'],
        'brand' => $brand['name'],
        'uncategorized' => ($category['name'] === FALLBACK_CATEGORY),
        'generic_brand' => ($brand['name'] === FALLBACK_BRAND),
    );
}

echo ($dry_run ? "DRY RUN — no database changes.\n\n" : "Rebuilding categories and brands from products...\n\n");

if (!$dry_run) {
    $conn->begin_transaction();
}

try {
    $category_lookup = array();
    $brand_lookup = array();
    $used_category_ids = array();
    $used_brand_ids = array();

    foreach ($assignments as $product_id => $data) {
        $category_id = ensure_lookup_id(
            $conn,
            'categories',
            'category',
            $data['category'],
            $dry_run,
            $report['categories_created'],
            $category_lookup
        );
        $brand_id = ensure_lookup_id(
            $conn,
            'brands',
            'name',
            $data['brand'],
            $dry_run,
            $report['brands_created'],
            $brand_lookup
        );

        $used_category_ids[$category_id] = true;
        $used_brand_ids[$brand_id] = true;

        if ($dry_run) {
            $report['products_assigned']++;
            if ($data['uncategorized']) {
                $report['uncategorized_products']++;
            }
            if ($data['generic_brand']) {
                $report['generic_brand_products']++;
            }
            continue;
        }

        $sql = "UPDATE products SET category_id = '" . (int)$category_id . "', brand_id = '" . (int)$brand_id . "' WHERE id = '" . (int)$product_id . "'";
        if (!$conn->query($sql)) {
            throw new RuntimeException("Failed to update product {$product_id}: {$conn->error}");
        }

        $report['products_assigned']++;
        if ($data['uncategorized']) {
            $report['uncategorized_products']++;
        }
        if ($data['generic_brand']) {
            $report['generic_brand_products']++;
        }
    }

    if (!$dry_run) {
        $category_id_list = implode(',', array_map('intval', array_keys($used_category_ids)));
        $brand_id_list = implode(',', array_map('intval', array_keys($used_brand_ids)));

        if (!$conn->query("DELETE FROM categories WHERE id NOT IN ({$category_id_list})")) {
            throw new RuntimeException('Failed to delete old categories: ' . $conn->error);
        }
        if (!$conn->query("DELETE FROM brands WHERE id NOT IN ({$brand_id_list})")) {
            throw new RuntimeException('Failed to delete old brands: ' . $conn->error);
        }

        $max_cat = 0;
        $max_brand = 0;
        $r1 = $conn->query('SELECT COALESCE(MAX(id), 0) AS m FROM categories');
        if ($r1 && ($row = $r1->fetch_assoc())) {
            $max_cat = (int)$row['m'];
        }
        $r2 = $conn->query('SELECT COALESCE(MAX(id), 0) AS m FROM brands');
        if ($r2 && ($row = $r2->fetch_assoc())) {
            $max_brand = (int)$row['m'];
        }
        $conn->query('ALTER TABLE categories AUTO_INCREMENT = ' . ($max_cat + 1));
        $conn->query('ALTER TABLE brands AUTO_INCREMENT = ' . ($max_brand + 1));

        $conn->commit();
    }
} catch (Throwable $e) {
    if (!$dry_run) {
        $conn->rollback();
    }
    fwrite(STDERR, "Error: {$e->getMessage()}\n");
    exit(1);
}

$unique_categories = array();
$unique_brands = array();
foreach ($assignments as $data) {
    $unique_categories[$data['category']] = true;
    $unique_brands[$data['brand']] = true;
}

echo "=== Rebuild Report ===\n";
echo '1. Categories created: ' . count($unique_categories) . "\n";
echo '2. Brands created: ' . count($unique_brands) . "\n";
echo '3. Products assigned: ' . $report['products_assigned'] . "\n";
echo '4. Products placed in Uncategorized: ' . $report['uncategorized_products'] . "\n";
echo '5. Products placed in Generic: ' . $report['generic_brand_products'] . "\n\n";

echo "Categories:\n";
ksort($unique_categories);
foreach (array_keys($unique_categories) as $name) {
    $count = 0;
    foreach ($assignments as $data) {
        if ($data['category'] === $name) {
            $count++;
        }
    }
    echo "  - {$name} ({$count})\n";
}

echo "\nBrands:\n";
ksort($unique_brands);
foreach (array_keys($unique_brands) as $name) {
    $count = 0;
    foreach ($assignments as $data) {
        if ($data['brand'] === $name) {
            $count++;
        }
    }
    echo "  - {$name} ({$count})\n";
}

if ($dry_run) {
    echo "\nRun without --dry-run to apply changes.\n";
} else {
    echo "\nDone. Sales, purchases, and inventory links were preserved (product IDs unchanged).\n";
}
