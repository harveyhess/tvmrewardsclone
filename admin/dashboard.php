<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../src/controllers/AdminController.php';

session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

$controller = new AdminController();
$stats = $controller->getDashboardStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../src/assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <header>
            <h1>Admin Dashboard</h1>
            <nav>
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="patients.php">Patients</a>
                <a href="tiers.php">Tiers</a>
                <a href="rewards.php">Rewards</a>
                <a href="upload.php">Upload CSV</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <section class="stats">
                <div class="stat-card">
                    <h3>Total Patients</h3>
                    <p><?php echo $stats['total_patients']; ?></p>
                </div>
                
            </section>

            <section class="actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="upload.php" class="button">Upload CSV</a>
                    <a href="patients.php" class="button">View Patients</a>
                    <a href="tiers.php" class="button">Manage Tiers</a>
                    <a href="rewards.php" class="button">Manage Rewards</a>
                   
                   
                </div>
            </section>
        </main>
    </div>
    <script src="../src/assets/js/admin.js"></script>
</body>
</html> 