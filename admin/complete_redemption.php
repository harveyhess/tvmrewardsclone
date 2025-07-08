<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/controllers/RewardController.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['redemption_id'])) {
    echo json_encode(['error' => 'Missing redemption ID']);
    exit;
}
$controller = new RewardController();
$result = $controller->completeRedemption($data['redemption_id']);
if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to complete redemption']);
} 