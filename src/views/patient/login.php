<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../controllers/PatientController.php';

session_start();

$error = '';
$name = '';
$uhid = '';

// Check if we have QR code data
if (isset($_GET['data'])) {
    try {
        error_log("[login.php] Received QR data: " . $_GET['data']);
        $loginData = json_decode(base64_decode($_GET['data']), true);
        if ($loginData && isset($loginData['name']) && isset($loginData['token'])) {
            $name = $loginData['name'];
            // Store token in session for verification
            $_SESSION['qr_token'] = $loginData['token'];
            error_log("[login.php] Successfully decoded QR data for: " . $name);
            
            // Verify token with database
            $controller = new PatientController();
            $patient = $controller->verifyQrToken($loginData['token']);
            if ($patient) {
                $name = $patient['name'];
                $uhid = $patient['UHID'];
                error_log("[login.php] Verified QR token for patient: " . $name);
            } else {
                error_log("[login.php] Invalid QR token");
                $error = "Invalid QR code. Please try again.";
            }
        } else {
            error_log("[login.php] Invalid QR data format");
            $error = "Invalid QR code format";
        }
    } catch (Exception $e) {
        error_log("[login.php] Error decoding QR data: " . $e->getMessage());
        $error = "Invalid QR code data";
    }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new PatientController();
    $result = $controller->login();
    if (isset($result['error'])) {
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../shared/loading.php'; ?>
    <div class="login-header">Patient Login - <?php echo SITE_NAME; ?></div>
    <div class="login-container">
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="/patient/login.php" id="loginForm">
            <div class="form-group">
                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required <?php echo $name ? 'readonly' : ''; ?>>
            </div>
            <div class="form-group">
                <label for="uhid">UHID:</label>
                <input type="text" id="uhid" name="uhid" value="<?php echo htmlspecialchars($uhid); ?>" required placeholder="Enter your UHID" <?php echo $uhid ? 'readonly' : ''; ?>>
            </div>
            <button type="submit" class="button" id="loginBtn">Login</button>
        </form>
    </div>
    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
        <div style="text-align:center; margin-top:20px;">
            <a href="/admin/dashboard.php">&larr; Back to Home</a>
        </div>
    <?php endif; ?>
    <style>
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    </style>
    <script>
    document.getElementById('loginForm').addEventListener('submit', function() {
        showLoading();
    });
    </script>
</body>
</html> 