<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../../includes/CsvHandler.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/RewardController.php';
require_once __DIR__ . '/TierController.php';

class AdminController extends BaseController {
    protected $db;

    public function __construct() {
        parent::__construct();
        $this->requireAdmin();
        $this->db = Database::getInstance();
    }

    public function dashboard() {
        $stats = $this->getDashboardStats();
        return $this->render('admin/dashboard', ['stats' => $stats]);
    }

    public function uploadCsv() {
        error_log("Starting CSV upload process");
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Invalid request method'];
            header('Location: upload.php');
            exit;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            error_log("File upload error: " . ($_FILES['csv_file']['error'] ?? 'No file uploaded'));
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Please select a valid CSV file'];
            header('Location: upload.php');
            exit;
        }

        $file = $_FILES['csv_file'];
        $pointsRate = isset($_POST['points_rate']) ? (int)$_POST['points_rate'] : DEFAULT_POINTS_RATE;
        
        error_log("Processing file: " . $file['name'] . " with points rate: " . $pointsRate);

        // Validate file type
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'csv') {
            error_log("Invalid file type: " . $fileExt);
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Only CSV files are allowed'];
            header('Location: upload.php');
            exit;
        }

        // Process the CSV file
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            error_log("Could not open file: " . $file['tmp_name']);
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Could not open file'];
            header('Location: upload.php');
            exit;
        }

        // Skip header row
        $headers = fgetcsv($handle, 0, ',', '"', '\\');
        error_log("CSV Headers: " . print_r($headers, true));

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            error_log("Processing row: " . print_r($data, true));
            
            if (count($data) < 4) {
                $errorCount++;
                $errors[] = "Invalid data format in row";
                continue;
            }

            $UHID = trim($data[0]);
            $name = trim($data[1]);
            $phone = trim($data[2]);
            $amount = (float)trim($data[3]);

            try {
                // Start transaction
                $this->db->getPdo()->beginTransaction();

                // Insert or update patient
                $patient = $this->db->fetch(
                    "SELECT id FROM patients WHERE UHID = ?",
                    [$UHID]
                );

                if ($patient) {
                    $UHID = $patient['id'];
                    error_log("Updating existing patient: " . $UHID);
                } else {
                    error_log("Creating new patient: " . $UHID);
                    $this->db->execute(
                        "INSERT INTO patients (UHID, name, phone_number) VALUES (?, ?, ?)",
                        [$UHID, $name, $phone]
                    );
                    $UHID = $this->db->lastInsertId();
                }

                // Calculate points based on amount paid
                $points = floor($amount / $pointsRate);
                error_log("Calculated points: " . $points . " for amount: " . $amount);

                // Add points to patient
                $this->updatePoints($UHID, $points);

                $this->db->getPdo()->commit();
                $successCount++;
            } catch (Exception $e) {
                $this->db->getPdo()->rollBack();
                $errorCount++;
                $errors[] = "Error processing UHID: $UHID - " . $e->getMessage();
                error_log("Error processing patient: " . $e->getMessage());
            }
        }

        fclose($handle);

        error_log("Upload complete. Success: $successCount, Errors: $errorCount");
        
        $_SESSION['flash_message'] = [
            'type' => $errorCount === 0 ? 'success' : 'warning',
            'message' => "Upload complete. Successfully processed $successCount patients. Failed: $errorCount" . 
                        ($errors ? "<br>Errors: " . implode("<br>", $errors) : "")
        ];
        header('Location: patients.php');
        exit;
    }

    public function patients() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $patients = $this->getPatients($page, $limit);
        $total = $this->getPatientCount();
        $totalPages = ceil($total / $limit);

        return $this->render('admin/patients', [
            'patients' => $patients,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }

    public function handleUpdatePoints() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['error' => 'Invalid request method'], 400);
        }

        $UHID = $this->sanitizeInput($_POST['UHID']);
        $points = (int)$_POST['points'];

        try {
            $this->updatePoints($UHID, $points);
            return $this->jsonResponse(['success' => true]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function updatePointsRate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['error' => 'Invalid request method'], 400);
        }

        $rate = (int)$_POST['rate'];
        $adminId = $_SESSION['user_id'];

        try {
            $this->db->insert('points_settings', [
                'points_rate' => $rate,
                'updated_by' => $adminId
            ]);

            return $this->jsonResponse(['success' => true]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function exportPatients() {
        $patients = $this->db->fetchAll(
            "SELECT UHID, name, phone_number, total_points FROM patients ORDER BY name"
        );

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="patients.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['UHID', 'Name', 'Phone Number', 'Total Points']);

        foreach ($patients as $patient) {
            fputcsv($output, $patient);
        }

        fclose($output);
        exit;
    }

    public function generateQrCode($UHID) {
        error_log("[AdminController] Generating QR code for UHID: " . $UHID);
        
        $patient = $this->getPatientDetails($UHID);
        if (!$patient) {
            error_log("[AdminController] Patient not found for UHID: " . $UHID);
            return false;
        }

        error_log("[AdminController] Found patient: " . $patient['name'] . " (ID: " . $patient['id'] . ")");

        // Generate a unique token if not exists
        if (empty($patient['qr_token'])) {
            error_log("[AdminController] Generating new QR token for patient");
            $token = bin2hex(random_bytes(32)); // Using 32 bytes for better security
            $result = $this->db->execute(
                "UPDATE patients SET qr_token = ? WHERE id = ?",
                [$token, $patient['id']]
            );
            if (!$result) {
                error_log("[AdminController] Failed to update patient with QR token");
                return false;
            }
            error_log("[AdminController] Successfully updated patient with QR token");
            $patient['qr_token'] = $token;
        } else {
            error_log("[AdminController] Using existing QR token: " . $patient['qr_token']);
        }

        // Create login data - only include name and token
        $loginData = [
            'name' => $patient['name'],
            'token' => $patient['qr_token']
        ];

        error_log("[AdminController] Generated login data for patient");

        // Return both token and patient info
        return [
            'token' => $patient['qr_token'],
            'UHID' => $patient['UHID'],
            'name' => $patient['name'],
            'login_data' => base64_encode(json_encode($loginData))
        ];
    }

    public function getDashboardStats() {
        $stats = [
            'total_patients' => 0
        ];

        try {
            // Get only total patients count initially
            $result = $this->db->fetch(
                "SELECT COUNT(*) as total_patients FROM patients"
            );
            
            $stats['total_patients'] = $result['total_patients'];
            
        } catch (Exception $e) {
            error_log("Error getting dashboard stats: " . $e->getMessage());
        }

        return $stats;
    }

    public function getPatients($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        return $this->db->fetchAll(
            "SELECT * FROM patients ORDER BY created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset
        );
    }

    public function getPatientCount() {
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM patients");
        return $result['count'];
    }

    public function getPatientDetails($idOrUhid) {
        // Try by id (integer)
        if (is_numeric($idOrUhid)) {
            $patient = $this->db->fetch("SELECT * FROM patients WHERE id = ?", [$idOrUhid]);
            if ($patient) return $patient;
        }
        // Try by UHID (string)
        $patient = $this->db->fetch("SELECT * FROM patients WHERE UHID = ?", [$idOrUhid]);
        return $patient;
    }

    public function updatePoints($UHID, $points) {
        try {
            $this->db->getConnection()->beginTransaction();

        // First update the patient's total points
        $this->db->execute(
            "UPDATE patients SET total_points = total_points + ? WHERE id = ?",
            [$points, $UHID]
        );

            // If points are being added, create a transaction record and points ledger entry
        if ($points > 0) {
                $transactionId = $this->db->insert('transactions', [
                    'UHID' => $UHID,
                    'Amount' => $points * 100, // Assuming 1 point per 100 KES
                    'ReffNo' => '', // No reference number in manual admin points add
                    'points_earned' => $points
                ]);

                // Add to points ledger
                $this->db->insert('points_ledger', [
                    'UHID' => $UHID,
                    'points' => $points,
                    'type' => 'earn',
                    'reference_id' => $transactionId,
                    'reference_type' => 'transaction',
                    'description' => "Points earned from transaction"
                ]);
            }

            // Update patient's tier
            $tierController = new TierController();
            $tierController->updatePatientTier($UHID);

            $this->db->getConnection()->commit();
            return true;
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("Error updating points: " . $e->getMessage());
            return false;
        }
    }

    public function getTiers() {
        $tierController = new TierController();
        return $tierController->getAllTiers();
    }

    public function getRewards() {
        try {
            $rewardController = new RewardController();
            return $rewardController->getAllRewards(false); // Get all rewards, including inactive ones
        } catch (Exception $e) {
            error_log("Error getting rewards: " . $e->getMessage());
            return [];
        }
    }

    public function getPointsLedger($UHID) {
        return $this->db->fetchAll(
            "SELECT * FROM points_ledger WHERE UHID = ? ORDER BY created_at DESC",
            [$UHID]
        );
    }

    public function getRedemptions($UHID) {
        $rewardController = new RewardController();
        return $rewardController->getPatientRedemptions($UHID);
    }

    public function getTransactions($page = 1, $limit = 10, $search = '', $uhid = '') {
        try {
            $offset = ($page - 1) * $limit;
            $params = [];
            $where = [];

            // Sanitize and build filters
            if (!empty($search)) {
                $search = htmlspecialchars(trim($search), ENT_QUOTES, 'UTF-8');
                $where[] = '(LOWER(p.name) LIKE ? OR LOWER(p.UHID) LIKE ?)';
                $params[] = '%' . strtolower($search) . '%';
                $params[] = '%' . strtolower($search) . '%';
            }
            if (!empty($uhid)) {
                $uhid = htmlspecialchars(trim($uhid), ENT_QUOTES, 'UTF-8');
                $where[] = 'p.UHID = ?';
                $params[] = $uhid;
            }

            $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

            $sql = "SELECT SQL_CALC_FOUND_ROWS
                        t.id,
                        t.transaction_date,
                        t.Amount,
                        t.points_earned,
                        t.ReffNo,
                        p.name as patient_name,
                        p.UHID as patient_uhid
                    FROM transactions t
                    FORCE INDEX (idx_transaction_date)
                    JOIN patients p ON t.UHID = p.id
                    $whereSql
                    ORDER BY t.transaction_date DESC
                    LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;

            $transactions = $this->db->fetchAll($sql, $params);
            $total = $this->db->fetch("SELECT FOUND_ROWS() as count")['count'];

            return [
                'transactions' => $transactions,
                'total' => $total,
                'currentPage' => $page,
                'totalPages' => ceil($total / $limit)
            ];
        } catch (Exception $e) {
            error_log("Error getting transactions: " . $e->getMessage());
            return [
                'transactions' => [],
                'total' => 0,
                'currentPage' => $page,
                'totalPages' => 0
            ];
        }
    }

    public function getAllRedemptions($page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT r.*, rw.name as reward_name, p.name as patient_name, p.UHID as patient_uhid
                FROM redemptions r
                JOIN rewards rw ON r.reward_id = rw.id
                JOIN patients p ON r.UHID = p.id
                ORDER BY r.created_at DESC
                LIMIT ? OFFSET ?";
        $redemptions = $this->db->fetchAll($sql, [$limit, $offset]);
        $total = $this->db->fetch("SELECT COUNT(*) as count FROM redemptions")['count'];
        return [
            'redemptions' => $redemptions,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => ceil($total / $limit)
        ];
    }
}