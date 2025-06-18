<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../src/controllers/AdminController.php';

try {
    $db = Database::getInstance();
    $adminController = new AdminController();
    
    echo "Fetching all patients...\n";
    flush();
    $patients = $db->fetchAll("SELECT id, UHID, name, qr_token FROM patients");
    echo "Found " . count($patients) . " patients.\n";
    flush();
    
    $updated = 0;
    $errors = [];
    
    echo "Starting QR code generation loop...\n";
    flush();
    
    foreach ($patients as $patient) {
        echo "Processing patient: " . $patient['name'] . " (ID: " . $patient['id'] . ", UHID: " . $patient['UHID'] . ")\n";
        flush();
        echo "Current QR token: " . ($patient['qr_token'] ?: 'none') . "\n";
        flush();
        
        $result = $adminController->generateQrCode($patient['id']);
        if (!$result) {
            echo "  - Failed with ID, trying with UHID...\n";
            flush();
            $result = $adminController->generateQrCode($patient['UHID']);
        }
        if ($result && !empty($result['token'])) {
            $updated++;
            echo "✓ Generated new QR token: " . $result['token'] . "\n";
        } else {
            $errors[] = "Failed for ID: " . $patient['id'] . ", UHID: " . $patient['UHID'] . ", Name: " . $patient['name'];
            echo "✗ Failed to generate QR code\n";
        }
        echo "----------------------------------------\n";
        flush();
    }
    
    echo "\nSummary:\n";
    echo "QR code tokens generated/verified for $updated patients.\n";
    if ($errors) {
        echo "Errors:\n" . implode("\n", $errors) . "\n";
    }
    flush();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    flush();
} 