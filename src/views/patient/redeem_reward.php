<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../src/controllers/PatientController.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$rewardId = $data['reward_id'] ?? null;

if (!$rewardId) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Reward ID is required']);
    exit;
}

try {
    $controller = new PatientController();
    $result = $controller->redeemReward($_SESSION['user_id'], $rewardId);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
} 