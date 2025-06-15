<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../src/controllers/AdminController.php';

session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

$controller = new AdminController();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$patients = $controller->getPatients($page);
$total = $controller->getPatientCount();
$totalPages = ceil($total / 10); // 10 patients per page

// Get the view content
$viewContent = $controller->render('admin/patients', [
    'patients' => $patients,
    'currentPage' => $page,
    'totalPages' => $totalPages
]);

// Output the view
echo $viewContent;
?> 