<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';

try {
    $db = Database::getInstance();
    echo "Database connection successful!\n";

    // Test admins table
    $admins = $db->fetchAll("SELECT * FROM admins");
    echo "\nAdmins table contents:\n";
    print_r($admins);

    // Test patients table
    $patients = $db->fetchAll("SELECT * FROM patients");
    echo "\nPatients table contents:\n";
    print_r($patients);

    // Test transactions table
    $transactions = $db->fetchAll("SELECT * FROM transactions");
    echo "\nTransactions table contents:\n";
    print_r($transactions);

    // Test points_settings table
    $settings = $db->fetchAll("SELECT * FROM points_settings");
    echo "\nPoints settings table contents:\n";
    print_r($settings);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 