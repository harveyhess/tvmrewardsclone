<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../src/controllers/AdminController.php';

session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['UHID'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'UHID is required']);
    exit;
}

$controller = new AdminController();
$qrData = $controller->generateQrCode($_GET['UHID']);

if ($qrData) {
    // Create the login URL with encoded data - using relative path
    $loginUrl = '/patient/login.php?data=' . $qrData['login_data'];
    
    // Generate QR code using Google Charts API with error correction
    $qrCodeUrl = 'https://chart.googleapis.com/chart?' . http_build_query([
        'cht' => 'qr',
        'chs' => '300x300',
        'chl' => $loginUrl,
        'choe' => 'UTF-8',
        'chld' => 'L|1' // Error correction level and margin
    ]);

    header('Content-Type: application/json');
    echo json_encode([
        'url' => $loginUrl,
        'qr_code_url' => $qrCodeUrl,
        'UHID' => $qrData['UHID'],
        'name' => $qrData['name'],
        'phone' => $qrData['phone']
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to generate QR code']);
} 