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
    
    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(10, intval($_GET['limit']))) : 10;
    $offset = ($page - 1) * $limit;
    
    // Get date range if provided
    $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
    $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
    
    // Build query conditions
    $conditions = [];
    $params = [];
    
    if ($dateFrom) {
        $conditions[] = "t.transaction_date >= ?";
        $params[] = $dateFrom;
    }
    if ($dateTo) {
        $conditions[] = "t.transaction_date <= ?";
        $params[] = $dateTo;
    }
    
    $whereClause = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";
    
    // Get transactions with patient info in a single optimized query
    $transactions = $db->fetchAll(
        "SELECT 
            t.id,
            t.transaction_date,
            t.Amount,
            t.points_earned,
            t.ReffNo,
            p.name as patient_name,
            p.UHID as patient_uhid
        FROM transactions t
        JOIN patients p ON t.UHID = p.id
        $whereClause
        ORDER BY t.transaction_date DESC
        LIMIT ? OFFSET ?",
        array_merge($params, [$limit, $offset])
    );
    
    // Get total count with the same conditions
    $total = $db->fetch(
        "SELECT COUNT(*) as count 
        FROM transactions t
        $whereClause",
        $params
    )['count'];
    
    // Format the response
    $response = [
        'transactions' => array_map(function($t) {
            return [
                'id' => $t['id'],
                'date' => date('Y-m-d H:i:s', strtotime($t['transaction_date'])),
                'amount' => number_format($t['Amount'], 2),
                'points' => $t['points_earned'],
                'reference' => $t['ReffNo'],
                'patient' => [
                    'name' => $t['patient_name'],
                    'uhid' => $t['patient_uhid']
                ]
            ];
        }, $transactions),
        'total' => $total,
        'currentPage' => $page,
        'totalPages' => ceil($total / $limit)
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Error in get_transactions.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Error fetching transactions',
        'transactions' => [],
        'total' => 0,
        'currentPage' => $page,
        'totalPages' => 0
    ]);
} 