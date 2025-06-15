<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['is_admin'])) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: patient/dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="src/assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Welcome to <?php echo SITE_NAME; ?></h1>
        <div class="login-options">
            <div class="login-option">
                <h2>Admin Login</h2>
                <p>Access the admin dashboard to manage patients and points.</p>
                <a href="admin/login.php" class="button">Admin Login</a>
            </div>
            <div class="login-option">
                <h2>Patient Login</h2>
                <p>Access your patient dashboard to view points and rewards.</p>
                <a href="patient/login.php" class="button">Patient Login</a>
            </div>
        </div>
    </div>
</body>
</html> 