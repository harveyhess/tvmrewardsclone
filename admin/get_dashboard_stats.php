<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // Get total points across all patients
    $totalPoints = $db->fetch(
        "SELECT SUM(total_points) as total FROM patients"
    )['total'] ?? 0;
    
    // Get total patients
    $totalPatients = $db->fetch(
        "SELECT COUNT(*) as count FROM patients"
    )['count'] ?? 0;
    
    // Get recent transactions with patient details - showing only transaction amount and points
    $recentTransactions = $db->fetchAll(
        "SELECT 
            p.name as patient_name,
            t.ReffNo,
            t.Amount,
            t.points_earned,
            t.created_at
         FROM transactions t 
         JOIN patients p ON t.UHID = p.id 
         ORDER BY t.created_at DESC 
         LIMIT 10"
    );
    
    // Get points distribution by tier
    $pointsByTier = $db->fetchAll(
        "SELECT t.name as tier_name, COUNT(p.id) as patient_count, SUM(p.total_points) as total_points
         FROM patients p
         LEFT JOIN tiers t ON p.total_points BETWEEN t.min_points AND t.max_points
         GROUP BY t.id, t.name
         ORDER BY t.min_points"
    );
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_points' => $totalPoints,
            'total_patients' => $totalPatients,
            'recent_transactions' => $recentTransactions,
            'points_by_tier' => $pointsByTier
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error getting dashboard stats: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to get dashboard statistics'
    ]);
} 