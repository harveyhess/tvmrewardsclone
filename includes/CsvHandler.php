<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../src/controllers/TierController.php';
require_once __DIR__ . '/../vendor/autoload.php'; // For PhpSpreadsheet

class CsvHandler {
    private $db;
    private $errors = [];
    private $processedRows = 0;
    private $skippedRows = 0;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function processFile($file) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext === 'csv') {
            return $this->processCsv($file);
        } elseif ($ext === 'xlsx') {
            return $this->processExcel($file);
        } else {
            throw new Exception('Unsupported file type');
        }
    }

    // Helper: map headers to required fields using fuzzy matching
    private function mapHeaders($headers) {
        $map = [];
        $headerVariants = [
            'uhid' => ['uhid', 'patientid', 'uniqueid', 'cash002', 'billno'],
            'name' => ['name', 'patientname', 'pname', 'fullname', 'patient name'],
            'phonenumber' => ['phonenumber', 'patientnumber', 'phone', 'contact', 'receiptno'],
            'amount' => ['amount', 'amountpaid', 'payment', 'amount paid'],
            'reffno' => ['reffno', 'transactionid', 'refno', 'reference', 'receiptno'],
        ];
        foreach ($headers as $i => $header) {
            $h = strtolower(preg_replace('/[^a-z0-9]/', '', $header));
            foreach ($headerVariants as $field => $variants) {
                foreach ($variants as $variant) {
                    if ($h === strtolower(preg_replace('/[^a-z0-9]/', '', $variant))) {
                        $map[$field] = $i;
                    }
                }
            }
        }
        return $map;
    }

    public function processCsv($file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Invalid file upload');
        }

        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('File size exceeds limit');
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            throw new Exception('Could not open file');
        }

        // Read headers
        $headers = fgetcsv($handle, 0, ',', '"', '\\');
        if (!$headers) {
            throw new Exception('Invalid CSV format');
        }
        $headerMap = $this->mapHeaders($headers);
        $requiredFields = ['uhid', 'name', 'phonenumber', 'amount', 'reffno'];
        $missingFields = array_diff($requiredFields, array_keys($headerMap));
        if (!empty($missingFields)) {
            throw new Exception('Missing required fields: ' . implode(', ', $missingFields) . '. Found headers: ' . implode(', ', $headers));
        }
        $pointsRate = $this->getPointsRate();
        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $data = [];
            foreach ($headerMap as $field => $idx) {
                $data[$field] = $row[$idx] ?? '';
            }
            $this->processRow(array_values($data), array_keys($data), $pointsRate);
        }

        fclose($handle);

        return [
            'processed' => $this->processedRows,
            'skipped' => $this->skippedRows,
            'errors' => $this->errors
        ];
    }

    public function processExcel($file) {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        if (empty($rows) || count($rows) < 2) {
            throw new Exception('Excel file is empty or missing data rows');
        }
        // Find the header row dynamically
        $headerRowIndex = null;
        $headerMap = [];
        $requiredFields = ['uhid', 'name', 'phonenumber', 'amount', 'reffno'];
        $headerVariants = [
            'uhid' => ['uhid', 'patientid', 'uniqueid', 'cash002', 'billno'],
            'name' => ['name', 'patientname', 'pname', 'fullname', 'patient name'],
            'phonenumber' => ['phonenumber', 'patientnumber', 'phone', 'contact', 'receiptno'],
            'amount' => ['amount', 'amountpaid', 'payment', 'amount paid'],
            'reffno' => ['reffno', 'transactionid', 'refno', 'reference', 'receiptno'],
        ];
        foreach ($rows as $i => $row) {
            $normalized = array_map(function($h) {
                if ($h === null || $h === '') return '';
                return preg_replace('/[^a-z0-9]/', '', strtolower($h));
            }, $row);
            $found = 0;
            $map = [];
            foreach ($requiredFields as $field) {
                foreach ($headerVariants[$field] as $variant) {
                    $idx = array_search(preg_replace('/[^a-z0-9]/', '', strtolower($variant)), $normalized);
                    if ($idx !== false) {
                        $map[$field] = $idx;
                        $found++;
                        break;
                    }
                }
            }
            if ($found === count($requiredFields)) {
                $headerRowIndex = $i;
                $headerMap = $map;
                break;
            }
        }
        if ($headerRowIndex === null) {
            $allHeaders = [];
            foreach ($rows as $row) {
                $allHeaders[] = implode(' | ', $row);
            }
            throw new Exception('Missing required fields: ' . implode(', ', $requiredFields) . '. No valid header row found. Headers scanned: ' . implode(' || ', $allHeaders));
        }
        $pointsRate = $this->getPointsRate();
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $data = [];
            foreach ($headerMap as $field => $idx) {
                $data[$field] = isset($row[$idx]) ? trim((string)$row[$idx]) : '';
            }
            $this->processRow(array_values($data), array_keys($data), $pointsRate);
        }
        return [
            'processed' => $this->processedRows,
            'skipped' => $this->skippedRows,
            'errors' => $this->errors
        ];
    }

    private function processRow($row, $headers, $pointsRate) {
        // Skip if row has different number of columns
        if (count($row) !== count($headers)) {
            $this->skippedRows++;
            $this->errors[] = "Row skipped: Invalid number of columns";
            return;
        }
        // Create associative array from row
        $data = array_combine($headers, $row);
        // Clean and validate data
        $data = array_map('trim', $data);
        // Defensive: uppercase all keys for consistent access
        $dataUC = [];
        foreach ($data as $k => $v) {
            $dataUC[strtoupper($k)] = $v;
        }
        // Validate required fields
        if (empty($dataUC['UHID']) || empty($dataUC['NAME']) || 
            empty($dataUC['PHONENUMBER']) || !is_numeric($dataUC['AMOUNT']) || (empty($dataUC['REFFNO']) && $dataUC['AMOUNT'] !== '')) {
            $this->skippedRows++;
            $this->errors[] = "Row skipped: Missing or invalid required fields (" . json_encode($dataUC) . ")";
            return;
        }
        // Clean phone number (remove non-numeric characters)
        $dataUC['PHONENUMBER'] = preg_replace('/[^0-9]/', '', $dataUC['PHONENUMBER']);
        // Calculate points
        $points = floor($dataUC['AMOUNT'] / $pointsRate);
        // Check for duplicate transaction by ReffNo
        $exists = $this->db->fetch("SELECT id FROM transactions WHERE UHID = ? AND ReffNo = ?", [$dataUC['UHID'], $dataUC['REFFNO']]);
        if ($exists) {
            $this->skippedRows++;
            $this->errors[] = "Row skipped: Duplicate transaction (ReffNo) for UHID {$dataUC['UHID']}";
            return;
        }
        try {
            // Start transaction
            $this->db->getConnection()->beginTransaction();
            // Check if patient exists
            $patient = $this->db->fetch(
                "SELECT id FROM patients WHERE UHID = ?",
                [$dataUC['UHID']]
            );
            if (!$patient) {
                // Create new patient
                $UHID = $this->db->insert('patients', [
                    'UHID' => $dataUC['UHID'],
                    'name' => $dataUC['NAME'],
                    'phone_number' => $dataUC['PHONENUMBER'],
                    'total_points' => $points,
                    'qr_token' => bin2hex(random_bytes(16))
                ]);
            } else {
                // Update existing patient
                $this->db->update(
                    'patients',
                    ['total_points' => $points],
                    'id = ?',
                    [$patient['id']]
                );
                $UHID = $patient['id'];
            }
            // Record transaction
            $this->db->insert('transactions', [
                'UHID' => $UHID,
                'Amount' => $dataUC['AMOUNT'],
                'ReffNo' => $dataUC['REFFNO'] ?? '',
                'points_earned' => $points
            ]);
            // Update patient's tier
            $tierController = new TierController(false);
            $tierController->updatePatientTier($UHID);
            $this->db->getConnection()->commit();
            $this->processedRows++;
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            $this->skippedRows++;
            $this->errors[] = "Error processing row: " . $e->getMessage();
        }
    }

    private function getPointsRate() {
        $settings = $this->db->fetch("SELECT points_rate FROM points_settings ORDER BY id DESC LIMIT 1");
        return $settings ? $settings['points_rate'] : DEFAULT_POINTS_RATE;
    }

    private function processTransaction($dataUC, $points) {
        try {
            // Start transaction
            $this->db->getConnection()->beginTransaction();

            // Check for duplicate transaction first
            $existingTransaction = $this->db->fetch(
                "SELECT id FROM transactions 
                 WHERE UHID = ? AND ReffNo = ? AND Amount = ? AND transaction_date = ?",
                [$dataUC['UHID'], $dataUC['REFFNO'] ?? '', $dataUC['AMOUNT'], $dataUC['DATE'] ?? date('Y-m-d H:i:s')]
            );

            if ($existingTransaction) {
                $this->db->getConnection()->rollBack();
                $this->skippedRows++;
                return; // Skip duplicate transaction
            }

            // Check if patient exists
            $patient = $this->db->fetch(
                "SELECT id, total_points FROM patients WHERE UHID = ?",
                [$dataUC['UHID']]
            );

            if (!$patient) {
                // Create new patient
                $UHID = $this->db->insert('patients', [
                    'UHID' => $dataUC['UHID'],
                    'name' => $dataUC['NAME'],
                    'phone_number' => $dataUC['PHONENUMBER'],
                    'total_points' => $points,
                    'qr_token' => bin2hex(random_bytes(16))
                ]);
            } else {
                // Update existing patient's points
                $newPoints = $patient['total_points'] + $points;
                $this->db->update(
                    'patients',
                    ['total_points' => $newPoints],
                    'id = ?',
                    [$patient['id']]
                );
                $UHID = $patient['id'];
            }

            // Record transaction with transaction date
            $this->db->insert('transactions', [
                'UHID' => $UHID,
                'Amount' => $dataUC['AMOUNT'],
                'ReffNo' => $dataUC['REFFNO'] ?? '',
                'points_earned' => $points,
                'transaction_date' => $dataUC['DATE'] ?? date('Y-m-d H:i:s')
            ]);

            // Update patient's tier
            $tierController = new TierController(false);
            $tierController->updatePatientTier($UHID);

            $this->db->getConnection()->commit();
            $this->processedRows++;
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            $this->skippedRows++;
            $this->errors[] = "Error processing row: " . $e->getMessage();
            error_log("Transaction processing error: " . $e->getMessage());
        }
    }
}