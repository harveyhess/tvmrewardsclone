<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/controllers/AdminController.php';
require_once __DIR__ . '/../src/controllers/RewardController.php';

session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

error_log("Loading rewards page");
try {
    $controller = new AdminController();
    error_log("AdminController instantiated");
    $rewards = $controller->getRewards();
    error_log("Rewards fetched: " . print_r($rewards, true));
} catch (Exception $e) {
    error_log("Error loading rewards: " . $e->getMessage());
    $rewards = [];
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Error loading rewards'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reward Management - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../src/assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <header>
            <h1>Reward Management</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="patients.php">Patients</a>
                <a href="tiers.php">Tiers</a>
                <a href="rewards.php" class="active">Rewards</a>
                <a href="upload.php">Upload CSV</a>
                <a href="transactions.php">Transactions</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="flash-message <?php echo $_SESSION['flash_message']['type']; ?>">
                    <?php echo $_SESSION['flash_message']['message']; ?>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>

            <section class="rewards-section">
                <div class="section-header">
                    <h2>Available Rewards</h2>
                    <button class="button" onclick="showAddRewardModal()">Add New Reward</button>
                </div>

                <?php if (empty($rewards)): ?>
                    <p>No rewards defined yet.</p>
                <?php else: ?>
                    <table class="rewards-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Points Cost</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rewards as $reward): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reward['name']); ?></td>
                                    <td><?php echo htmlspecialchars($reward['description']); ?></td>
                                    <td><?php echo $reward['points_cost']; ?></td>
                                    <td>
                                        <span class="status <?php echo $reward['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $reward['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="edit-reward" data-id="<?php echo $reward['id']; ?>">Edit</button>
                                        <button class="delete-reward" data-id="<?php echo $reward['id']; ?>">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Add/Edit Reward Modal -->
    <div id="rewardModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle">Add New Reward</h2>
            <form id="rewardForm">
                <input type="hidden" id="rewardId" name="reward_id">
                <div class="form-group">
                    <label for="rewardName">Reward Name:</label>
                    <input type="text" id="rewardName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="pointsCost">Points Cost:</label>
                    <input type="number" id="pointsCost" name="points_cost" required min="1">
                </div>
                <div class="form-group">
                    <label for="isActive">Status:</label>
                    <select id="isActive" name="is_active">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <button type="submit" class="button">Save Reward</button>
            </form>
        </div>
    </div>

    <script src="../src/assets/js/admin.js"></script>
</body>
</html> 