<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../src/controllers/CsvController.php';

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$csvController = new CsvController();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'upload':
                    if (isset($_FILES['csv_file'])) {
                        $result = $csvController->uploadCsv($_FILES['csv_file']);
                        $_SESSION['success'] = $result['message'];
                    }
                    break;

                case 'add_link':
                    if (isset($_POST['url'])) {
                        $result = $csvController->addCsvLink($_POST['url']);
                        $_SESSION['success'] = $result['message'];
                    }
                    break;

                case 'update_status':
                    if (isset($_POST['id']) && isset($_POST['status'])) {
                        $result = $csvController->updateCsvLinkStatus($_POST['id'], $_POST['status']);
                        $_SESSION['success'] = $result['message'];
                    }
                    break;

                case 'delete':
                    if (isset($_POST['id'])) {
                        $result = $csvController->deleteCsvLink($_POST['id']);
                        $_SESSION['success'] = $result['message'];
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }

    header('Location: transactions.php');
    exit;
}

// Get CSV links
$csvLinks = $csvController->getCsvLinks();

// Get recent transaction syncs
$recentSyncs = $db->fetchAll(
    "SELECT * FROM transaction_sync ORDER BY last_sync_time DESC LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .nav-link {
            color: #333;
        }
        .nav-link:hover {
            background-color: #e9ecef;
        }
        .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <h4>Admin Panel</h4>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="patients.php">
                                <i class="bi bi-people"></i> Patients
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="transactions.php">
                                <i class="bi bi-cash-stack"></i> Transactions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="tiers.php">
                                <i class="bi bi-trophy"></i> Tiers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="rewards.php">
                                <i class="bi bi-gift"></i> Rewards
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="bi bi-gear"></i> Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <h2>Transaction Management</h2>
                <hr>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <!-- CSV Upload Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Upload CSV File</h5>
                    </div>
                    <div class="card-body">
                        <form action="transactions.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload">
                            <div class="mb-3">
                                <label for="csv_file" class="form-label">Select CSV File</label>
                                <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                                <div class="form-text">
                                    The CSV file should have the following columns: UHID, amount, dateofvisit
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Upload</button>
                        </form>
                    </div>
                </div>

                <!-- CSV Links Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Remote CSV Links</h5>
                    </div>
                    <div class="card-body">
                        <form action="transactions.php" method="post" class="mb-4">
                            <input type="hidden" name="action" value="add_link">
                            <div class="input-group">
                                <input type="url" class="form-control" name="url" placeholder="Enter CSV URL" required>
                                <button type="submit" class="btn btn-primary">Add Link</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>URL</th>
                                        <th>Status</th>
                                        <th>Last Fetched</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($csvLinks as $link): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($link['url']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $link['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($link['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo $link['last_fetched'] ? date('Y-m-d H:i:s', strtotime($link['last_fetched'])) : 'Never'; ?>
                                            </td>
                                            <td>
                                                <form action="transactions.php" method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="id" value="<?php echo $link['id']; ?>">
                                                    <input type="hidden" name="status" value="<?php echo $link['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                    <button type="submit" class="btn btn-sm btn-<?php echo $link['status'] === 'active' ? 'warning' : 'success'; ?>">
                                                        <?php echo $link['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                                    </button>
                                                </form>
                                                <form action="transactions.php" method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $link['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this link?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Syncs Section -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Syncs</h5>
                        <button id="queryTransactions" class="btn btn-primary">Query Transactions</button>
                    </div>
                    <div class="card-body">
                        <div id="transactionsTable" style="display: none;">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>File Name</th>
                                            <th>Status</th>
                                            <th>Last Sync</th>
                                            <th>Last Line</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transactionsBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div id="noTransactions" class="text-center py-4">
                            <p class="text-muted">Click "Query Transactions" to view recent syncs</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('queryTransactions').addEventListener('click', function() {
            const table = document.getElementById('transactionsTable');
            const noTransactions = document.getElementById('noTransactions');
            const tbody = document.getElementById('transactionsBody');
            
            // Show loading state
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">Loading...</td></tr>';
            table.style.display = 'block';
            noTransactions.style.display = 'none';
            
            // Fetch transactions
            fetch('get_transactions.php')
                .then(response => response.json())
                .then(data => {
                    if (data.transactions && data.transactions.length > 0) {
                        tbody.innerHTML = data.transactions.map(sync => `
                            <tr>
                                <td>${sync.file_name}</td>
                                <td>
                                    <span class="badge bg-${sync.status === 'completed' ? 'success' : (sync.status === 'failed' ? 'danger' : 'warning')}">
                                        ${sync.status.charAt(0).toUpperCase() + sync.status.slice(1)}
                                    </span>
                                </td>
                                <td>${new Date(sync.last_sync_time).toLocaleString()}</td>
                                <td>${sync.last_processed_line}</td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center">No transactions found</td></tr>';
                    }
                })
                .catch(error => {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error loading transactions</td></tr>';
                    console.error('Error:', error);
                });
        });
    </script>
</body>
</html> 