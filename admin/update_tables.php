<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

try {
    $db = Database::getInstance();
    
    // Create points_settings table if not exists
    $db->execute("
        CREATE TABLE IF NOT EXISTS points_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            points_rate INT NOT NULL DEFAULT 100,
            updated_by INT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "Points settings table created/verified\n";

    // Drop foreign key constraints first
    $db->execute("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop and recreate rewards table to ensure correct structure
    $db->execute("DROP TABLE IF EXISTS rewards");
    $db->execute("
        CREATE TABLE rewards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            points_cost INT NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "Rewards table recreated with correct structure\n";

    // Create redemptions table if not exists
    $db->execute("
        CREATE TABLE IF NOT EXISTS redemptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            UHID VARCHAR(255) NOT NULL,
            reward_id INT NOT NULL,
            points_spent INT NOT NULL,
            status VARCHAR(50) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (reward_id) REFERENCES rewards(id)
        )
    ");
    echo "Redemptions table created/verified\n";

    // Re-enable foreign key checks
    $db->execute("SET FOREIGN_KEY_CHECKS = 1");

    // Create points_ledger table if not exists
    $db->execute("
        CREATE TABLE IF NOT EXISTS points_ledger (
            id INT AUTO_INCREMENT PRIMARY KEY,
            UHID VARCHAR(255) NOT NULL,
            points INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            reference_id INT,
            reference_type VARCHAR(50),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Points ledger table created/verified\n";

    // Create login_logs table if not exists
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
    echo "Login logs table created/verified\n";

    // Create tiers table if not exists
    $db->execute("
        CREATE TABLE IF NOT EXISTS tiers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            min_points INT NOT NULL,
            max_points INT,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "Tiers table created/verified\n";

    // Add tier_id to patients table if not exists
    $result = $db->fetch("SHOW COLUMNS FROM patients LIKE 'tier_id'");
    if (!$result) {
        $db->execute("ALTER TABLE patients ADD COLUMN tier_id INT, ADD FOREIGN KEY (tier_id) REFERENCES tiers(id)");
        echo "Added tier_id to patients table\n";
    }

    // Add qr_token to patients table if not exists
    $result = $db->fetch("SHOW COLUMNS FROM patients LIKE 'qr_token'");
    if (!$result) {
        $db->execute("ALTER TABLE patients ADD COLUMN qr_token VARCHAR(255)");
        echo "Added qr_token to patients table\n";
    }

    echo "All tables updated successfully\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 