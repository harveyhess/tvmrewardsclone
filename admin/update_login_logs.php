<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

try {
    $db = Database::getInstance();
    
    // Create the login_logs table if it doesn't exist
    $db->execute("
        CREATE TABLE IF NOT EXISTS login_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            UHID VARCHAR(255) NOT NULL,
            patient_name VARCHAR(255) NOT NULL,
            login_method VARCHAR(50) NOT NULL,
            login_time DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    echo "Login logs table created successfully\n";
    
    // Check if phone_number column exists
    $result = $db->fetch("SHOW COLUMNS FROM login_logs LIKE 'phone_number'");
    
    if ($result) {
        // Drop the phone_number column
        $db->execute("ALTER TABLE login_logs DROP COLUMN phone_number");
        echo "Successfully removed phone_number column from login_logs table\n";
    } else {
        echo "phone_number column does not exist in login_logs table\n";
    }
    
    echo "Login logs table structure updated successfully\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 