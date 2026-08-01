<?php
/**
 * Kalmoy POS — production SEO helpers (public + admin).
 */

if (!function_exists('seo_public_base_url')) {
    function seo_public_base_url()
    {
        if (function_exists('app_is_local_host_request') && app_is_local_host_request()) {
            $url = function_exists('app_resolve_base_url') ? app_resolve_base_url() : base_url;
            return rtrim($url, '/') . '/';
        }

        $is_prod = (defined('APP_ENV') && APP_ENV === 'production')
            || (function_exists('app_is_production_request') && app_is_production_request());

        if ($is_prod) {
            return 'https://kalmoypos.com/';
        }

        $url = function_exists('app_resolve_base_url') ? app_resolve_base_url() : base_url;
        return rtrim($url, '/') . '/';
    }
}

if (!function_exists('seo_escape')) {
    function seo_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('seo_brand_name')) {
    function seo_brand_name()
    {
        return 'Kalmoy POS';
    }
}

if (!function_exists('seo_company_name')) {
    function seo_company_name()
    {
        return 'Kalmoy Tech Solutions';
    }
}

if (!function_exists('seo_default_image')) {
    function seo_default_image()
    {
        return seo_public_base_url() . 'assets/img/process.png';
    }
}

if (!function_exists('seo_favicon_url')) {
    function seo_favicon_url()
    {
        return seo_public_base_url() . 'assets/img/Kalmoypos.png';
    }
}

if (!function_exists('seo_build_context')) {
    function seo_build_context($page, array $query = array())
    {
        global $conn;

        $context = array(
            'page' => $page,
            'query' => $query,
            'title' => '',
            'description' => '',
            'robots' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
            'canonical' => '',
            'og_type' => 'website',
            'image' => seo_default_image(),
            'breadcrumbs' => array(),
            'json_ld_extra' => array(),
            'preload_css' => array(),
            'theme_color' => '#0f172a',
        );

        $base = seo_public_base_url();
        $brand = seo_brand_name();
        $company = seo_company_name();

        switch ($page) {
            case 'home':
                $context['title'] = $brand . ' | Best Point of Sale Software in Kenya | ' . $company;
                $context['description'] = 'Kalmoy POS is Kenya\'s professional retail point of sale system by ' . $company . '. Manage sales, inventory, debt, reports, and staff permissions for supermarkets, pharmacies, and wholesalers.';
                $context['canonical'] = $base;
                $context['og_type'] = 'website';
                $context['preload_css'][] = 'assets/css/platform-home.css';
                break;

            case 'platform-privacy':
                $context['title'] = 'Privacy Policy | ' . $brand . ' | ' . $company;
                $context['description'] = 'Privacy Policy for ' . $brand . ' by ' . $company . '. Learn how we handle business data, security, and client privacy for our retail POS platform in Kenya.';
                $context['canonical'] = $base . '?p=platform-privacy';
                $context['breadcrumbs'] = array(
                    array('name' => $brand, 'url' => $base),
                    array('name' => 'Privacy Policy', 'url' => $base . '?p=platform-privacy'),
                );
                $context['preload_css'][] = 'assets/css/platform-home.css';
                break;

            case 'storefront':
                $context['title'] = 'Demo Storefront | ' . $brand . ' Sample Catalog';
                $context['description'] = 'Explore a sample retail storefront powered by ' . $brand . ' — Kenya\'s point of sale software for inventory, checkout, and daily shop operations.';
                $context['canonical'] = $base . '?p=storefront';
                break;

            case 'about':
                $context['title'] = 'About | ' . $brand . ' Demo Store';
                $context['description'] = 'About the ' . $brand . ' demo storefront — sample retail catalog showcasing point of sale and inventory features for Kenyan businesses.';
                $context['canonical'] = $base . '?p=about';
                break;

            case 'view_categories':
                $context['title'] = 'Product Categories | ' . $brand . ' Demo Store';
                $context['description'] = 'Browse product categories in the ' . $brand . ' demo storefront catalog.';
                $context['canonical'] = $base . '?p=view_categories';
                break;

            case 'products':
                $cat_name = '';
                if (!empty($query['c']) && isset($conn) && $conn) {
                    $c = $conn->real_escape_string($query['c']);
                    $q = $conn->query("SELECT category FROM categories WHERE md5(id) = '{$c}' LIMIT 1");
                    if ($q && $q->num_rows > 0) {
                        $cat_name = stripslashes($q->fetch_assoc()['category']);
                    }
                }
                $search = isset($query['search']) ? trim((string) $query['search']) : '';
                if ($search !== '') {
                    $context['title'] = 'Search: ' . $search . ' | ' . $brand . ' Demo Store';
                    $context['description'] = 'Search results for "' . $search . '" in the ' . $brand . ' demo product catalog.';
                    $context['canonical'] = $base . '?p=products&search=' . rawurlencode($search);
                } elseif ($cat_name !== '') {
                    $context['title'] = $cat_name . ' Products | ' . $brand . ' Demo Store';
                    $context['description'] = 'Browse ' . $cat_name . ' products in the ' . $brand . ' demo retail catalog.';
                    $context['canonical'] = $base . '?p=products&c=' . rawurlencode($query['c']);
                } else {
                    $context['title'] = 'All Products | ' . $brand . ' Demo Store';
                    $context['description'] = 'Browse all products in the ' . $brand . ' demo retail catalog.';
                    $context['canonical'] = $base . '?p=products';
                }
                break;

            case 'view_product':
                $product_name = '';
                $product_image = seo_default_image();
                if (!empty($query['id']) && isset($conn) && $conn) {
                    $pid = $conn->real_escape_string($query['id']);
                    $q = $conn->query("SELECT p.id, p.name, p.specs FROM products p WHERE md5(p.id) = '{$pid}' AND p.delete_flag = 0 LIMIT 1");
                    if ($q && $q->num_rows > 0) {
                        $row = $q->fetch_assoc();
                        $product_name = stripslashes($row['name']);
                        $upload_path = base_app . 'uploads/product_' . (int) $row['id'];
                        if (is_dir($upload_path)) {
                            $files = array_values(array_diff(scandir($upload_path), array('.', '..')));
                            if (!empty($files[0])) {
                                $product_image = seo_public_base_url() . 'uploads/product_' . (int) $row['id'] . '/' . $files[0];
                            }
                        }
                        $desc = trim(strip_tags(stripslashes($row['specs'])));
                        if ($desc !== '') {
                            $context['description'] = function_exists('mb_substr') ? mb_substr($desc, 0, 155) : substr($desc, 0, 155);
                        }
                    }
                }
                if ($product_name !== '') {
                    $context['title'] = $product_name . ' | ' . $brand . ' Demo Store';
                    if ($context['description'] === '') {
                        $context['description'] = 'View ' . $product_name . ' in the ' . $brand . ' demo storefront catalog.';
                    }
                    $context['canonical'] = $base . '?p=view_product&id=' . rawurlencode($query['id']);
                    $context['image'] = $product_image;
                    $context['og_type'] = 'product';
                    $context['breadcrumbs'] = array(
                        array('name' => $brand, 'url' => $base),
                        array('name' => 'Demo Store', 'url' => $base . '?p=storefront'),
                        array('name' => $product_name, 'url' => $context['canonical']),
                    );
                } else {
                    $context['title'] = 'Product | ' . $brand . ' Demo Store';
                    $context['description'] = 'Product details in the ' . $brand . ' demo storefront.';
                    $context['canonical'] = $base . '?p=storefront';
                    $context['robots'] = 'noindex, follow';
                }
                break;

            case 'cart':
            case 'checkout':
            case 'my_account':
            case 'edit_account':
                $context['title'] = ucfirst(str_replace('_', ' ', $page)) . ' | ' . $brand;
                $context['description'] = 'Private account area for the ' . $brand . ' demo storefront.';
                $context['robots'] = 'noindex, nofollow';
                $context['canonical'] = $base . '?p=' . rawurlencode($page);
                break;

            case '404':
                $context['title'] = 'Page Not Found | ' . $brand;
                $context['description'] = 'The page you requested could not be found on ' . $brand . '.';
                $context['robots'] = 'noindex, nofollow';
                $context['canonical'] = $base;
                break;

            default:
                $context['title'] = $brand . ' | ' . $company;
                $context['description'] = $brand . ' — professional point of sale software for retail businesses in Kenya by ' . $company . '.';
                $context['canonical'] = $base . '?p=' . rawurlencode($page);
                break;
        }

        if ($context['title'] === '') {
            $context['title'] = $brand . ' | ' . $company;
        }
        if ($context['description'] === '') {
            $context['description'] = $brand . ' — Kenya point of sale software for retail, inventory, and business management.';
        }
        if ($context['canonical'] === '') {
            $context['canonical'] = $base . '?p=' . rawurlencode($page);
        }

        return $context;
    }
}

if (!function_exists('seo_json_ld_organization')) {
    function seo_json_ld_organization()
    {
        $base = seo_public_base_url();
        return array(
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => seo_company_name(),
            'url' => $base,
            'logo' => seo_favicon_url(),
            'email' => 'kalmoypos@gmail.com',
            'description' => seo_brand_name() . ' retail point of sale software for businesses in Kenya.',
            'sameAs' => array(),
        );
    }
}

if (!function_exists('seo_json_ld_software')) {
    function seo_json_ld_software()
    {
        $base = seo_public_base_url();
        return array(
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => seo_brand_name(),
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web Browser',
            'description' => 'Professional retail point of sale (POS) software for supermarkets, pharmacies, wholesalers, and specialty retail in Kenya. Includes inventory, sales, debt management, and reporting.',
            'url' => $base,
            'image' => seo_default_image(),
            'offers' => array(
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'KES',
                'availability' => 'https://schema.org/InStock',
                'description' => 'Contact Kalmoy Tech Solutions for demo and pricing.',
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => seo_company_name(),
            ),
            'keywords' => 'Kalmoy POS, Kalmoy POS Kenya, Kalmoy Tech Solutions POS, Kalmoy Point of Sale, Best POS Kenya, retail POS Kenya',
        );
    }
}

if (!function_exists('seo_json_ld_website')) {
    function seo_json_ld_website()
    {
        $base = seo_public_base_url();
        return array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => seo_brand_name(),
            'alternateName' => array('Kalmoy Point of Sale', 'Kalmoy POS Kenya', 'Kalmoy Tech Solutions POS'),
            'url' => $base,
            'publisher' => array(
                '@type' => 'Organization',
                'name' => seo_company_name(),
            ),
        );
    }
}

if (!function_exists('seo_json_ld_breadcrumbs')) {
    function seo_json_ld_breadcrumbs(array $items)
    {
        if (count($items) < 2) {
            return null;
        }
        $list = array();
        $pos = 1;
        foreach ($items as $item) {
            $list[] = array(
                '@type' => 'ListItem',
                'position' => $pos++,
                'name' => $item['name'],
                'item' => $item['url'],
            );
        }
        return array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list,
        );
    }
}

if (!function_exists('seo_render_json_ld')) {
    function seo_render_json_ld(array $context)
    {
        $blocks = array();
        $page = isset($context['page']) ? $context['page'] : '';

        if ($page === 'home') {
            $blocks[] = seo_json_ld_organization();
            $blocks[] = seo_json_ld_software();
            $blocks[] = seo_json_ld_website();
        } elseif (!empty($context['breadcrumbs'])) {
            $crumb = seo_json_ld_breadcrumbs($context['breadcrumbs']);
            if ($crumb) {
                $blocks[] = $crumb;
            }
        }

        if (!empty($context['json_ld_extra'])) {
            $blocks = array_merge($blocks, $context['json_ld_extra']);
        }

        foreach ($blocks as $block) {
            echo '<script type="application/ld+json">' . json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        }
    }
}

if (!function_exists('seo_render_head_tags')) {
    function seo_render_head_tags(array $context)
    {
        $title = seo_escape($context['title']);
        $description = seo_escape($context['description']);
        $robots = seo_escape($context['robots']);
        $canonical = seo_escape($context['canonical']);
        $image = seo_escape($context['image']);
        $og_type = seo_escape($context['og_type']);
        $site_name = seo_escape(seo_brand_name());
        $theme = seo_escape($context['theme_color']);
        $favicon = seo_escape(seo_favicon_url());
        $base = seo_public_base_url();

        echo '<title>' . $title . '</title>' . "\n";
        echo '<meta name="description" content="' . $description . '">' . "\n";
        echo '<meta name="robots" content="' . $robots . '">' . "\n";
        echo '<meta name="author" content="' . seo_escape(seo_company_name()) . '">' . "\n";
        echo '<meta name="theme-color" content="' . $theme . '">' . "\n";
        echo '<link rel="canonical" href="' . $canonical . '">' . "\n";
        echo '<link rel="icon" type="image/png" href="' . $favicon . '" sizes="32x32">' . "\n";
        echo '<link rel="apple-touch-icon" href="' . $favicon . '">' . "\n";
        echo '<meta property="og:locale" content="en_KE">' . "\n";
        echo '<meta property="og:type" content="' . $og_type . '">' . "\n";
        echo '<meta property="og:title" content="' . $title . '">' . "\n";
        echo '<meta property="og:description" content="' . $description . '">' . "\n";
        echo '<meta property="og:url" content="' . $canonical . '">' . "\n";
        echo '<meta property="og:site_name" content="' . $site_name . '">' . "\n";
        echo '<meta property="og:image" content="' . $image . '">' . "\n";
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . $title . '">' . "\n";
        echo '<meta name="twitter:description" content="' . $description . '">' . "\n";
        echo '<meta name="twitter:image" content="' . $image . '">' . "\n";

        if ($context['page'] === 'home') {
            echo '<meta name="keywords" content="Kalmoy POS, Kalmoy POS Kenya, Kalmoy Tech Solutions POS, Kalmoy Point of Sale, Best POS Kenya, retail POS Kenya, point of sale software Kenya">' . "\n";
            echo '<link rel="preload" as="image" href="' . seo_escape(seo_default_image()) . '" fetchpriority="high">' . "\n";
        }

        if (!empty($context['preload_css'])) {
            foreach ($context['preload_css'] as $css) {
                echo '<link rel="preload" href="' . seo_escape($base . ltrim($css, '/')) . '" as="style">' . "\n";
                echo '<link rel="stylesheet" href="' . seo_escape($base . ltrim($css, '/')) . '">' . "\n";
            }
        }
    }
}

if (!function_exists('seo_render_admin_head_tags')) {
    function seo_render_admin_head_tags($page = 'home')
    {
        $brand = seo_brand_name();
        $label = ucwords(str_replace(array('/', '_', '-'), ' ', (string) $page));
        $title = seo_escape($label . ' | Admin | ' . $brand);
        echo '<title>' . $title . '</title>' . "\n";
        echo '<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">' . "\n";
        echo '<meta name="googlebot" content="noindex, nofollow">' . "\n";
        echo '<meta name="description" content="Private administration area for ' . seo_escape($brand) . '. Not for public indexing.">' . "\n";
        echo '<meta name="theme-color" content="#1e3a5f">' . "\n";
        echo '<link rel="icon" type="image/png" href="' . seo_escape(seo_favicon_url()) . '" sizes="32x32">' . "\n";
        echo '<link rel="apple-touch-icon" href="' . seo_escape(seo_favicon_url()) . '">' . "\n";
    }
}

if (!function_exists('seo_sitemap_urls')) {
    function seo_sitemap_urls()
    {
        global $conn;

        $base = seo_public_base_url();
        $now = date('c');
        $urls = array();

        $add = function ($loc, $priority, $changefreq) use (&$urls, $now) {
            $urls[] = array(
                'loc' => $loc,
                'lastmod' => $now,
                'changefreq' => $changefreq,
                'priority' => $priority,
            );
        };

        $add($base, '1.0', 'weekly');
        $add($base . '?p=platform-privacy', '0.5', 'monthly');
        $add($base . '?p=storefront', '0.7', 'weekly');
        $add($base . '?p=about', '0.4', 'monthly');
        $add($base . '?p=view_categories', '0.5', 'weekly');
        $add($base . '?p=products', '0.6', 'weekly');

        if (isset($conn) && $conn) {
            $cats = $conn->query("SELECT id FROM categories WHERE status = 1");
            if ($cats) {
                while ($row = $cats->fetch_assoc()) {
                    $add($base . '?p=products&c=' . md5($row['id']), '0.5', 'weekly');
                }
            }
            $products = $conn->query("SELECT id, date_created FROM products WHERE delete_flag = 0 ORDER BY id ASC");
            if ($products) {
                while ($row = $products->fetch_assoc()) {
                    $lastmod = !empty($row['date_created']) ? date('c', strtotime($row['date_created'])) : $now;
                    $urls[] = array(
                        'loc' => $base . '?p=view_product&id=' . md5($row['id']),
                        'lastmod' => $lastmod,
                        'changefreq' => 'weekly',
                        'priority' => '0.6',
                    );
                }
            }
        }

        return $urls;
    }
}

if (!function_exists('seo_render_sitemap_xml')) {
    function seo_render_sitemap_xml()
    {
        header('Content-Type: application/xml; charset=UTF-8');
        header('X-Robots-Tag: noindex', true);
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach (seo_sitemap_urls() as $entry) {
            echo "  <url>\n";
            echo '    <loc>' . seo_escape($entry['loc']) . "</loc>\n";
            echo '    <lastmod>' . seo_escape($entry['lastmod']) . "</lastmod>\n";
            echo '    <changefreq>' . seo_escape($entry['changefreq']) . "</changefreq>\n";
            echo '    <priority>' . seo_escape($entry['priority']) . "</priority>\n";
            echo "  </url>\n";
        }
        echo "</urlset>\n";
    }
}

if (!function_exists('seo_render_robots_txt')) {
    function seo_render_robots_txt()
    {
        $base = seo_public_base_url();
        header('Content-Type: text/plain; charset=UTF-8');
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Disallow: /admin/\n";
        echo "Disallow: /classes/\n";
        echo "Disallow: /database/\n";
        echo "Disallow: /vendor/\n";
        echo "Disallow: /uploads/backups/\n";
        echo "Disallow: /tools/\n";
        echo "Disallow: /*?p=cart\n";
        echo "Disallow: /*?p=checkout\n";
        echo "Disallow: /*?p=my_account\n";
        echo "Disallow: /*?p=edit_account\n";
        echo "\nSitemap: " . $base . "sitemap.xml\n";
    }
}
