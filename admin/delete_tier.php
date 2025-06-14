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
if (!isset($data['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing tier ID']);
    exit;
}

$controller = new TierController();
$result = $controller->deleteTier($data['id']);

header('Content-Type: application/json');
if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to delete tier']);
} 