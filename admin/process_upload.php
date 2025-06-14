<?php
// Start output buffering
ob_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/CsvHandler.php';
require_once __DIR__ . '/../src/controllers/PatientController.php';

session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'You must be logged in as an admin to upload files.'
    ];
    header('Location: login.php');
    exit;
}

// Log the request
error_log("Upload request received: " . print_r($_FILES, true));
error_log("POST data: " . print_r($_POST, true));

try {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }

    $file = $_FILES['csv_file'];
    
    // Validate file type
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileType, ['csv', 'xlsx'])) {
        throw new Exception('Only CSV or Excel (.xlsx) files are allowed');
    }

    // Process CSV or Excel file
    $csvHandler = new CsvHandler();
    $result = $csvHandler->processFile($file);

    // Set success message
    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => sprintf(
            'Import completed: %d rows processed, %d rows skipped. %d errors.',
            $result['processed'],
            $result['skipped'],
            count($result['errors'])
        )
    ];

    // Log any errors
    if (!empty($result['errors'])) {
        error_log("CSV/Excel Import Errors: " . print_r($result['errors'], true));
    }

} catch (Exception $e) {
    error_log("Upload error: " . $e->getMessage());
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Error processing file: ' . $e->getMessage()
    ];
}

// Clear any output buffer
ob_end_clean();

// Redirect
header('Location: upload.php');
exit;
?>