<?php
// Load environment variables from .env if available
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        if (!defined($key)) {
            define($key, $value);
        }
    }
}

// Parse DATABASE_URL if present and define DB_* constants
if (defined('DATABASE_URL')) {
    $url = getenv('DATABASE_URL') ?: DATABASE_URL;
    $parts = parse_url($url);
    if ($parts !== false) {
        if (!defined('DB_HOST') && isset($parts['host'])) define('DB_HOST', $parts['host']);
        if (!defined('DB_USER') && isset($parts['user'])) define('DB_USER', $parts['user']);
        if (!defined('DB_PASS') && isset($parts['pass'])) define('DB_PASS', $parts['pass']);
        if (!defined('DB_NAME') && isset($parts['path'])) define('DB_NAME', ltrim($parts['path'], '/'));
        if (!defined('DB_PORT') && isset($parts['port'])) define('DB_PORT', $parts['port']);
    }
}

// Fallbacks if not set
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'Veteran');

// Application configuration
define('SITE_NAME', 'Patient Loyalty Rewards System');
define('SITE_URL', defined('SITE_URL') ? SITE_URL : 'http://localhost/Veteran');
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['csv','xlsx']);

// Server configuration
// PORT is loaded from .env

// Session configuration
// SESSION_NAME and SESSION_LIFETIME are loaded from .env

// Points configuration
// DEFAULT_POINTS_RATE is loaded from .env

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}