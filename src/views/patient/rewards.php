<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../src/controllers/PatientController.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /patient/login.php');
    exit;
}

$controller = new PatientController();
$availableRewards = $controller->getAvailableRewards();
$redemptions = $controller->getPatientRedemptions($_SESSION['user_id']);
$patient = $controller->getPatientDetails($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/src/assets/css/style.css">
    <style>
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .rewards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .reward-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: white;
        }
        .reward-card h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .points-cost {
            color: #e74c3c;
            font-weight: bold;
            margin: 10px 0;
        }
        .redeem-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        .redeem-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .redemptions-list {
            margin-top: 30px;
        }
        .redemption-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background: white;
        }
        .redemption-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.9em;
        }
        .status-pending { background: #f1c40f; color: black; }
        .status-completed { background: #2ecc71; color: white; }
        .status-cancelled { background: #e74c3c; color: white; }
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }
        .modal-content {
            background: #fff;
            border-radius: 12px;
            padding: 32px 24px 24px 24px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.18);
            max-width: 90vw;
            width: 350px;
            text-align: center;
        }
        .modal-content h2 {
            margin-top: 0;
            color: #3498db;
        }
        .modal-actions {
            margin-top: 24px;
            display: flex;
            gap: 16px;
            justify-content: center;
        }
        .modal-btn {
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.2s;
        }
        .modal-btn.confirm {
            background: #3498db;
            color: #fff;
        }
        .modal-btn.confirm:hover {
            background: #217dbb;
        }
        .modal-btn.cancel {
            background: #eee;
            color: #333;
        }
        .modal-btn.cancel:hover {
            background: #ddd;
        }
        body.modal-open {
            overflow: hidden;
        }
        body.loading-blur > *:not(.loading-overlay):not(script):not(style) {
            filter: blur(2px) grayscale(0.1);
            pointer-events: none;
            user-select: none;
        }
        .loading-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            width: 100vw; height: 100vh;
            background: rgba(255,255,255,0.85);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3000;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @media (max-width: 500px) {
            .modal-content {
                width: 95vw;
                padding: 18px 6vw 18px 6vw;
            }
        }
    </style>
</head>
<body>
    <div id="loading" class="loading-overlay" style="display:none;">
        <div class="spinner"></div>
    </div>

    <div id="notification" style="display:none;position:fixed;bottom:30px;left:50%;transform:translateX(-50%);background:#333;color:#fff;padding:16px 32px;border-radius:8px;z-index:9999;font-size:1.1em;box-shadow:0 2px 8px rgba(0,0,0,0.2);min-width:200px;text-align:center;"></div>

    <!-- Redeem Confirmation Modal -->
    <div id="redeemModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <h2>Confirm Redemption</h2>
            <p>Are you sure you want to redeem this reward?</p>
            <div class="modal-actions">
                <button id="confirmRedeemBtn" class="modal-btn confirm">Yes, Redeem</button>
                <button id="cancelRedeemBtn" class="modal-btn cancel">Cancel</button>
            </div>
        </div>
    </div>

    <div class="container">
        <nav class="navbar">
            <div class="nav-brand"><?php echo SITE_NAME; ?></div>
            <div class="nav-menu">
                <span>Welcome, <?php echo htmlspecialchars($patient['name']); ?></span>
                <a href="/patient/dashboard.php">Dashboard</a>
                <a href="/patient/transactions.php">Transactions</a>
                <a href="/patient/rewards.php" class="active">Rewards</a>
                <a href="/patient/logout.php">Logout</a>
            </div>
        </nav>

        <div class="dashboard-content">
            <div class="card">
                <h2>Available Rewards</h2>
                <div class="rewards-grid">
                    <?php foreach ($availableRewards as $reward): ?>
                        <div class="reward-card">
                            <h3><?php echo htmlspecialchars($reward['name']); ?></h3>
                            <p><?php echo htmlspecialchars($reward['description']); ?></p>
                            <div class="points-cost"><?php echo number_format($reward['points_cost']); ?> Points</div>
                            <button class="redeem-btn" data-reward-id="<?php echo $reward['id']; ?>"
                                    <?php echo $patient['total_points'] < $reward['points_cost'] ? 'disabled' : ''; ?>>
                                <?php echo $patient['total_points'] < $reward['points_cost'] ? 'Not Enough Points' : 'Redeem'; ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card redemptions-list">
                <h2>Your Redemptions</h2>
                <?php if (empty($redemptions)): ?>
                    <p>No rewards redeemed yet.</p>
                <?php else: ?>
                    <?php foreach ($redemptions as $redemption): ?>
                        <div class="redemption-item">
                            <h3><?php echo htmlspecialchars($redemption['reward_name']); ?></h3>
                            <p>Points Spent: <?php echo number_format($redemption['points_spent']); ?></p>
                            <p>Date: <?php echo date('Y-m-d', strtotime($redemption['created_at'])); ?></p>
                            <span class="redemption-status status-<?php echo strtolower($redemption['status']); ?>">
                                <?php echo ucfirst($redemption['status']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Hide loading overlay when page is fully loaded
        window.addEventListener('load', function() {
            document.getElementById('loading').style.display = 'none';
        });

        // Show loading overlay when navigating
        document.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                document.getElementById('loading').style.display = 'flex';
            });
        });

        function showNotification(message, isSuccess = true) {
            var notif = document.getElementById('notification');
            notif.textContent = message;
            notif.style.background = isSuccess ? '#27ae60' : '#e74c3c';
            notif.style.display = 'block';
            notif.style.opacity = 1;
            setTimeout(function() {
                notif.style.transition = 'opacity 0.5s';
                notif.style.opacity = 0;
                setTimeout(function() {
                    notif.style.display = 'none';
                    notif.style.transition = '';
                }, 500);
            }, 3000);
        }

        // Modal logic
        let selectedRewardId = null;
        function openRedeemModal(rewardId) {
            selectedRewardId = rewardId;
            document.getElementById('redeemModal').style.display = 'flex';
            document.body.classList.add('modal-open');
        }
        function closeRedeemModal() {
            selectedRewardId = null;
            document.getElementById('redeemModal').style.display = 'none';
            document.body.classList.remove('modal-open');
        }
        document.getElementById('cancelRedeemBtn').onclick = closeRedeemModal;
        document.getElementById('redeemModal').onclick = function(e) {
            if (e.target === this) closeRedeemModal();
        };
        document.getElementById('confirmRedeemBtn').onclick = function() {
            if (!selectedRewardId) return;
            closeRedeemModal();
            redeemReward(selectedRewardId);
        };

        function redeemReward(rewardId) {
            // Show loading overlay
            document.getElementById('loading').style.display = 'flex';
            document.body.classList.add('loading-blur');

            fetch('/patient/redeem_reward.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ reward_id: rewardId }),
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Reward redeemed successfully!', true);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showNotification(data.error || 'Failed to redeem reward', false);
                }
            })
            .catch(error => {
                showNotification('An error occurred. Please try again.', false);
            })
            .finally(() => {
                document.getElementById('loading').style.display = 'none';
                document.body.classList.remove('loading-blur');
            });
        }

        // Attach modal trigger to redeem buttons
        document.querySelectorAll('.redeem-btn:not([disabled])').forEach(btn => {
            btn.onclick = function() {
                openRedeemModal(this.getAttribute('data-reward-id'));
            };
        });
    </script>
</body>
</html> 