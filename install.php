<?php
require_once 'config/config.php';

try {
    // Create database if it doesn't exist
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $pdo->exec("USE " . DB_NAME);

    // Create tables
    $tables = [
        "CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS patients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            phone_number VARCHAR(20) NOT NULL,
            total_points INT DEFAULT 0,
            qr_token VARCHAR(255) UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            amount_paid DECIMAL(10,2) NOT NULL,
            points_earned INT NOT NULL,
            transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES patients(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS points_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            points_rate INT NOT NULL DEFAULT 100,
            updated_by INT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (updated_by) REFERENCES admins(id)
        )",

        "CREATE TABLE IF NOT EXISTS login_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            patient_name VARCHAR(100) NOT NULL,
            phone_number VARCHAR(20) NOT NULL,
            login_method ENUM('regular', 'qr_code') NOT NULL DEFAULT 'regular',
            login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES patients(id)
        )"
    ];

    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }

    // Create default admin account
    $defaultAdmin = [
        'username' => 'admin',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'email' => 'admin@hospital.com'
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO admins (username, password, email) VALUES (?, ?, ?)");
    $stmt->execute([$defaultAdmin['username'], $defaultAdmin['password'], $defaultAdmin['email']]);

    // Insert default points settings
    $stmt = $pdo->prepare("INSERT IGNORE INTO points_settings (points_rate, updated_by) VALUES (?, 1)");
    $stmt->execute([DEFAULT_POINTS_RATE]);

    echo "Installation completed successfully!\n";
    echo "Default admin credentials:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    echo "Please change these credentials after first login.\n";

} catch (PDOException $e) {
    die("Installation failed: " . $e->getMessage());
} 