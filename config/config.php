<?php
// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'Veteran');

// Application configuration
define('SITE_NAME', 'Patient Loyalty Rewards System');
define('SITE_URL', 'http://localhost/Veteran');
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['csv,xlsx']);

// Server configuration
define('PORT', 2500); // Development server port

// Session configuration
define('SESSION_NAME', 'Veteran_session');
define('SESSION_LIFETIME', 3600); // 1 hour

// Points configuration
define('DEFAULT_POINTS_RATE', 100); // 1 point per 100 KES

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
} 