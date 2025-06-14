<?php
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/TierController.php';

class PatientController extends BaseController {
    public function __construct() {
        parent::__construct();
    }

    public function dashboard() {
        if (!$this->isLoggedIn() && !isset($_GET['token'])) {
            return $this->render('patient/login');
        }

        $UHID = null;
        if (isset($_GET['token'])) {
            $patient = $this->db->fetch(
                "SELECT * FROM patients WHERE qr_token = ?",
                [$_GET['token']]
            );
            if ($patient) {
                $UHID = $patient['id'];
            }
        } else {
            $UHID = $_SESSION['user_id'];
        }

        if (!$UHID) {
            return $this->render('patient/login', [
                'error' => 'Invalid token or session'
            ]);
        }

        $patient = $this->db->fetch(
            "SELECT * FROM patients WHERE id = ?",
            [$UHID]
        );

        $transactions = $this->db->fetchAll(
            "SELECT * FROM transactions WHERE UHID = ? ORDER BY transaction_date DESC LIMIT 10",
            [$UHID]
        );

        return $this->render('patient/dashboard', [
            'patient' => $patient,
            'transactions' => $transactions
        ]);
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->render('patient/login');
        }

        $identifier = $this->sanitizeInput($_POST['identifier']);
        $patient = $this->db->fetch(
            "SELECT * FROM patients WHERE UHID = ? OR phone_number = ?",
            [$identifier, $identifier]
        );

        if (!$patient) {
            return $this->render('patient/login', [
                'error' => 'Patient not found'
            ]);
        }

        $_SESSION['user_id'] = $patient['id'];
        $_SESSION['is_admin'] = false;

        // Update patient's tier
        $this->updatePatientTier($patient['id']);

        $this->redirect('/patient/dashboard.php');
    }

    public function logout() {
        session_destroy();
        $this->redirect('/patient/login.php');
    }

    public function getTransactions() {
        if (!$this->isLoggedIn()) {
            return $this->jsonResponse(['error' => 'Not authenticated'], 401);
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $transactions = $this->db->fetchAll(
            "SELECT * FROM transactions WHERE UHID = ? ORDER BY transaction_date DESC LIMIT ? OFFSET ?",
            [$_SESSION['user_id'], $limit, $offset]
        );

        $total = $this->db->fetch(
            "SELECT COUNT(*) as count FROM transactions WHERE UHID = ?",
            [$_SESSION['user_id']]
        )['count'];

        return $this->jsonResponse([
            'transactions' => $transactions,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => ceil($total / $limit)
        ]);
    }

    public function getPatientDetails($UHID) {
        $patient = $this->db->fetch(
            "SELECT p.*, COALESCE(t.name, 'No Tier') as tier_name 
            FROM patients p 
            LEFT JOIN tiers t ON p.tier_id = t.id 
            WHERE p.id = ?",
            [$UHID]
        );
        return $patient;
    }

    public function getPatientTransactions($UHID) {
        return $this->db->fetchAll(
            "SELECT t.* 
            FROM (
                SELECT id, UHID, transaction_date,Amount,ReffNo, points_earned,
                       ROW_NUMBER() OVER (
                           PARTITION BY DATE(transaction_date),Amount, ReffNo,points_earned 
                           ORDER BY id DESC
                       ) as rn
                FROM transactions 
                WHERE UHID = ?
            ) t 
            WHERE t.rn = 1
            ORDER BY t.transaction_date DESC, t.id DESC 
            LIMIT 10",
            [$UHID]
        );
    }

    public function getAllPatientTransactions($UHID, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $transactions = $this->db->fetchAll(
            "SELECT t.* 
            FROM (
                SELECT id, UHID, transaction_date,Amount, points_earned,
                       ROW_NUMBER() OVER (
                           PARTITION BY DATE(transaction_date),Amount, ReffNo,points_earned 
                           ORDER BY id DESC
                       ) as rn
                FROM transactions 
                WHERE UHID = ?
            ) t 
            WHERE t.rn = 1
            ORDER BY t.transaction_date DESC, t.id DESC 
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset,
            [$UHID]
        );

        $total = $this->db->fetch(
            "SELECT COUNT(*) as count 
            FROM (
                SELECT id
                FROM (
                    SELECT id,
                           ROW_NUMBER() OVER (
                               PARTITION BY DATE(transaction_date),Amount,ReffNo, points_earned 
                               ORDER BY id DESC
                           ) as rn
                    FROM transactions 
                    WHERE UHID = ?
                ) t 
                WHERE t.rn = 1
            ) as unique_transactions",
            [$UHID]
        )['count'];

        return [
            'transactions' => $transactions,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => ceil($total / $limit)
        ];
    }

    public function updatePatientTier($UHID) {
        $tierController = new TierController(false);
        return $tierController->updatePatientTier($UHID);
    }

    public function validatePatient($name, $phone) {
        // Debug log
        error_log("[PatientController] Validating patient - Name: $name, Phone: $phone");

        // First try exact match
        $query = "SELECT * FROM patients WHERE name = ? AND phone_number = ?";
        error_log("[PatientController] Trying exact match query: $query");
        
        $patient = $this->db->fetch($query, [$name, $phone]);
        
        if ($patient) {
            error_log("[PatientController] Found patient with exact match: " . print_r($patient, true));
            // Update patient's tier
            $this->updatePatientTier($patient['id']);
            return $patient;
        }

        // If no exact match, try case-insensitive match
        $query = "SELECT * FROM patients WHERE LOWER(name) = LOWER(?) AND phone_number = ?";
        error_log("[PatientController] Trying case-insensitive match query: $query");
        
        $patient = $this->db->fetch($query, [$name, $phone]);
        
        if ($patient) {
            error_log("[PatientController] Found patient with case-insensitive match: " . print_r($patient, true));
            // Update patient's tier
            $this->updatePatientTier($patient['id']);
        } else {
            error_log("[PatientController] No patient found with either match method");
        }
        
        return $patient;
    }

    public function logLogin($UHID, $method = 'regular') {
        // Get patient details directly
        $patient = $this->db->fetch(
            "SELECT name, phone_number FROM patients WHERE id = ?",
            [$UHID]
        );
        
        if (!$patient) {
            error_log("[PatientController] Failed to log login - Patient not found: $UHID");
            return false;
        }

        // Insert login log with patient details
        $result = $this->db->insert('login_logs', [
            'UHID' => $UHID,
            'patient_name' => $patient['name'],
            'phone_number' => $patient['phone_number'],
            'login_method' => $method,
            'login_time' => date('Y-m-d H:i:s')
        ]);

        error_log("[PatientController] Login log result: " . ($result ? "Success" : "Failed"));
        return $result;
    }

    public function getPointsRate() {
        $result = $this->db->fetch(
            "SELECT points_rate FROM points_settings ORDER BY updated_at DESC LIMIT 1"
        );
        return $result['points_rate'] ?? DEFAULT_POINTS_RATE;
    }

    public function calculatePoints($amount) {
        $rate = $this->getPointsRate();
        return floor($amount / $rate);
    }

    public function importPatients($csvData) {
        $imported = 0;
        $updated = 0;
        $errors = [];

        foreach ($csvData as $row) {
            try {
                // Clean and validate data
                $name = trim($row['name'] ?? '');
                $phone = trim($row['phone_number'] ?? '');
                $UHID = trim($row['UHID'] ?? '');

                if (empty($name) || empty($phone) || empty($UHID)) {
                    $errors[] = "Missing required data for row: " . json_encode($row);
                    continue;
                }

                // Check if patient exists
                $existingPatient = $this->db->fetch(
                    "SELECT * FROM patients WHERE UHID = ? OR (name = ? AND phone_number = ?)",
                    [$UHID, $name, $phone]
                );

                if ($existingPatient) {
                    // Update existing patient
                    $this->db->update('patients', 
                        [
                            'name' => $name,
                            'phone_number' => $phone,
                            'updated_at' => date('Y-m-d H:i:s')
                        ],
                        'id = ?',
                        [$existingPatient['id']]
                    );
                    $updated++;
                } else {
                    // Insert new patient
                    $this->db->insert('patients', [
                        'UHID' => $UHID,
                        'name' => $name,
                        'phone_number' => $phone,
                        'total_points' => 0
                    ]);
                    $imported++;
                }
            } catch (Exception $e) {
                $errors[] = "Error processing row: " . json_encode($row) . " - " . $e->getMessage();
            }
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors
        ];
    }

    public function getAvailableRewards() {
        return $this->db->fetchAll(
            "SELECT * FROM rewards WHERE is_active = 1 ORDER BY points_cost ASC"
        );
    }

    public function getPatientRedemptions($UHID) {
        return $this->db->fetchAll(
            "SELECT r.*, rw.name as reward_name 
            FROM redemptions r 
            JOIN rewards rw ON r.reward_id = rw.id 
            WHERE r.UHID = ? 
            ORDER BY r.created_at DESC 
            LIMIT 10",
            [$UHID]
        );
    }

    public function redeemReward($UHID, $rewardId) {
        // Start transaction
        $this->db->getConnection()->beginTransaction();

        try {
            // Get reward details
            $reward = $this->db->fetch(
                "SELECT * FROM rewards WHERE id = ? AND is_active = 1",
                [$rewardId]
            );

            if (!$reward) {
                throw new Exception('Reward not found or inactive');
            }

            // Get patient details
            $patient = $this->db->fetch(
                "SELECT * FROM patients WHERE id = ?",
                [$UHID]
            );

            if (!$patient) {
                throw new Exception('Patient not found');
            }

            if ($patient['total_points'] < $reward['points_cost']) {
                throw new Exception('Not enough points');
            }

            // Create redemption record
            $redemptionId = $this->db->insert('redemptions', [
                'UHID' => $UHID,
                'reward_id' => $rewardId,
                'points_spent' => $reward['points_cost'],
                'status' => 'pending'
            ]);

            // Update patient points
            $this->db->execute(
                "UPDATE patients SET total_points = total_points - ? WHERE id = ?",
                [$reward['points_cost'], $UHID]
            );

            // Add to points ledger
            $this->db->insert('points_ledger', [
                'UHID' => $UHID,
                'points' => -$reward['points_cost'],
                'type' => 'redeem',
                'reference_id' => $redemptionId,
                'reference_type' => 'redemption',
                'description' => "Points spent on reward: " . $reward['name']
            ]);

            // Update patient's tier
            $this->updatePatientTier($UHID);

            $this->db->getConnection()->commit();
            return true;
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            throw $e;
        }
    }
} 