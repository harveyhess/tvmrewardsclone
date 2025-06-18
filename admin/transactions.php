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

$db = Database::getInstance();
$UHID = isset($_GET['UHID']) ? trim($_GET['UHID']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$query = "SELECT t.*, p.name as patient_name, p.UHID as patient_uhid FROM transactions t JOIN patients p ON t.UHID = p.id";
$params = [];
if ($UHID) {
    $query .= " WHERE t.UHID = ?";
    $params[] = $UHID;
} elseif ($search) {
    $query .= " WHERE p.name LIKE ? OR p.UHID LIKE ? OR t.Amount LIKE ? OR t.points_earned LIKE ?";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam, $searchParam];
}
$query .= " ORDER BY t.transaction_date DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$transactions = $db->fetchAll($query, $params);

$countQuery = "SELECT COUNT(*) as count FROM transactions t JOIN patients p ON t.UHID = p.id";
if ($UHID) {
    $countQuery .= " WHERE t.UHID = ?";
} elseif ($search) {
    $countQuery .= " WHERE p.name LIKE ? OR p.UHID LIKE ? OR t.Amount LIKE ? OR t.points_earned LIKE ?";
}
$countParams = $UHID ? [$UHID] : ($search ? ["%$search%", "%$search%", "%$search%", "%$search%"] : []);
$total = $db->fetch($countQuery, $countParams)['count'];
$totalPages = ceil($total / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .filters { margin-bottom: 20px; }
        .filters input, .filters select { margin-right: 10px; padding: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f4f4f4; }
        .pagination { margin-top: 20px; }
        .pagination a { margin: 0 5px; padding: 5px 10px; text-decoration: none; border: 1px solid #ddd; }
        .pagination a.active { background-color: #007bff; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Transactions</h1>
        <div class="filters">
            <form method="GET" action="transactions.php">
                <input type="text" name="search" placeholder="Search by name, UHID, amount, or points" value="<?php echo htmlspecialchars($search); ?>">
                <select name="per_page" onchange="this.form.submit()">
                    <option value="10" <?php echo $perPage == 10 ? 'selected' : ''; ?>>10 per page</option>
                    <option value="20" <?php echo $perPage == 20 ? 'selected' : ''; ?>>20 per page</option>
                    <option value="30" <?php echo $perPage == 30 ? 'selected' : ''; ?>>30 per page</option>
                    <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50 per page</option>
                </select>
                <button type="submit">Filter</button>
                        </form>
                    </div>
        <table>
                                <thead>
                                    <tr>
                    <th>Date</th>
                    <th>UHID</th>
                    <th>Patient Name</th>
                    <th>Amount</th>
                    <th>Points Earned</th>
                    <th>Reference No</th>
                                    </tr>
                                </thead>
                                <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($transaction['transaction_date']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['patient_uhid']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['patient_name']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['Amount']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['points_earned']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['ReffNo']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&per_page=<?php echo $perPage; ?>&search=<?php echo urlencode($search); ?>" class="<?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
        <a href="patients.php" class="button">Back to Patients</a>
    </div>
</body>
</html> 