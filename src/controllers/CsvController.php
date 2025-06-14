<?php
require_once __DIR__ . '/../../includes/Database.php';

class CsvController {
    private $db;
    private $uploadDir;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->uploadDir = __DIR__ . '/../../uploads/';
    }

    public function uploadCsv($file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('No file uploaded');
        }

        $fileName = basename($file['name']);
        $targetPath = $this->uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Failed to move uploaded file');
        }

        return [
            'success' => true,
            'message' => 'File uploaded successfully',
            'file_name' => $fileName
        ];
    }

    public function addCsvLink($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL format');
        }

        // Check if URL already exists
        $existing = $this->db->fetch(
            "SELECT id FROM transaction_csv_links WHERE url = ?",
            [$url]
        );

        if ($existing) {
            throw new Exception('This URL is already registered');
        }

        $id = $this->db->insert('transaction_csv_links', [
            'url' => $url,
            'status' => 'active'
        ]);

        return [
            'success' => true,
            'message' => 'CSV link added successfully',
            'id' => $id
        ];
    }

    public function getCsvLinks() {
        return $this->db->fetchAll(
            "SELECT * FROM transaction_csv_links ORDER BY created_at DESC"
        );
    }

    public function updateCsvLinkStatus($id, $status) {
        if (!in_array($status, ['active', 'inactive'])) {
            throw new Exception('Invalid status');
        }

        $this->db->execute(
            "UPDATE transaction_csv_links SET status = ? WHERE id = ?",
            [$status, $id]
        );

        return [
            'success' => true,
            'message' => 'CSV link status updated successfully'
        ];
    }

    public function deleteCsvLink($id) {
        $this->db->execute(
            "DELETE FROM transaction_csv_links WHERE id = ?",
            [$id]
        );

        return [
            'success' => true,
            'message' => 'CSV link deleted successfully'
        ];
    }

    public function fetchRemoteCsv($url) {
        $content = @file_get_contents($url);
        if ($content === false) {
            throw new Exception('Failed to fetch remote CSV');
        }

        $fileName = 'remote_' . md5($url) . '.csv';
        $targetPath = $this->uploadDir . $fileName;

        if (file_put_contents($targetPath, $content) === false) {
            throw new Exception('Failed to save remote CSV');
        }

        // Update last_fetched timestamp
        $this->db->execute(
            "UPDATE transaction_csv_links SET last_fetched = CURRENT_TIMESTAMP WHERE url = ?",
            [$url]
        );

        return $fileName;
    }
} 