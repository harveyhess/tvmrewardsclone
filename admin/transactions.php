<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../src/controllers/AdminController.php';

session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: /admin/login.php');
    exit;
}

$controller = new AdminController();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$uhid = isset($_GET['UHID']) ? trim($_GET['UHID']) : '';

$result = $controller->getTransactions($page, $limit, $search, $uhid);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/src/assets/css/style.css">
    <style>
        :root {
            --primary-color: #2ecc71;
            --primary-dark: #27ae60;
            --primary-light: #a9dfbf;
        }

        .navbar {
            background: var(--primary-color);
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .nav-brand {
            color: white;
            font-size: 1.5em;
            font-weight: bold;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .nav-menu a:hover {
            background: var(--primary-dark);
        }

        .nav-menu a.active {
            background: var(--primary-dark);
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            flex: 1;
            min-width: 200px;
        }

        .filter-button {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .filter-button:hover {
            background: var(--primary-dark);
        }

        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: var(--primary-color);
        }

        .pagination a:hover {
            background: var(--primary-light);
        }

        .pagination .active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .page-size {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-size select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/shared/loading.php'; ?>

    <div class="container">
        <nav class="navbar">
            <div class="nav-brand"><?php echo SITE_NAME; ?></div>
            <div class="nav-menu">
                <a href="/admin/dashboard.php">Dashboard</a>
                <a href="/admin/patients.php">Patients</a>
                <a href="/admin/transactions.php" class="active">Transactions</a>
                <a href="/admin/rewards.php">Rewards</a>
                <a href="/admin/tiers.php">Tiers</a>
                <a href="/admin/logout.php">Logout</a>
            </div>
        </nav>

        <div class="dashboard-content">
            <div class="filters">
                <form method="GET" action="" class="filter-group">
                    <input type="text" name="search" class="filter-input" 
                           placeholder="Search by name or UHID" 
                           value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="filter-button">Filter</button>
                        </form>
                </div>

            <div class="table-container">
                        <div class="table-responsive">
                    <table>
                                <thead>
                                    <tr>
                                <th>Date</th>
                                <th>Patient Name</th>
                                <th>UHID</th>
                                <th>Amount (KSH)</th>
                                <th>Points</th>
                                <th>Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                        <?php foreach ($result['transactions'] as $transaction): ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($transaction['transaction_date'])); ?></td>
                                <td><?php echo htmlspecialchars($transaction['patient_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($transaction['patient_uhid'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>KSH <?php echo number_format($transaction['Amount'], 2); ?></td>
                                <td><?php echo number_format($transaction['points_earned']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['ReffNo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

            <div class="pagination">
                <?php if ($result['currentPage'] > 1): ?>
                    <a href="?page=<?php echo $result['currentPage'] - 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $result['totalPages']; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>" 
                       class="<?php echo $i === $result['currentPage'] ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($result['currentPage'] < $result['totalPages']): ?>
                    <a href="?page=<?php echo $result['currentPage'] + 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                <?php endif; ?>
                </div>

            <div class="page-size">
                <span>Show</span>
                <select onchange="window.location.href='?page=1&limit=' + this.value + '&search=<?php echo urlencode($search); ?>'">
                    <option value="10" <?php echo $limit === 10 ? 'selected' : ''; ?>>10</option>
                    <option value="20" <?php echo $limit === 20 ? 'selected' : ''; ?>>20</option>
                    <option value="30" <?php echo $limit === 30 ? 'selected' : ''; ?>>30</option>
                    <option value="40" <?php echo $limit === 40 ? 'selected' : ''; ?>>40</option>
                    <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50</option>
                </select>
                <span>entries</span>
            </div>
        </div>
    </div>
</body>
</html> 