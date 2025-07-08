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
$transactions = $controller->getPatientTransactions($_SESSION['user_id']);
$rewards = $controller->getAvailableRewards();
$redemptions = $controller->getPatientRedemptions($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../src/assets/css/style.css">
    <style>
        .tier-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 1.3em;
            color: #27ae60 !important;
            background: #eafaf1;
            border: 2px solid #27ae60;
        }
    </style>
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
                    <h3>Current Tier</h3>
                    <span class="tier-badge tier-<?php echo strtolower(str_replace(' ', '-', $patient['tier_name'])); ?>">
                        <?php echo htmlspecialchars($patient['tier_name']); ?>
                    </span>
                </div>
                <div class="points-card">
                    <h3>UHID</h3>
                    <p><?php echo htmlspecialchars($patient['UHID']); ?></p>
                </div>
            </section>

            <section class="available-rewards">
                <h2>Available Rewards</h2>
                <?php if (empty($rewards)): ?>
                    <p>No rewards available at the moment.</p>
                <?php else: ?>
                    <div class="rewards-grid">
                        <?php foreach ($rewards as $reward): ?>
                            <div class="reward-card">
                                <h3><?php echo htmlspecialchars($reward['name']); ?></h3>
                                <p class="description"><?php echo htmlspecialchars($reward['description']); ?></p>
                                <p class="points-cost"><?php echo $reward['points_cost']; ?> points</p>
                                <?php if ($patient['total_points'] >= $reward['points_cost']): ?>
                                    <button class="redeem-btn" data-reward-id="<?php echo $reward['id']; ?>">
                                        Redeem
                                    </button>
                                <?php else: ?>
                                    <button class="redeem-btn disabled" disabled>
                                        Not enough points
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
                                <td>KES <?php echo number_format($transaction['Amount'], 2); ?></td>
                                <td><?php echo $transaction['points_earned']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <section class="recent-redemptions">
                <h2>Recent Redemptions</h2>
                <?php if (empty($redemptions)): ?>
                    <p>No redemptions found.</p>
                <?php else: ?>
                    <table class="redemptions-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reward</th>
                                <th>Points</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($redemptions as $redemption): ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($redemption['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($redemption['reward_name']); ?></td>
                                <td><?php echo $redemption['points_spent']; ?></td>
                                <td>
                                    <span class="status <?php echo $redemption['status']; ?>">
                                        <?php echo ucfirst($redemption['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Custom Modal for Redeem Confirmation -->
    <div id="redeemModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); align-items:center; justify-content:center;">
        <div style="background:#fff; padding:2em; border-radius:8px; max-width:90vw; width:350px; box-shadow:0 2px 10px rgba(0,0,0,0.2); text-align:center; position:relative;">
            <h3 style="margin-bottom:1em;">Confirm Redemption</h3>
            <p id="redeemModalMessage" style="margin-bottom:2em;">Are you sure you want to redeem this reward?</p>
            <div id="redeemModalLoading" style="display:none; margin-bottom:1em;">
                <span style="display:inline-block; width:24px; height:24px; border:3px solid #ccc; border-top:3px solid #28a745; border-radius:50%; animation:spin 1s linear infinite;"></span>
                <span style="margin-left:0.5em;">Processing...</span>
            </div>
            <div style="display:flex; gap:1em; justify-content:center;">
                <button id="modalConfirmBtn" style="padding:0.5em 1.5em; background:#28a745; color:#fff; border:none; border-radius:4px; cursor:pointer;">Yes</button>
                <button id="modalCancelBtn" style="padding:0.5em 1.5em; background:#ccc; color:#333; border:none; border-radius:4px; cursor:pointer;">No</button>
            </div>
        </div>
    </div>
    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <script>
        let selectedRewardId = null;
        const modal = document.getElementById('redeemModal');
        const confirmBtn = document.getElementById('modalConfirmBtn');
        const cancelBtn = document.getElementById('modalCancelBtn');
        const loadingDiv = document.getElementById('redeemModalLoading');
        const modalMessage = document.getElementById('redeemModalMessage');

        document.querySelectorAll('.redeem-btn:not(.disabled)').forEach(button => {
            button.addEventListener('click', function() {
                selectedRewardId = this.dataset.rewardId;
                modal.style.display = 'flex';
                loadingDiv.style.display = 'none';
                modalMessage.textContent = 'Are you sure you want to redeem this reward?';
                confirmBtn.disabled = false;
                cancelBtn.disabled = false;
            });
        });

        confirmBtn.addEventListener('click', function() {
            if (!selectedRewardId) {
                alert('Error: No reward selected.');
                modal.style.display = 'none';
                return;
            }
            loadingDiv.style.display = 'inline-block';
            confirmBtn.disabled = true;
            cancelBtn.disabled = true;
            modalMessage.textContent = '';
            fetch('redeem_reward.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ reward_id: selectedRewardId })
            })
            .then(response => response.json())
            .then(data => {
                loadingDiv.style.display = 'none';
                confirmBtn.disabled = false;
                cancelBtn.disabled = false;
                selectedRewardId = null;
                if (data.success) {
                    // Try to update points, rewards, and redemptions in the DOM if data is available
                    if (data.updated_points !== undefined && data.updated_rewards && data.updated_redemptions) {
                        // Update points
                        const pointsElem = document.querySelector('.points-summary .points');
                        if (pointsElem) pointsElem.textContent = data.updated_points;
                        // Update rewards
                        const rewardsGrid = document.querySelector('.available-rewards .rewards-grid');
                        if (rewardsGrid) rewardsGrid.innerHTML = data.updated_rewards;
                        // Update redemptions
                        const redemptionsTable = document.querySelector('.recent-redemptions tbody');
                        if (redemptionsTable) redemptionsTable.innerHTML = data.updated_redemptions;
                        modal.style.display = 'none';
                    } else {
                        // Fallback: reload page quickly
                        modalMessage.textContent = 'Redemption successful! Reloading...';
                        setTimeout(() => { location.reload(); }, 600);
                    }
                } else {
                    modalMessage.textContent = 'Error redeeming reward: ' + data.error;
                    confirmBtn.disabled = false;
                    cancelBtn.disabled = false;
                }
            })
            .catch(() => {
                loadingDiv.style.display = 'none';
                modalMessage.textContent = 'Network error. Please try again.';
                confirmBtn.disabled = false;
                cancelBtn.disabled = false;
            });
        });

        cancelBtn.addEventListener('click', function() {
            modal.style.display = 'none';
            selectedRewardId = null;
        });

        // Optional: Close modal when clicking outside the modal content
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
                selectedRewardId = null;
            }
        });
    </script>
</body>
</html> 