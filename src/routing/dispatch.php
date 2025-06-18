<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/routes.php';

// Get the request path
$requestPath = $_SERVER['REQUEST_URI'];

// Remove query string if present
if (($pos = strpos($requestPath, '?')) !== false) {
    $requestPath = substr($requestPath, 0, $pos);
}

// Create router instance
$router = new Router($routes);

try {
    // Try to match the route
    if ($router->match($requestPath)) {
        // Dispatch to the appropriate controller action
        $router->dispatch();
    } else {
        // No route matched, show 404
        header("HTTP/1.0 404 Not Found");
        require_once __DIR__ . '/../../404.php';
    }
} catch (Exception $e) {
    error_log("Routing error: " . $e->getMessage());
    header("HTTP/1.0 500 Internal Server Error");
    echo "An error occurred. Please try again later.";
} 