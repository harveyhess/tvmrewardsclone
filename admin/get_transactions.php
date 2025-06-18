<?php
require_once '../config/config.php';
require_once '../includes/Database.php';

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Get recent transaction syncs with pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Use prepared statement with LIMIT and OFFSET
    $transactions = $db->fetchAll(
        "SELECT * FROM transaction_sync 
         ORDER BY last_sync_time DESC 
         LIMIT ? OFFSET ?",
        [$limit, $offset]
    );
    
    // Get total count for pagination
    $total = $db->fetch(
        "SELECT COUNT(*) as count FROM transaction_sync"
    )['count'];
    
    header('Content-Type: application/json');
    echo json_encode([
        'transactions' => $transactions,
        'total' => $total,
        'currentPage' => $page,
        'totalPages' => ceil($total / $limit)
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error fetching transactions']);
    error_log("Error in get_transactions.php: " . $e->getMessage());
} 