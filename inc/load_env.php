<?php
/**
 * Lightweight .env loader (no Composer dependency).
 * Localhost: skips production env files so XAMPP / config.local.php defaults apply.
 * InfinityFree: may load .env.infinityfree if present (prefer config.production.php).
 * Optional: loads .env when present; process environment variables take precedence.
 */
if (!defined('base_app')) {
    return;
}

if (!function_exists('app_is_local_request')) {
    function app_is_local_request()
    {
        if (defined('APP_ENV') && APP_ENV === 'local') {
            return true;
        }
        $host = strtolower($_SERVER['HTTP_HOST'] ?? 'localhost');
        return in_array($host, array('localhost', '127.0.0.1'), true)
            || strpos($host, 'localhost:') === 0
            || strpos($host, '127.0.0.1:') === 0;
    }
}

if (!function_exists('app_is_infinityfree_request')) {
    function app_is_infinityfree_request()
    {
        $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
        if ($host === '') {
            return false;
        }
        $markers = array(
            'infinityfree',
            'infinityfreeapp.com',
            'epizy.com',
            'rf.gd',
            '42web.io',
            '000webhost',
        );
        foreach ($markers as $marker) {
            if (strpos($host, $marker) !== false) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('app_load_dotenv_file')) {
    function app_load_dotenv_file($file)
    {
        if (!is_file($file) || !is_readable($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if ($key === '') {
                continue;
            }

            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            // Process environment variables always win over file values.
            if (getenv($key) !== false && getenv($key) !== '') {
                continue;
            }

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

if (!function_exists('app_load_dotenv')) {
    function app_load_dotenv()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        if (app_is_infinityfree_request()) {
            app_load_dotenv_file(base_app . '.env.infinityfree');
        }
        app_load_dotenv_file(base_app . '.env');
    }
}

if (!function_exists('app_env')) {
    function app_env($key, $default = '')
    {
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        return $default;
    }
}

if (!function_exists('app_env_int')) {
    function app_env_int($key, $default = 0)
    {
        $val = app_env($key);
        if ($val === '' || $val === null) {
            return (int) $default;
        }
        return (int) $val;
    }
}

if (!function_exists('app_dotenv_has_database')) {
    function app_dotenv_has_database()
    {
        return app_env('DB_HOST') !== '' || app_env('DB_NAME') !== '';
    }
}

if (!function_exists('app_define_db_ssl_from_env')) {
    function app_define_db_ssl_from_env()
    {
        if (defined('DB_SSL')) {
            return;
        }
        $ssl = strtolower(app_env('DB_SSL', ''));
        $enabled = in_array($ssl, array('1', 'true', 'yes', 'on'), true);
        define('DB_SSL', $enabled);

        $ca = app_env('DB_SSL_CA');
        if ($ca !== '' && !is_file($ca) && is_file(base_app . $ca)) {
            $ca = base_app . $ca;
        }
        define('DB_SSL_CA', $ca);
    }
}

if (!function_exists('app_configure_database_from_env')) {
    function app_configure_database_from_env()
    {
        if (!app_dotenv_has_database()) {
            return false;
        }

        if (!defined('DB_SERVER')) {
            define('DB_SERVER', app_env('DB_HOST', 'localhost'));
        }
        if (!defined('DB_USERNAME')) {
            define('DB_USERNAME', app_env('DB_USER', 'root'));
        }
        if (!defined('DB_PASSWORD')) {
            define('DB_PASSWORD', app_env('DB_PASSWORD', ''));
        }
        if (!defined('DB_NAME')) {
            define('DB_NAME', app_env('DB_NAME', 'ash_pos_db'));
        }
        if (!defined('DB_PORT')) {
            define('DB_PORT', app_env_int('DB_PORT', 3306));
        }

        app_define_db_ssl_from_env();
        return true;
    }
}
