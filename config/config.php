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

/* Database configuration
define('DB_HOST', defined('DB_HOST') ? DB_HOST : '127.0.0.1');
define('DB_USER', defined('DB_USER') ? DB_USER : 'root');
define('DB_PASS', defined('DB_PASS') ? DB_PASS : '');
define('DB_NAME', defined('DB_NAME') ? DB_NAME : 'Veteran'); */

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