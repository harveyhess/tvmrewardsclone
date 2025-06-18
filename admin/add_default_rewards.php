<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

try {
    $db = Database::getInstance();
    
    // Check if rewards table is empty
    $count = $db->fetch("SELECT COUNT(*) as count FROM rewards")['count'];
    
    if ($count == 0) {
        // Add default rewards
        $rewards = [
            [
                'name' => 'Free Consultation',
                'description' => 'One free consultation with a doctor',
                'points_cost' => 1000,
                'is_active' => 1
            ],
            [
                'name' => '10% Discount on Medicines',
                'description' => 'Get 10% off on your next medicine purchase',
                'points_cost' => 500,
                'is_active' => 1
            ],
            [
                'name' => 'Priority Appointment',
                'description' => 'Get priority booking for your next appointment',
                'points_cost' => 200,
                'is_active' => 1
            ],
            [
                'name' => 'Free Health Checkup',
                'description' => 'Comprehensive health checkup package',
                'points_cost' => 2000,
                'is_active' => 1
            ],
            [
                'name' => '5% Discount on Lab Tests',
                'description' => 'Get 5% off on your next lab test',
                'points_cost' => 300,
                'is_active' => 1
            ]
        ];
        
        foreach ($rewards as $reward) {
            $db->insert('rewards', $reward);
        }
        
        echo "Added " . count($rewards) . " default rewards\n";
    } else {
        echo "Rewards table already has data\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}