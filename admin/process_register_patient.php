<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$uhid = trim($_POST['uhid'] ?? '');

if (empty($name) || empty($uhid)) {
    echo json_encode(['error' => 'Name and UHID are required']);
    exit;
}

$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$uhid = htmlspecialchars($uhid, ENT_QUOTES, 'UTF-8');

try {
    $db = Database::getInstance();
    $existing = $db->fetch("SELECT id FROM patients WHERE UHID = ?", [$uhid]);
    if ($existing) {
        echo json_encode(['error' => 'UHID already exists']);
        exit;
    }
    $result = $db->insert('patients', [
        'UHID' => $uhid,
        'name' => $name,
        'total_points' => 0
    ]);
    if ($result) {
        echo json_encode(['success' => 'Patient registered successfully']);
    } else {
        echo json_encode(['error' => 'Failed to register patient']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
} 