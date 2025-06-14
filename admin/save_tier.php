<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/controllers/TierController.php';

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
if (!isset($data['name']) || !isset($data['min_points'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$controller = new TierController();
$result = false;

if (isset($data['tier_id']) && !empty($data['tier_id'])) {
    // Update existing tier
    $result = $controller->updateTier(
        $data['tier_id'],
        $data['name'],
        $data['min_points'],
        $data['max_points'] ?? null,
        $data['description'] ?? ''
    );
} else {
    // Create new tier
    $result = $controller->createTier(
        $data['name'],
        $data['min_points'],
        $data['max_points'] ?? null,
        $data['description'] ?? ''
    );
}

header('Content-Type: application/json');
if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to save tier']);
} 