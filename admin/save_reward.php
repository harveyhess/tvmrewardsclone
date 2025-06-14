<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/controllers/RewardController.php';

session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
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
if (!isset($data['name']) || !isset($data['points_cost'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$controller = new RewardController();
$result = false;

if (isset($data['reward_id']) && !empty($data['reward_id'])) {
    // Update existing reward
    $result = $controller->updateReward(
        $data['reward_id'],
        $data['name'],
        $data['description'] ?? '',
        $data['points_cost'],
        $data['is_active'] ?? true
    );
} else {
    // Create new reward
    $result = $controller->createReward(
        $data['name'],
        $data['description'] ?? '',
        $data['points_cost']
    );
}

header('Content-Type: application/json');
if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to save reward']);
} 