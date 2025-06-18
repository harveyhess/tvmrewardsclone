<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../src/controllers/TierController.php';
require_once __DIR__ . '/../vendor/autoload.php'; // For PhpSpreadsheet

class CsvHandler {
    private $db;
    private $processedRows = 0;
    private $skippedRows = 0;
    private $errors = [];
    private $batchSize = 100; // Process rows in batches

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function processFile($file) {
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($fileExt === 'xlsx' || $fileExt === 'xls') {
            return $this->processExcel($file);
        } else {
            throw new Exception('Only Excel (.xlsx or .xls) files are allowed');
        }
    }

    public function processExcel($file) {
        try {
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($fileExt === 'xlsx' ? 'Xlsx' : 'Xls');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Get all data
            $data = $worksheet->toArray();
            if (empty($data)) {
                throw new Exception("No data found in Excel file");
            }

            // Find the header row by scanning through rows
            $headerRowIndex = $this->findHeaderRow($data);
            if ($headerRowIndex === -1) {
                throw new Exception("Could not find header row in the Excel file. Please ensure the file contains columns for: UHID, Patient Name, Reference Number, and Amount");
            }

            error_log("Found headers at row index: " . $headerRowIndex);

            // Get headers from the found row
            $headers = array_map(function($header) {
                $header = trim($header ?? '');
                error_log("Processing header: '$header'");
                return $header;
            }, $data[$headerRowIndex]);

            // Remove any empty headers
            $headers = array_filter($headers, function($header) {
                return !empty($header);
            });

            if (empty($headers)) {
                throw new Exception("No valid headers found in the Excel file. Please ensure the file contains column headers.");
            }

            // Define required columns and their possible variations
            $requiredColumns = [
                'UHID' => [
                    'uhid', 'id', 'patient id', 'patientid', 'UHID', 'ID', 'PATIENT ID', 'PATIENTID',
                    'patient id', 'patientid', 'patient_id', 'patient-id', 'patientid', 'patient-id',
                    'patient id number', 'patient id no', 'patient id no.', 'patient id number',
                    'patientid', 'patient-id', 'patient_id'
                ],
                'PNAME' => [
                    'pname', 'patient name', 'name', 'patientname', 'PNAME', 'PATIENT NAME', 'NAME', 'PATIENTNAME',
                    'patient name', 'patientname', 'patient_name', 'patient-name', 'patient name',
                    'full name', 'fullname', 'full_name', 'full-name', 'patient full name',
                    'name', 'patient name', 'patient-name', 'patient_name'
                ],
                'REFFNO' => [
                    'reffno', 'reference', 'ref no', 'refno', 'transaction id', 'REFFNO', 'REFERENCE', 'REF NO', 'REFNO', 'TRANSACTION ID',
                    'reference no', 'reference number', 'reference no.', 'reference number',
                    'ref', 'ref.', 'ref no.', 'ref number', 'transaction reference',
                    'transaction ref', 'transaction ref no', 'transaction reference no',
                    'refno', 'ref no', 'reference no', 'reference number'
                ],
                'AMOUNT' => [
                    'amount', 'amt', 'transaction amount', 'value', 'AMOUNT', 'AMT', 'TRANSACTION AMOUNT', 'VALUE',
                    'payment amount', 'payment', 'payment value', 'payment amt',
                    'transaction value', 'transaction amt', 'transaction amount',
                    'paid amount', 'paid amt', 'paid value', 'paid',
                    'amount', 'payment amount', 'transaction amount'
                ]
            ];

            // Map headers to required columns
            $columnMap = [];
            $foundHeaders = [];
            foreach ($requiredColumns as $required => $variations) {
                foreach ($variations as $variation) {
                    $index = array_search(strtolower($variation), array_map('strtolower', $headers));
                    if ($index !== false) {
                        $columnMap[$required] = $index;
                        $foundHeaders[] = $headers[$index];
                        error_log("Matched header '$variation' to column '$required' at index $index");
                        break;
                    }
                }
            }

            // Validate required columns are found
            $missingColumns = array_diff(array_keys($requiredColumns), array_keys($columnMap));
            if (!empty($missingColumns)) {
                $errorMessage = "Missing required columns: " . implode(', ', $missingColumns) . "\n";
                $errorMessage .= "Found headers: " . implode(', ', $headers) . "\n";
                $errorMessage .= "Please ensure your Excel file contains columns for: UHID, Patient Name, Reference Number, and Amount\n";
                $errorMessage .= "Raw headers from file: " . print_r($data[$headerRowIndex], true);
                throw new Exception($errorMessage);
            }

            // Log successful column mapping
            error_log("Successfully mapped columns: " . print_r($columnMap, true));
            error_log("Found headers: " . implode(', ', $foundHeaders));
            
            // Process rows starting from the row after headers
            $batch = [];
            $pointsRate = $this->getPointsRate();
            
            for ($i = $headerRowIndex + 1; $i < count($data); $i++) {
                $row = $data[$i];
                $rowData = [];
                
                // Map row data using column mapping
                foreach ($columnMap as $column => $index) {
                    $rowData[$column] = isset($row[$index]) ? trim($row[$index] ?? '') : '';
                }
                
                // Skip empty rows
                if (empty(array_filter($rowData))) {
                    continue;
                }

                // Validate row data
                if (empty($rowData['UHID']) || empty($rowData['REFFNO']) || !is_numeric($rowData['AMOUNT'])) {
                    $this->skippedRows++;
                    $this->errors[] = "Row " . ($i + 1) . " skipped: Invalid data - UHID: {$rowData['UHID']}, ReffNo: {$rowData['REFFNO']}, Amount: {$rowData['AMOUNT']}";
                    continue;
                }
                
                $batch[] = $rowData;
                
                if (count($batch) >= $this->batchSize) {
                    $this->processBatch($batch, $pointsRate);
                    $batch = [];
                }
            }
            
            // Process remaining rows
            if (!empty($batch)) {
                $this->processBatch($batch, $pointsRate);
            }

            return [
                'processed' => $this->processedRows,
                'skipped' => $this->skippedRows,
                'errors' => $this->errors
            ];
            
        } catch (Exception $e) {
            error_log("Error processing Excel file: " . $e->getMessage());
            throw new Exception("Error processing Excel file: " . $e->getMessage());
        }
    }

    private function findHeaderRow($data) {
        // Define possible header variations
        $headerVariations = [
            'uhid' => ['uhid', 'id', 'patient id', 'patientid', 'patient id number', 'patient id no'],
            'name' => ['name', 'patient name', 'pname', 'full name', 'patient full name'],
            'reffno' => ['reffno', 'reference', 'ref no', 'refno', 'transaction id', 'reference no', 'reference number'],
            'amount' => ['amount', 'amt', 'payment amount', 'transaction amount', 'paid amount']
        ];

        // Scan through rows to find headers
        for ($i = 0; $i < min(20, count($data)); $i++) { // Check first 20 rows
            // Safely handle null values in the row
            $row = array_map(function($value) {
                return $value !== null ? strtolower(trim($value)) : '';
            }, $data[$i]);
            
            $row = array_filter($row); // Remove empty values
            
            if (empty($row)) {
                error_log("Row $i is empty, skipping");
                continue;
            }

            error_log("Checking row $i for headers: " . implode(', ', $row));
            
            // Count how many header variations are found in this row
            $matches = 0;
            $foundHeaders = [];
            foreach ($headerVariations as $type => $variations) {
                foreach ($variations as $variation) {
                    if (in_array($variation, $row)) {
                        $matches++;
                        $foundHeaders[] = "$type: $variation";
                        break;
                    }
                }
            }

            // If we found at least 3 matching headers, this is likely our header row
            if ($matches >= 3) {
                error_log("Found potential header row at index $i with $matches matches");
                error_log("Found headers: " . implode(', ', $foundHeaders));
                return $i;
            }
        }

        error_log("No header row found in first 20 rows");
        return -1; // No header row found
    }

    private function processBatch($batch, $pointsRate) {
        foreach ($batch as $rowData) {
            try {
                error_log("Processing row data: " . print_r($rowData, true));

                // Start transaction for this row
                $this->db->beginTransaction();

                try {
                    // 1. Check for existing transaction first (most restrictive)
                $existingTransaction = $this->db->fetch(
                        "SELECT t.id, p.id as patient_id, p.total_points, p.points_version 
                     FROM transactions t 
                         JOIN patients p ON t.UHID = p.id 
                         WHERE t.ReffNo = ? FOR UPDATE",
                    [$rowData['REFFNO']]
                );

                if ($existingTransaction) {
                    $this->skippedRows++;
                        error_log("Skipping - ReffNo {$rowData['REFFNO']} exists globally");
                        $this->db->commit();
                    continue;
                }

                    // 2. Check for existing patient
                    $patient = $this->db->fetch(
                        "SELECT id, name, total_points, points_version FROM patients WHERE UHID = ? FOR UPDATE",
                        [$rowData['UHID']]
                    );

                    if ($patient) {
                        error_log("Found existing patient with UHID: {$rowData['UHID']}");
                        
                        // Calculate points
                        $amount = floatval($rowData['AMOUNT']);
                        $points = floor($amount / $pointsRate);
                        error_log("Calculated points: $points for amount: $amount (rate: $pointsRate)");

                        // Update points with version check
                        $newPoints = $patient['total_points'] + $points;
                        $newVersion = $patient['points_version'] + 1;
                        
                        error_log("Updating patient points: {$patient['total_points']} + $points = $newPoints (version: $newVersion)");
                        
                        // Update patient points first
                        $updated = $this->db->update(
                            'patients',
                            [
                                'total_points' => $newPoints,
                                'points_version' => $newVersion,
                                'updated_at' => date('Y-m-d H:i:s')
                            ],
                            'id = ? AND points_version = ?',
                            [$patient['id'], $patient['points_version']]
                        );
                        
                        if (!$updated) {
                            throw new Exception("Points version mismatch - concurrent update detected");
                        }

                        // Verify the update
                        $updatedPatient = $this->db->fetch(
                            "SELECT total_points, points_version FROM patients WHERE id = ?",
                            [$patient['id']]
                        );

                        if (!$updatedPatient || $updatedPatient['total_points'] != $newPoints) {
                            throw new Exception("Failed to verify points update");
                        }
                        
                        // Record transaction
                        $transactionId = $this->db->insert('transactions', [
                            'UHID' => $patient['id'],
                            'ReffNo' => $rowData['REFFNO'],
                            'Amount' => $amount,
                            'points_earned' => $points,
                            'points_version' => $newVersion,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                        
                        if (!$transactionId) {
                            throw new Exception("Failed to record transaction");
                        }
                        
                        error_log("Recorded transaction with ID: " . $transactionId);
                        $this->processedRows++;
                        
                    } else {
                        error_log("No existing patient found for UHID: {$rowData['UHID']}");
                        
                        // Calculate points for new patient
                        $amount = floatval($rowData['AMOUNT']);
                        $points = floor($amount / $pointsRate);
                        error_log("Calculated points: $points for amount: $amount (rate: $pointsRate)");

                    // Create new patient
                    error_log("Creating new patient with UHID: " . $rowData['UHID']);
                    try {
                        $patientId = $this->db->insert('patients', [
                            'UHID' => $rowData['UHID'],
                            'name' => $rowData['PNAME'],
                            'total_points' => $points,
                                'points_version' => 1,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
                    
                    if (!$patientId) {
                        throw new Exception("Failed to create new patient");
                    }

                            // Verify the patient creation
                            $newPatient = $this->db->fetch(
                                "SELECT total_points, points_version FROM patients WHERE id = ?",
                                [$patientId]
                            );

                            if (!$newPatient || $newPatient['total_points'] != $points) {
                                throw new Exception("Failed to verify new patient points");
                            }
                    
                    error_log("Created new patient with ID: " . $patientId);
                    error_log("Initial points for new patient: " . $points);
                    
                    // Record transaction
                    $transactionId = $this->db->insert('transactions', [
                        'UHID' => $patientId,
                        'ReffNo' => $rowData['REFFNO'],
                        'Amount' => $amount,
                        'points_earned' => $points,
                        'points_version' => 1,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    if (!$transactionId) {
                        throw new Exception("Failed to record transaction");
                    }
                    
                    error_log("Recorded transaction with ID: " . $transactionId);
                    $this->processedRows++;
                            
                        } catch (Exception $e) {
                            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                                // Double check if patient exists now
                                $patient = $this->db->fetch(
                                    "SELECT id, name, total_points, points_version FROM patients WHERE UHID = ? FOR UPDATE",
                                    [$rowData['UHID']]
                                );

                if ($patient) {
                                    error_log("Patient was created concurrently, processing as existing patient");
                                    
                                    // Calculate points
                                    $amount = floatval($rowData['AMOUNT']);
                                    $points = floor($amount / $pointsRate);
                                    error_log("Calculated points: $points for amount: $amount (rate: $pointsRate)");

                                    // Update points with version check
                    $newPoints = $patient['total_points'] + $points;
                    $newVersion = $patient['points_version'] + 1;
                    
                                    error_log("Updating patient points: {$patient['total_points']} + $points = $newPoints (version: $newVersion)");
                    
                                    // Update patient points first
                    $updated = $this->db->update(
                        'patients',
                        [
                            'total_points' => $newPoints,
                                            'points_version' => $newVersion,
                                            'updated_at' => date('Y-m-d H:i:s')
                                        ],
                                        'id = ? AND points_version = ?',
                                        [$patient['id'], $patient['points_version']]
                                    );
                                    
                                    if (!$updated) {
                                        throw new Exception("Points version mismatch - concurrent update detected");
                                    }

                                    // Verify the update
                                    $updatedPatient = $this->db->fetch(
                                        "SELECT total_points, points_version FROM patients WHERE id = ?",
                        [$patient['id']]
                    );
                    
                                    if (!$updatedPatient || $updatedPatient['total_points'] != $newPoints) {
                                        throw new Exception("Failed to verify points update");
                    }
                    
                    // Record transaction
                    $transactionId = $this->db->insert('transactions', [
                        'UHID' => $patient['id'],
                        'ReffNo' => $rowData['REFFNO'],
                        'Amount' => $amount,
                        'points_earned' => $points,
                        'points_version' => $newVersion,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    if (!$transactionId) {
                        throw new Exception("Failed to record transaction");
                    }
                    
                    error_log("Recorded transaction with ID: " . $transactionId);
                    $this->processedRows++;
                                } else {
                                    error_log("Patient already exists, skipping row");
                                    $this->skippedRows++;
                                }
                            } else {
                                throw $e;
                            }
                        }
                    }
                    
                    // Commit the transaction for this row
                    $this->db->commit();
                    error_log("Successfully processed transaction");
                    
                } catch (Exception $e) {
                    // Rollback on any error
                    $this->db->rollback();
                    throw $e;
                }
                
            } catch (Exception $e) {
                error_log("Error processing row: " . $e->getMessage());
                error_log("Row data that caused error: " . print_r($rowData, true));
                $this->errors[] = "Error processing row: " . $e->getMessage();
            }
        }
    }

    private function getPointsRate() {
        $settings = $this->db->fetch("SELECT points_rate FROM points_settings ORDER BY id DESC LIMIT 1");
        return $settings ? $settings['points_rate'] : 100; // Default to 100 if no setting found
    }
}