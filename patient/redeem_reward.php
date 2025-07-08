<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/controllers/PatientController.php';

// Set session name before starting session
session_name(SESSION_NAME);
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['reward_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing reward ID']);
    exit;
}

$controller = new PatientController();
$result = $controller->redeemReward($_SESSION['user_id'], $data['reward_id']);

header('Content-Type: application/json');
if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to redeem reward']);
} 