<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$redemptions = $db->fetchAll("SELECT r.*, rw.name as reward_name, p.UHID, p.name as patient_name, p.tier_id, t.name as tier_name, p.total_points
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
                <a href="upload.php">Upload Excel</a>
                <a href="redemptions.php" class="active">Redemptions</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>
        <main>
            <section class="redemptions-section">
                <h2>Recent Redemptions</h2>
                <div class="table-responsive">
                <table class="redemptions-table">
                    <thead>
                        <tr>
                            <th>UHID</th>
                            <th>Patient Name</th>
                            <th>Reward</th>
                            <th>Points Spent</th>
                            <th>Current Points</th>
                            <th>Tier</th>
                            <th>Redeemed At</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($redemptions as $r): ?>
                        <tr data-redemption-id="<?php echo $r['id']; ?>">
                            <td><?php echo htmlspecialchars($r['UHID']); ?></td>
                            <td><?php echo htmlspecialchars($r['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($r['reward_name']); ?></td>
                            <td><?php echo htmlspecialchars($r['points_spent']); ?></td>
                            <td><?php echo htmlspecialchars($r['total_points']); ?></td>
                            <td><?php echo htmlspecialchars($r['tier_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                            <td class="redemption-status"><?php echo htmlspecialchars($r['status']); ?></td>
                            <td>
                                <?php if ($r['status'] === 'pending'): ?>
                                    <button class="mark-completed-btn button" style="background:#28a745; color:#fff; border:none; border-radius:4px; padding:6px 16px; cursor:pointer;">Mark as Completed</button>
                                <?php else: ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </section>
        </main>
    </div>
    <style>
    .table-responsive {
        width: 100%;
        overflow-x: auto;
    }
    .redemptions-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        background: #fff;
    }
    .redemptions-table th, .redemptions-table td {
        padding: 12px 8px;
        border-bottom: 1px solid #ddd;
        text-align: left;
        font-size: 1em;
    }
    .redemptions-table th {
        background: #f5f5f5;
        font-weight: bold;
    }
    .redemptions-table tr:hover {
        background: #f9f9f9;
    }
    @media (max-width: 700px) {
        .redemptions-table th, .redemptions-table td {
            font-size: 0.95em;
            padding: 8px 4px;
        }
    }
    </style>
    <script>
    document.querySelectorAll('.mark-completed-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const redemptionId = row.getAttribute('data-redemption-id');
            if (!redemptionId) return;
            this.disabled = true;
            this.textContent = 'Updating...';
            fetch('complete_redemption.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ redemption_id: redemptionId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    row.querySelector('.redemption-status').textContent = 'completed';
                    this.remove();
                } else {
                    alert('Error: ' + (data.error || 'Could not update.'));
                    this.disabled = false;
                    this.textContent = 'Mark as Completed';
                }
            })
            .catch(() => {
                alert('Network error.');
                this.disabled = false;
                this.textContent = 'Mark as Completed';
            });
        });
    });
    </script>
</body>
</html>
