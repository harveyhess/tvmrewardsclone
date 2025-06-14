<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$redemptions = $db->fetchAll("SELECT r.*, rw.name as reward_name, p.UHID, p.name as patient_name, p.tier_id, t.name as tier_name
    FROM redemptions r
    JOIN rewards rw ON r.reward_id = rw.id
    JOIN patients p ON r.UHID = p.id
    LEFT JOIN tiers t ON p.tier_id = t.id
    ORDER BY r.created_at DESC LIMIT 100");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redemptions - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../src/assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <header>
            <h1>Redemptions</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="patients.php">Patients</a>
                <a href="upload.php">Upload CSV/Excel</a>
                <a href="redemptions.php" class="active">Redemptions</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>
        <main>
            <section class="redemptions-section">
                <h2>Recent Redemptions</h2>
                <table class="redemptions-table">
                    <thead>
                        <tr>
                            <th>UHID</th>
                            <th>Patient Name</th>
                            <th>Reward</th>
                            <th>Points Spent</th>
                            <th>Tier</th>
                            <th>Redeemed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($redemptions as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['UHID']); ?></td>
                            <td><?php echo htmlspecialchars($r['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($r['reward_name']); ?></td>
                            <td><?php echo htmlspecialchars($r['points_spent']); ?></td>
                            <td><?php echo htmlspecialchars($r['tier_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>
