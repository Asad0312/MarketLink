<?php

declare(strict_types=1);

/**
 * MarketLink application configuration.
 */
const APP_NAME = 'MarketLink';
const APP_TAGLINE = 'Fresh finds. Local roots.';
const APP_VERSION = '1.0.0';
const APP_TIMEZONE = 'Asia/Karachi';

// XAMPP defaults. Override with environment variables if your MySQL setup differs.
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'marketlink');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '');
const UPLOAD_PATH = __DIR__ . '/../uploads';
const UPLOAD_URL = 'uploads';
const MAX_UPLOAD_BYTES = 2 * 1024 * 1024;

date_default_timezone_set(APP_TIMEZONE);

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('marketlink_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Small development aid. Set APP_DEBUG=0 in a production web server.
define('APP_DEBUG', getenv('APP_DEBUG') !== '0');
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}
