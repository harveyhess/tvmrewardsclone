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

// Get the UHID from the request
$uhid = isset($_GET['UHID']) ? trim($_GET['UHID']) : null;
if (!$uhid) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'UHID is required']);
    exit;
}

error_log("[generate_qr.php] Generating QR code for UHID: " . $uhid);

try {
    $controller = new AdminController();
    $qrData = $controller->generateQrCode($uhid);

    if (!$qrData) {
        error_log("[generate_qr.php] Failed to generate QR data for UHID: " . $uhid);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to generate QR code']);
        exit;
    }

    error_log("[generate_qr.php] Successfully generated QR data for patient: " . $qrData['name']);
    
    // Get the site URL from config or construct it
    $siteUrl = SITE_URL ?? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['SERVER_NAME'] . (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443' ? ':' . $_SERVER['SERVER_PORT'] : ''));
    $loginUrl = rtrim($siteUrl, '/') . '/patient/login.php?data=' . $qrData['login_data'];
    
    error_log("[generate_qr.php] Generated login URL: " . $loginUrl);

    // Generate QR code using Google Charts API with error correction
    $qrCodeUrl = 'https://chart.googleapis.com/chart?' . http_build_query([
        'cht' => 'qr',
        'chs' => '300x300',
        'chl' => $loginUrl,
        'choe' => 'UTF-8',
        'chld' => 'L|1' // Error correction level and margin
    ]);

    // If requested as HTML, show the QR code image
    if (isset($_GET['view']) && $_GET['view'] === '1') {
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Patient QR Code</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .container { max-width: 600px; margin: 0 auto; text-align: center; }
                .qr-code { margin: 20px 0; }
                .info { margin: 20px 0; padding: 10px; background: #f5f5f5; border-radius: 5px; }
                .url { word-break: break-all; color: #0066cc; }
            </style>
        </head>
        <body>
            <div class="container">
                <h2>Patient QR Code</h2>
                <div class="info">
                    <p><strong>Patient Name:</strong> <?php echo htmlspecialchars($qrData['name']); ?></p>
                    <p><strong>UHID:</strong> <?php echo htmlspecialchars($qrData['UHID']); ?></p>
                </div>
                <div class="qr-code">
                    <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="QR Code" />
                </div>
                <p>Scan this QR code to login as the patient.</p>
                <p class="url">Login URL: <a href="<?php echo htmlspecialchars($loginUrl); ?>" target="_blank"><?php echo htmlspecialchars($loginUrl); ?></a></p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'url' => $loginUrl,
        'qr_code_url' => $qrCodeUrl,
        'UHID' => $qrData['UHID'],
        'name' => $qrData['name']
    ]);

} catch (Exception $e) {
    error_log("[generate_qr.php] Error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'An error occurred while generating the QR code']);
} 