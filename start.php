<?php
require_once __DIR__ . '/config/config.php';

// Function to check if port is available
function isPortAvailable($port) {
    $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
        return false;
    }
    $result = @socket_bind($socket, '127.0.0.1', $port);
    socket_close($socket);
    return $result;
}

// Function to run installation
function runInstallation() {
    echo "Running installation...\n";
    require_once __DIR__ . '/install.php';
    echo "Installation completed.\n";
}

// Function to start PHP development server
function startServer($port) {
    $command = sprintf(
        'php -S localhost:%d router.php',
        $port
    );
    
    echo "Starting server on port {$port}...\n";
    echo "Access the application at: http://localhost:{$port}\n";
    echo "Press Ctrl+C to stop the server\n";
    
    system($command);
}

// Main execution
if (!isPortAvailable(PORT)) {
    echo "Port " . PORT . " is already in use. Please choose a different port.\n";
    exit(1);
}

// Check if database exists
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST,
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if database exists
    $result = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    if ($result->rowCount() == 0) {
        echo "Database not found. Running installation...\n";
        runInstallation();
    } else {
        echo "Database found. Skipping installation.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Start the server
startServer(PORT);