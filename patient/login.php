<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../src/controllers/PatientController.php';

// Configure session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
session_name(SESSION_NAME);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear any existing session data
session_unset();
session_destroy();
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug log function
function debug_log($message) {
    error_log("[Patient Login] " . $message);
}

// Check if already logged in as patient
if (isset($_SESSION['user_id']) && isset($_SESSION['is_patient'])) {
    debug_log("Already logged in as patient, redirecting to dashboard");
    header('Location: dashboard.php');
    exit;
}

// If logged in as admin, redirect to admin dashboard
if (isset($_SESSION['is_admin'])) {
    debug_log("Admin logged in, redirecting to admin dashboard");
    header('Location: ../admin/dashboard.php');
    exit;
}

$error = '';
$controller = new PatientController();

// Handle QR code login
if (isset($_GET['data'])) {
    $loginData = base64_decode($_GET['data']);
    $data = json_decode($loginData, true);
    
    if ($data && isset($data['name']) && isset($data['phone'])) {
        $patient = $controller->validatePatient($data['name'], $data['phone']);
        
        if ($patient) {
            $_SESSION['user_id'] = $patient['id'];
            $_SESSION['patient_name'] = $patient['name'];
            $controller->logLogin($patient['id'], 'qr_code');
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid QR code data';
        }
    } else {
        $error = 'Invalid QR code format';
    }
}

// Handle regular login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    debug_log("Login attempt - POST data: " . print_r($_POST, true));
    
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($name) || empty($phone)) {
        $error = 'Please enter both name and phone number';
        debug_log("Login failed - Empty fields");
    } else {
        debug_log("Validating patient - Name: $name, Phone: $phone");
        $patient = $controller->validatePatient($name, $phone);
        
        if ($patient) {
            debug_log("Patient found - ID: " . $patient['id']);
            
            // Set session variables
            $_SESSION['user_id'] = $patient['id'];
            $_SESSION['patient_name'] = $patient['name'];
            $_SESSION['is_patient'] = true;
            
            // Debug session
            debug_log("Session after setting: " . print_r($_SESSION, true));
            
            // Test if session is working
            if (!isset($_SESSION['user_id'])) {
                $error = 'Session error - Please try again';
                debug_log("Session not set after assignment");
            } else {
                // Log the login
                $loginResult = $controller->logLogin($patient['id'], 'regular');
                debug_log("Login logged: " . ($loginResult ? "Success" : "Failed"));
                
                // Force session write
                session_write_close();
                
                debug_log("Redirecting to dashboard");
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $error = 'Invalid name or phone number';
            debug_log("Login failed - Invalid credentials");
        }
    }
}

// Debug current session
debug_log("Current session state: " . print_r($_SESSION, true));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../src/assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1>Patient Login</h1>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                           placeholder="Enter your full name">
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                           placeholder="Enter your phone number">
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <p class="login-info">
                Don't have an account? Please contact the clinic administrator.
            </p>
        </div>
    </div>
</body>
</html> 