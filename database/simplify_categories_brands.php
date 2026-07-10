<?php
/**
 * Simplify categories and brands by merging similar entries.
 *
 * Usage:
 *   php database/simplify_categories_brands.php
 *   php database/simplify_categories_brands.php --dry-run
 */
require_once __DIR__ . '/../config.php';

$dry_run = in_array('--dry-run', $argv ?? array(), true);

if (!isset($conn) || !$conn) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

const FALLBACK_CATEGORY = 'General Merchandise';
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

function normalize_name($name)
{
    $name = strtoupper(trim($name));
    $name = preg_replace('/\([^)]*\)/', ' ', $name);
    $name = preg_replace('/[^A-Z0-9\s]/', ' ', $name);
    $name = preg_replace('/\s+/', ' ', trim($name));
    return $name;
}

function tokenize_name($name)
{
    $normalized = normalize_name($name);
    if ($normalized === '') {
        return array();
    }
    return preg_split('/\s+/', $normalized);
}

function is_pack_or_size_token($token)
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
    $stops = array('STT', 'CT', 'CTN', 'BX', 'BG', 'CNT', 'PCS', 'SB', 'SB3', 'KSL', 'GX', 'NO', 'ML', 'LTR', 'KG', 'G', 'GM', 'GR', 'UNCLEAR', 'CHO');
    return in_array($token, $stops, true);
}

/**
 * Map product name to one simplified category (target 8-15 groups).
 */
function simplified_category_for_product($name)
{
    $u = normalize_name($name);

    if ($u === '') {
        return FALLBACK_CATEGORY;
    }

    if (preg_match('/\bBLUE\s+BAND\b/', $u)) {
        return 'Margarine & Spreads';
    }
    if (preg_match('/\b(WATER|JUICE|SODA|FLAVOURED|FANTA|APPLE|NESCAFE|COFFEE|TONE|BRAVA|LIMATE|DELA|CRYSTAL|STAR)\b/', $u)) {
        return 'Beverages';
    }
    if (preg_match('/\b(MILK|BROOKSIDE)\b/', $u)) {
        return 'Dairy & Milk';
    }
    if (preg_match('/\b(OIL|SALIT|POPCO)\b/', $u) && preg_match('/\b(OIL|SALIT)\b/', $u)) {
        return 'Cooking Oil';
    }
    if (preg_match('/\b(RICE|MACARONI|PASTA|TUNA|BREAD|SUGAR|INDOMI|NOODLE|AMEFI|EAFRICA)\b/', $u)) {
        return 'Food & Groceries';
    }
    if (preg_match('/\b(BISCUIT|BISCUITS|SNICKERS|NUTELLA|PEANUT|DONUT|MINT|CHILI|CHOCOLATE|KRACHLES|MARIE|SOOPER|HAPPY|TAK|TROPICAL|MABUYUZ|MILANO)\b/', $u)) {
        return 'Biscuits & Snacks';
    }
    if (preg_match('/\b(MASALA|PILAU|SEASONING|SEGMETTES|CHICKEN\s+MASALA)\b/', $u)) {
        return 'Spices & Seasoning';
    }
    if (preg_match('/\b(COLGATE|WIPES|SOFTCARE|SOFT\s+CARE|ALWAYS|TISHY|BISO|OMAR|FINOLESA|HERBAL|JUNIA|MAXI|WOSH|WASH)\b/', $u)) {
        return 'Personal Care';
    }
    if (preg_match('/\b(HARPIC|CHARCOAL|WIFTOS|CLEANER)\b/', $u)) {
        return 'Household Cleaning';
    }

    return FALLBACK_CATEGORY;
}

/**
 * Extract and merge brand from product name (ignore sizes/variants).
 */
function simplified_brand_for_product($name)
{
    $u = normalize_name($name);
    if ($u === '') {
        return FALLBACK_BRAND;
    }

    $merge_rules = array(
        '/^BLUE\s+BAND\b/' => 'Blue Band',
        '/^BROOKSIDE\b/' => 'Brookside',
        '/^(POPCO|SALIT)\b/' => 'Popco',
        '/^SOFTCARE\b|^SOFT\s+CARE\b|^ALWAYS\s+SOFTCARE\b|^BABY\s+WIPES\s+SOFTCARE\b/' => 'Softcare',
        '/^GX\s+HARPIC\b|^HARPIC\b/' => 'Harpic',
        '/^COLGATE\b/' => 'Colgate',
        '/^NESCAFE\b/' => 'Nescafe',
        '/^NUTELLA\b/' => 'Nutella',
        '/^SNICKERS\b/' => 'Snickers',
        '/^INDOMI\b/' => 'Indomi',
        '/^DELA\b/' => 'Dela',
        '/^BRAVA\b/' => 'Brava',
        '/^LIMATE\b/' => 'Limate',
        '/^RUDA\b/' => 'Ruda',
        '/^FANTA\b/' => 'Fanta',
        '/^CRYSTAL\b/' => 'Crystal',
        '/^STAR\b/' => 'Star',
        '/^EAFRICA\b/' => 'Eafrica',
        '/^AMEFI\b/' => 'Amefi',
        '/^QUEEN\b/' => 'Queen',
        '/^TANA\b/' => 'Tana',
        '/^OMAR\b/' => 'Omar',
        '/^WHITE\b/' => 'White',
        '/^KRACHLES\b/' => 'Krachles',
        '/^CIFA\b/' => 'Cifa',
        '/^HAPPY\b/' => 'Happy',
        '/^MARIE\b/' => 'Marie',
        '/^MILANO\b/' => 'Milano',
        '/^SOOPER\b/' => 'Sooper',
        '/^TAK\b/' => 'Tak',
        '/^TROPICAL\b/' => 'Tropical',
        '/^ABU\s+WAAL\b/' => 'Abu Waal',
        '/^BELA\b/' => 'Bela',
        '/^MABUYUZ\b/' => 'Mabuyuz',
        '/^WIFTOS\b/' => 'Wiftos',
        '/^FINOLESA\b/' => 'Finolesa',
        '/^BROWN\b/' => 'Brown',
    );

    foreach ($merge_rules as $pattern => $brand) {
        if (preg_match($pattern, $u)) {
            return $brand;
        }
    }

    $tokens = tokenize_name($name);
    $brand_tokens = array();
    foreach ($tokens as $token) {
        if (is_pack_or_size_token($token)) {
            break;
        }
        if (strlen($token) < 2) {
            continue;
        }
        $brand_tokens[] = $token;
        if (count($brand_tokens) >= 2) {
            break;
        }
    }

    if (empty($brand_tokens)) {
        return FALLBACK_BRAND;
    }

    return title_case_words(implode(' ', $brand_tokens));
}

/**
 * Merge descriptive or weak extractions into Generic.
 */
function finalize_brand_name($brand, $product_name)
{
    $generic_brands = array(
        'Juice', 'Milk', 'Water', 'Soda Plastic', 'Peanut Butter', 'Charcoal Bag',
        'Chicken Masala', 'Pilau Masala', 'Seasoning', 'Brown',
    );
    if (in_array($brand, $generic_brands, true)) {
        return FALLBACK_BRAND;
    }

    $u = normalize_name($product_name);
    if (strpos($u, 'UNCLEAR') !== false && $brand !== 'Dela' && $brand !== 'White') {
        return FALLBACK_BRAND;
    }

    return $brand;
}

function ensure_lookup_id($conn, $table, $name_column, $name, $dry_run, &$lookup)
{
    $key = strtolower(trim($name));
    if (isset($lookup[$key])) {
        return $lookup[$key];
    }

    if ($dry_run) {
        $lookup[$key] = 1000 + count($lookup);
        return $lookup[$key];
    }

    $sql = "INSERT INTO `{$table}` SET `{$name_column}` = '" . esc_sql($conn, $name) . "', status = 1, delete_flag = 0";
    if (!$conn->query($sql)) {
        throw new RuntimeException("Failed to insert into {$table}: {$conn->error}");
    }

    $lookup[$key] = (int)$conn->insert_id;
    return $lookup[$key];
}

$products = array();
$q = $conn->query("SELECT id, name FROM products WHERE delete_flag = 0 ORDER BY id");
if (!$q) {
    fwrite(STDERR, "Could not read products: {$conn->error}\n");
    exit(1);
}
while ($row = $q->fetch_assoc()) {
    $products[] = $row;
}

if (empty($products)) {
    echo "No active products found.\n";
    exit(0);
}

$assignments = array();
foreach ($products as $product) {
    $brand = simplified_brand_for_product($product['name']);
    $assignments[(int)$product['id']] = array(
        'name' => $product['name'],
        'category' => simplified_category_for_product($product['name']),
        'brand' => finalize_brand_name($brand, $product['name']),
    );
}

// Merge single-product obscure brands into Generic when too many brands remain.
$brand_usage = array();
foreach ($assignments as $data) {
    $brand_usage[$data['brand']] = ($brand_usage[$data['brand']] ?? 0) + 1;
}
$major_brands = array(
    'Blue Band', 'Brookside', 'Popco', 'Softcare', 'Colgate', 'Harpic', 'Nescafe', 'Nutella',
    'Snickers', 'Indomi', 'Fanta', 'Omar', 'White', 'Eafrica', 'Amefi', 'Queen', 'Tana', 'Dela',
    'Generic',
);
foreach ($assignments as $product_id => $data) {
    if ($brand_usage[$data['brand']] === 1 && !in_array($data['brand'], $major_brands, true)) {
        $assignments[$product_id]['brand'] = FALLBACK_BRAND;
    }
}
$brand_usage = array();
foreach ($assignments as $data) {
    $brand_usage[$data['brand']] = ($brand_usage[$data['brand']] ?? 0) + 1;
}

echo ($dry_run ? "DRY RUN — no database changes.\n\n" : "Simplifying categories and brands...\n\n");

if (!$dry_run) {
    $conn->begin_transaction();
}

try {
    $category_lookup = array();
    $brand_lookup = array();
    $used_category_ids = array();
    $used_brand_ids = array();

    foreach ($assignments as $product_id => $data) {
        $category_id = ensure_lookup_id($conn, 'categories', 'category', $data['category'], $dry_run, $category_lookup);
        $brand_id = ensure_lookup_id($conn, 'brands', 'name', $data['brand'], $dry_run, $brand_lookup);

        $used_category_ids[$category_id] = true;
        $used_brand_ids[$brand_id] = true;

        if (!$dry_run) {
            $sql = "UPDATE products SET category_id = '" . (int)$category_id . "', brand_id = '" . (int)$brand_id . "' WHERE id = '" . (int)$product_id . "'";
            if (!$conn->query($sql)) {
                throw new RuntimeException("Failed to update product {$product_id}: {$conn->error}");
            }
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

        $max_cat = (int)$conn->query('SELECT COALESCE(MAX(id), 0) AS m FROM categories')->fetch_assoc()['m'];
        $max_brand = (int)$conn->query('SELECT COALESCE(MAX(id), 0) AS m FROM brands')->fetch_assoc()['m'];
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

$category_counts = array();
$brand_counts = array();
foreach ($assignments as $data) {
    $category_counts[$data['category']] = ($category_counts[$data['category']] ?? 0) + 1;
    $brand_counts[$data['brand']] = ($brand_counts[$data['brand']] ?? 0) + 1;
}

ksort($category_counts);
ksort($brand_counts);

echo "=== Simplified Catalog ===\n";
echo 'Categories: ' . count($category_counts) . "\n";
echo 'Brands: ' . count($brand_counts) . "\n";
echo 'Products updated: ' . count($assignments) . "\n\n";

echo "Categories (product count):\n";
foreach ($category_counts as $name => $count) {
    echo "  - {$name} ({$count})\n";
}

echo "\nBrands (product count):\n";
foreach ($brand_counts as $name => $count) {
    echo "  - {$name} ({$count})\n";
}

if ($dry_run) {
    echo "\nRun without --dry-run to apply changes.\n";
} else {
    echo "\nDone. Unused categories and brands were removed. Product counts will reflect on the Categories and Brands pages.\n";
}
