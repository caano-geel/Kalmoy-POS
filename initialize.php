<?php
/**
 * Application bootstrap / environment loader.
 *
 * Configuration priority:
 * 1. config.local.php (or legacy initialize.local.php) on localhost
 * 2. .env / process environment variables (optional)
 * 3. config.production.php (or legacy initialize.production.php) on InfinityFree / production
 * 4. Safe built-in defaults (local XAMPP or InfinityFree host/user/db WITHOUT password)
 *
 * Note: config.php is the main application include (helpers + DB). It requires this file.
 */
if (!defined('base_app')) {
    define('base_app', str_replace('\\', '/', __DIR__) . '/');
}

if (!function_exists('app_set_kenya_timezone')) {
    function app_set_kenya_timezone()
    {
        if (!defined('APP_TIMEZONE')) {
            define('APP_TIMEZONE', 'Africa/Nairobi');
        }
        if (!defined('APP_LOCALE')) {
            define('APP_LOCALE', 'en_KE');
        }
        @ini_set('date.timezone', APP_TIMEZONE);
        date_default_timezone_set(APP_TIMEZONE);
        setlocale(LC_TIME, 'en_KE.UTF-8', 'en_KE', 'sw_KE.UTF-8', 'sw_KE', 'C');
    }
}
app_set_kenya_timezone();

require_once __DIR__ . '/inc/load_env.php';

$__http_host = strtolower($_SERVER['HTTP_HOST'] ?? 'localhost');
$__is_local = in_array($__http_host, array('localhost', '127.0.0.1'), true)
    || strpos($__http_host, 'localhost:') === 0
    || strpos($__http_host, '127.0.0.1:') === 0;

if ($__is_local && !app_is_infinityfree_request()) {
    if (is_file(__DIR__ . '/config.local.php')) {
        require_once __DIR__ . '/config.local.php';
    } elseif (is_file(__DIR__ . '/initialize.local.php')) {
        require_once __DIR__ . '/initialize.local.php';
    }
}

app_load_dotenv();

if (!defined('APP_ENV')) {
    $env_app = app_env('APP_ENV');
    if ($env_app !== '') {
        define('APP_ENV', $env_app);
    } else {
        define('APP_ENV', $__is_local ? 'local' : 'production');
    }
}

if (!defined('DB_SERVER')) {
    if (app_is_infinityfree_request() || (!app_is_local_request() && APP_ENV === 'production')) {
        if (is_file(__DIR__ . '/config.production.php')) {
            require_once __DIR__ . '/config.production.php';
        } elseif (is_file(__DIR__ . '/initialize.production.php')) {
            require_once __DIR__ . '/initialize.production.php';
        } elseif (!app_is_local_request()) {
            app_configure_database_from_env();
        }
    } elseif (!app_is_local_request()) {
        app_configure_database_from_env();
    }
}

if (!defined('DB_SERVER') && !app_is_local_request()) {
    if (is_file(__DIR__ . '/config.production.php')) {
        require_once __DIR__ . '/config.production.php';
    } elseif (is_file(__DIR__ . '/initialize.production.php')) {
        require_once __DIR__ . '/initialize.production.php';
    }
}

if (!defined('DB_SERVER')) {
    if (APP_ENV === 'local' || $__is_local) {
        define('DB_SERVER', 'localhost');
        define('DB_USERNAME', 'root');
        define('DB_PASSWORD', '');
        define('DB_NAME', 'ash_pos_db');
    } else {
        // Public host/user/db names only — password must come from config.production.php
        define('DB_SERVER', 'sql110.infinityfree.com');
        define('DB_USERNAME', 'if0_42375362');
        define('DB_NAME', 'if0_42375362_kalmoyposdb');
        if (!defined('DB_PASSWORD')) {
            define('DB_PASSWORD', '');
        }
    }
}

if (!defined('DB_HOST')) {
    define('DB_HOST', DB_SERVER);
}

if (!defined('DB_PORT')) {
    define('DB_PORT', 3306);
}

if (!defined('DB_SSL')) {
    define('DB_SSL', false);
}

if (!defined('DB_SSL_CA')) {
    define('DB_SSL_CA', '');
}

if (!function_exists('app_resolve_base_url')) {
    function app_resolve_base_url()
    {
        $forced = app_env('APP_URL');
        if ($forced !== '') {
            return rtrim($forced, '/') . '/';
        }
        if (defined('base_url')) {
            return base_url;
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $doc_root = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
        $app_root = str_replace('\\', '/', realpath(__DIR__));
        $path = '/';
        if ($doc_root && $app_root && strpos($app_root, $doc_root) === 0) {
            $rel = substr($app_root, strlen($doc_root));
            $rel = trim(str_replace('\\', '/', $rel), '/');
            $path = $rel === '' ? '/' : '/' . $rel . '/';
        }
        return $scheme . '://' . $host . $path;
    }
}

if (!defined('base_url')) {
    define('base_url', app_resolve_base_url());
}

if (APP_ENV === 'production') {
    @ini_set('display_errors', '0');
    @ini_set('log_errors', '1');
    @error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
} else {
    @ini_set('display_errors', '1');
    @error_reporting(E_ALL);
}
