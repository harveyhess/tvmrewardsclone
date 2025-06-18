<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

try {
    $db = Database::getInstance();
    
    // Check if patients table exists
    $tableExists = $db->fetch("SHOW TABLES LIKE 'patients'");
    if (!$tableExists) {
        echo "Error: patients table does not exist!\n";
        exit;
    }
    
    // Count patients
    $count = $db->fetch("SELECT COUNT(*) as count FROM patients")['count'];
    echo "Found $count patients in the database.\n";
    
    if ($count > 0) {
        // Show first 5 patients
        $patients = $db->fetchAll("SELECT id, UHID, name, qr_token FROM patients LIMIT 5");
        echo "\nFirst 5 patients:\n";
        foreach ($patients as $patient) {
            echo "ID: {$patient['id']}, UHID: {$patient['UHID']}, Name: {$patient['name']}\n";
            echo "QR Token: " . ($patient['qr_token'] ?: 'none') . "\n";
            echo "----------------------------------------\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 