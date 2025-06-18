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

// Database configuration
if (!defined('DB_HOST')) define('DB_HOST', 'ballast.proxy.rlwy.net');
if (!defined('DB_PORT')) define('DB_PORT', 24760);
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', 'XoXJQyYaDfuxRNXatApyitBkwVJlemBZ');
if (!defined('DB_NAME')) define('DB_NAME', 'Veteran');

// Application configuration
define('SITE_NAME', 'Patient Loyalty Rewards System');
define('SITE_URL', defined('SITE_URL') ? SITE_URL : 'http://localhost/Veteran');
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['csv','xlsx']);

// Server port configuration
if (!defined('PORT')) {
    $envPort = getenv('PORT');
    define('PORT', $envPort ?: 8000);
}

// Session configuration
if (!defined('SESSION_NAME')) define('SESSION_NAME', 'Veteran_session');
if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', 3600);

// Points system configuration
if (!defined('DEFAULT_POINTS_RATE')) define('DEFAULT_POINTS_RATE', 100);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}
