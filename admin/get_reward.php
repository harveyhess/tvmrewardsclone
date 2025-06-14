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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing reward ID']);
    exit;
}

$controller = new RewardController();
$reward = $controller->getRewardById($_GET['id']);

header('Content-Type: application/json');
if ($reward) {
    echo json_encode($reward);
} else {
    echo json_encode(['error' => 'Reward not found']);
} 