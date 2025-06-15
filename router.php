<?php
// Get the request URI
$request_uri = $_SERVER['REQUEST_URI'];

// Remove query string if present
if (($pos = strpos($request_uri, '?')) !== false) {
    $request_uri = substr($request_uri, 0, $pos);
}

// Remove trailing slash
$request_uri = rtrim($request_uri, '/');

// If empty, set to index
if (empty($request_uri)) {
    $request_uri = '/index.php';
}

// Simple routing
if (strpos($request_uri, '/admin') === 0) {
    $file = __DIR__ . $request_uri . '.php';
    if (file_exists($file)) {
        require $file;
        exit;
    }
} elseif (strpos($request_uri, '/patient') === 0) {
    $file = __DIR__ . $request_uri . '.php';
    if (file_exists($file)) {
        require $file;
        exit;
    }
} elseif (strpos($request_uri, '/src/assets') === 0) {
    // Serve static files
    $file = __DIR__ . $request_uri;
    if (file_exists($file)) {
        return false;
    }
}

// If not found, try to serve the file directly
$file = __DIR__ . $request_uri;
if (file_exists($file) && is_file($file)) {
    return false; // Let the PHP server handle it
} else {
    header("HTTP/1.0 404 Not Found");
    echo "404 Not Found";
} 