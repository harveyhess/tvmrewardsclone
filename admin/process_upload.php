<?php
// Start output buffering
ob_start();

// Disable error display and set error reporting to log
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/CsvHandler.php';

// Function to send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    // Clear any previous output
    ob_clean();
    
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Check if admin is logged in
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    sendJsonResponse(['error' => 'Unauthorized'], 401);
}

// Check if file was uploaded
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    sendJsonResponse(['error' => 'No file uploaded or upload error'], 400);
}

// Validate file type
$file = $_FILES['excel_file'];
$fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($fileExt !== 'xlsx') {
    sendJsonResponse(['error' => 'Only Excel (.xlsx) files are allowed'], 400);
}

try {
    // Set execution time limit to 5 minutes
    set_time_limit(300);
    
    // Process the file
    $handler = new CsvHandler();
    $result = $handler->processFile($file);
    
    sendJsonResponse([
        'success' => true,
        'processed' => $result['processed'],
        'skipped' => $result['skipped'],
        'errors' => $result['errors']
    ]);
    
} catch (Exception $e) {
    error_log("Error processing file: " . $e->getMessage());
    sendJsonResponse(['error' => 'Error processing file: ' . $e->getMessage()], 500);
}

// Redirect
header('Location: upload.php');
exit;
?>