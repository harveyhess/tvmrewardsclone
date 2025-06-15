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

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug session data
error_log("Dashboard - Session data: " . print_r($_SESSION, true));

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_patient'])) {
    error_log("Dashboard - No user_id or not a patient in session. Redirecting to login.");
    header('Location: login.php');
    exit;
}

if (isset($_SESSION['is_admin'])) {
    error_log("Dashboard - Admin trying to access patient area. Redirecting to admin.");
    header('Location: ../admin/dashboard.php');
    exit;
}

$controller = new PatientController();
$patient = $controller->getPatientDetails($_SESSION['user_id']);

if (!$patient) {
    error_log("Dashboard - Patient not found. Destroying session.");
    session_destroy();
    header('Location: login.php');
    exit;
}

$transactions = $controller->getPatientTransactions($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../src/assets/css/style.css">
</head>
<body>
    <div class="patient-container">
        <header>
            <h1>Welcome, <?php echo htmlspecialchars($patient['name']); ?></h1>
            <nav>
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="transactions.php">Transactions</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <section class="points-summary">
                <div class="points-card">
                    <h3>Total Points</h3>
                    <p class="points"><?php echo $patient['total_points']; ?></p>
                </div>
                <div class="points-card">
                    <h3>Patient ID</h3>
                    <p><?php echo htmlspecialchars($patient['patient_id']); ?></p>
                </div>
            </section>

            <section class="recent-transactions">
                <h2>Recent Transactions</h2>
                <?php if (empty($transactions)): ?>
                    <p>No transactions found.</p>
                <?php else: ?>
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($transaction['transaction_date'])); ?></td>
                                <td>KES <?php echo number_format($transaction['amount_paid'], 2); ?></td>
                                <td><?php echo $transaction['points_earned']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script src="../src/assets/js/patient.js"></script>
</body>
</html> 