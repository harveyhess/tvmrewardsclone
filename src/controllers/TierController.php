<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../../includes/Database.php';

class TierController extends BaseController {
    protected $db;

    public function __construct($requireAdmin = true) {
        parent::__construct();
        if ($requireAdmin) {
            $this->requireAdmin();
        }
        $this->db = Database::getInstance();
    }

    public function createTier($name, $minPoints, $maxPoints = null, $description = '') {
        try {
            return $this->db->insert('tiers', [
                'name' => $name,
                'min_points' => $minPoints,
                'max_points' => $maxPoints,
                'description' => $description
            ]);
        } catch (Exception $e) {
            error_log("Error creating tier: " . $e->getMessage());
            return false;
        }
    }

    public function updateTier($id, $name, $minPoints, $maxPoints = null, $description = '') {
        try {
            $this->db->execute(
                "UPDATE tiers SET name = ?, min_points = ?, max_points = ?, description = ? WHERE id = ?",
                [$name, $minPoints, $maxPoints, $description, $id]
            );
            return true;
        } catch (Exception $e) {
            error_log("Error updating tier: " . $e->getMessage());
            return false;
        }
    }

    public function deleteTier($id) {
        try {
            $this->db->execute("DELETE FROM tiers WHERE id = ?", [$id]);
            return true;
        } catch (Exception $e) {
            error_log("Error deleting tier: " . $e->getMessage());
            return false;
        }
    }

    public function getAllTiers() {
        return $this->db->fetchAll("SELECT * FROM tiers ORDER BY min_points ASC");
    }

    public function getTierById($id) {
        return $this->db->fetch("SELECT * FROM tiers WHERE id = ?", [$id]);
    }

    public function getTierByPoints($points) {
        return $this->db->fetch(
            "SELECT * FROM tiers WHERE min_points <= ? AND (max_points IS NULL OR max_points >= ?) ORDER BY min_points DESC LIMIT 1",
            [$points, $points]
        );
    }

    public function updatePatientTier($patientId) {
        try {
            $patient = $this->db->fetch(
                "SELECT total_points FROM patients WHERE id = ?",
                [$patientId]
            );

            if (!$patient) {
                return false;
            }

            $tier = $this->getTierByPoints($patient['total_points']);
            
            if ($tier) {
                $this->db->execute(
                    "UPDATE patients SET tier_id = ? WHERE id = ?",
                    [$tier['id'], $patientId]
                );
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error updating patient tier: " . $e->getMessage());
            return false;
        }
    }
} 