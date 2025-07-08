<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || isset($_SESSION['is_admin'])) {
    echo json_encode(['error' => 'Not logged in as patient']);
    exit;
}
$user_id = $_SESSION['user_id'];
$db = Database::getInstance();
$redemptions = $db->fetchAll('SELECT id, status FROM redemptions WHERE UHID = ? ORDER BY created_at DESC', [$user_id]);
echo json_encode(['redemptions' => $redemptions]); 