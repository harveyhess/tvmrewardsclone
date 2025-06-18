<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

try {
    // Use the Database class for connection (supports DATABASE_URL)
    $pdo = Database::getInstance()->getConnection();

    // Create tables with optimized schema
    $tables = [
        // Drop existing tables if they exist - order matters due to foreign key constraints
        "DROP TABLE IF EXISTS points_ledger",        // References patients
        "DROP TABLE IF EXISTS redemptions",          // References patients and rewards
        "DROP TABLE IF EXISTS transactions",         // References patients
        "DROP TABLE IF EXISTS login_logs",           // References patients
        "DROP TABLE IF EXISTS tier_downgrade_tracking", // References patients and tiers
        "DROP TABLE IF EXISTS points_settings",      // References admins
        "DROP TABLE IF EXISTS patients",             // References tiers
        "DROP TABLE IF EXISTS rewards",              // No foreign keys
        "DROP TABLE IF EXISTS tiers",                // No foreign keys
        "DROP TABLE IF EXISTS admins",               // No foreign keys
        "DROP TABLE IF EXISTS transaction_sync",     // No foreign keys
        "DROP TABLE IF EXISTS transaction_csv_links", // No foreign keys

        // Create admins table with proper indexing
        "CREATE TABLE admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY idx_username (username),
            UNIQUE KEY idx_email (email)
        ) ENGINE=InnoDB",

        // Create tiers table with optimized structure
        "CREATE TABLE tiers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            min_points INT NOT NULL,
            max_points INT,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_points (min_points, max_points)
        ) ENGINE=InnoDB",
        
        // Create patients table with optimized indexes
        "CREATE TABLE patients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            UHID VARCHAR(50) NOT NULL,
            name VARCHAR(100) NOT NULL,
            total_points INT DEFAULT 0,
            points_version INT DEFAULT 1,
            tier_id INT,
            qr_token VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_uhid (UHID),
            UNIQUE KEY idx_qr_token (qr_token),
            INDEX idx_points (total_points),
            INDEX idx_tier (tier_id),
            FOREIGN KEY (tier_id) REFERENCES tiers(id) ON DELETE SET NULL
        ) ENGINE=InnoDB",
        
        // Create transactions table with optimized structure for bulk operations
        "CREATE TABLE transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            UHID INT NOT NULL,
            ReffNo VARCHAR(255) NOT NULL,
            Amount DECIMAL(10,2) NOT NULL,
            points_earned INT NOT NULL,
            points_version INT NOT NULL,
            transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_uhid (UHID),
            INDEX idx_transaction_date (transaction_date),
            INDEX idx_reffno (ReffNo),
            UNIQUE KEY idx_unique_transaction (UHID, ReffNo, Amount),
            FOREIGN KEY (UHID) REFERENCES patients(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        // Create points ledger for tracking point changes
        "CREATE TABLE points_ledger (
            id INT AUTO_INCREMENT PRIMARY KEY,
            UHID INT NOT NULL,
            points INT NOT NULL,
            type ENUM('earn', 'redeem', 'adjust') NOT NULL,
            reference_id INT,
            reference_type VARCHAR(50),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_uhid (UHID),
            INDEX idx_type (type),
            INDEX idx_reference (reference_type, reference_id),
            FOREIGN KEY (UHID) REFERENCES patients(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        // Create rewards table
        "CREATE TABLE rewards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            points_required INT NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_points (points_required),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB",
        
        // Create redemptions table
        "CREATE TABLE redemptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            UHID INT NOT NULL,
            reward_id INT NOT NULL,
            points_spent INT NOT NULL,
            status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            INDEX idx_uhid (UHID),
            INDEX idx_status (status),
            INDEX idx_created (created_at),
            FOREIGN KEY (UHID) REFERENCES patients(id) ON DELETE CASCADE,
            FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        // Create points settings table
        "CREATE TABLE points_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            points_rate INT NOT NULL,
            updated_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (updated_by) REFERENCES admins(id)
        ) ENGINE=InnoDB",
        
        // Create transaction sync table for tracking CSV imports
        "CREATE TABLE transaction_sync (
            id INT AUTO_INCREMENT PRIMARY KEY,
            file_name VARCHAR(255) NOT NULL,
            last_processed_line INT DEFAULT 0,
            last_sync_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_sync_time (last_sync_time)
        ) ENGINE=InnoDB",
        
        // Create tier downgrade tracking table
        "CREATE TABLE tier_downgrade_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            UHID INT NOT NULL,
            current_tier_id INT NOT NULL,
            downgrade_reason ENUM('inactivity', 'redemption_limit') NOT NULL,
            downgrade_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_pending BOOLEAN DEFAULT TRUE,
            INDEX idx_uhid (UHID),
            INDEX idx_pending (is_pending),
            FOREIGN KEY (UHID) REFERENCES patients(id) ON DELETE CASCADE,
            FOREIGN KEY (current_tier_id) REFERENCES tiers(id)
        ) ENGINE=InnoDB",

        // Create transaction CSV links table
        "CREATE TABLE transaction_csv_links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            url VARCHAR(255) NOT NULL,
            last_fetched TIMESTAMP NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_last_fetched (last_fetched)
        ) ENGINE=InnoDB"
    ];

    // Execute each table creation query
    foreach ($tables as $query) {
        $pdo->exec($query);
    }

    // Insert default admin user
    $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO admins (username, password, email) VALUES ('admin', '$defaultPassword', 'admin@example.com')");

    // Insert default points rate
    $pdo->exec("INSERT INTO points_settings (points_rate, updated_by) VALUES (100, 1)");

    // Insert default tiers
    $defaultTiers = [
        ['Bronze', 0, 500, 'Basic tier with standard benefits'],
        ['Silver', 501, 999, 'Mid-tier with enhanced benefits'],
        ['Gold', 1000, null, 'Premium tier with exclusive benefits']
    ];

    foreach ($defaultTiers as $tier) {
        $stmt = $pdo->prepare("INSERT INTO tiers (name, min_points, max_points, description) VALUES (?, ?, ?, ?)");
        $stmt->execute($tier);
    }

    echo "Database setup completed successfully!\n";
} catch (Exception $e) {
    echo "Error setting up database: " . $e->getMessage() . "\n";
    exit(1);
}