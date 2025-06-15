<?php
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/BaseController.php';

class PatientController extends BaseController {
    public function __construct() {
        parent::__construct();
    }

    public function dashboard() {
        if (!$this->isLoggedIn() && !isset($_GET['token'])) {
            return $this->render('patient/login');
        }

        $patientId = null;
        if (isset($_GET['token'])) {
            $patient = $this->db->fetch(
                "SELECT * FROM patients WHERE qr_token = ?",
                [$_GET['token']]
            );
            if ($patient) {
                $patientId = $patient['id'];
            }
        } else {
            $patientId = $_SESSION['user_id'];
        }

        if (!$patientId) {
            return $this->render('patient/login', [
                'error' => 'Invalid token or session'
            ]);
        }

        $patient = $this->db->fetch(
            "SELECT * FROM patients WHERE id = ?",
            [$patientId]
        );

        $transactions = $this->db->fetchAll(
            "SELECT * FROM transactions WHERE patient_id = ? ORDER BY transaction_date DESC LIMIT 10",
            [$patientId]
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
            "SELECT * FROM patients WHERE patient_id = ? OR phone_number = ?",
            [$identifier, $identifier]
        );

        if (!$patient) {
            return $this->render('patient/login', [
                'error' => 'Patient not found'
            ]);
        }

        $_SESSION['user_id'] = $patient['id'];
        $_SESSION['is_admin'] = false;

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
            "SELECT * FROM transactions WHERE patient_id = ? ORDER BY transaction_date DESC LIMIT ? OFFSET ?",
            [$_SESSION['user_id'], $limit, $offset]
        );

        $total = $this->db->fetch(
            "SELECT COUNT(*) as count FROM transactions WHERE patient_id = ?",
            [$_SESSION['user_id']]
        )['count'];

        return $this->jsonResponse([
            'transactions' => $transactions,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => ceil($total / $limit)
        ]);
    }

    public function getPatientDetails($patientId) {
        return $this->db->fetch(
            "SELECT * FROM patients WHERE id = ?",
            [$patientId]
        );
    }

    public function getPatientTransactions($patientId) {
        return $this->db->fetchAll(
            "SELECT t.* 
            FROM (
                SELECT id, patient_id, transaction_date, amount_paid, points_earned,
                       ROW_NUMBER() OVER (
                           PARTITION BY DATE(transaction_date), amount_paid, points_earned 
                           ORDER BY id DESC
                       ) as rn
                FROM transactions 
                WHERE patient_id = ?
            ) t 
            WHERE t.rn = 1
            ORDER BY t.transaction_date DESC, t.id DESC 
            LIMIT 10",
            [$patientId]
        );
    }

    public function getAllPatientTransactions($patientId, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $transactions = $this->db->fetchAll(
            "SELECT t.* 
            FROM (
                SELECT id, patient_id, transaction_date, amount_paid, points_earned,
                       ROW_NUMBER() OVER (
                           PARTITION BY DATE(transaction_date), amount_paid, points_earned 
                           ORDER BY id DESC
                       ) as rn
                FROM transactions 
                WHERE patient_id = ?
            ) t 
            WHERE t.rn = 1
            ORDER BY t.transaction_date DESC, t.id DESC 
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset,
            [$patientId]
        );

        $total = $this->db->fetch(
            "SELECT COUNT(*) as count 
            FROM (
                SELECT id
                FROM (
                    SELECT id,
                           ROW_NUMBER() OVER (
                               PARTITION BY DATE(transaction_date), amount_paid, points_earned 
                               ORDER BY id DESC
                           ) as rn
                    FROM transactions 
                    WHERE patient_id = ?
                ) t 
                WHERE t.rn = 1
            ) as unique_transactions",
            [$patientId]
        )['count'];

        return [
            'transactions' => $transactions,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => ceil($total / $limit)
        ];
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
            return $patient;
        }

        // If no exact match, try case-insensitive match
        $query = "SELECT * FROM patients WHERE LOWER(name) = LOWER(?) AND phone_number = ?";
        error_log("[PatientController] Trying case-insensitive match query: $query");
        
        $patient = $this->db->fetch($query, [$name, $phone]);
        
        if ($patient) {
            error_log("[PatientController] Found patient with case-insensitive match: " . print_r($patient, true));
        } else {
            error_log("[PatientController] No patient found with either match method");
        }
        
        return $patient;
    }

    public function logLogin($patientId, $method = 'regular') {
        // Get patient details first
        $patient = $this->getPatientDetails($patientId);
        if (!$patient) {
            error_log("[PatientController] Failed to log login - Patient not found: $patientId");
            return false;
        }

        // Insert login log with patient details
        $result = $this->db->insert('login_logs', [
            'patient_id' => $patientId,
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
                $patientId = trim($row['patient_id'] ?? '');

                if (empty($name) || empty($phone) || empty($patientId)) {
                    $errors[] = "Missing required data for row: " . json_encode($row);
                    continue;
                }

                // Check if patient exists
                $existingPatient = $this->db->fetch(
                    "SELECT * FROM patients WHERE patient_id = ? OR (name = ? AND phone_number = ?)",
                    [$patientId, $name, $phone]
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
                        'patient_id' => $patientId,
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
} 