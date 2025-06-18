<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../src/controllers/AdminController.php';

session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

// Debug information
error_log("Session data: " . print_r($_SESSION, true));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Excel - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../src/assets/css/style.css">
    <style>
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .loading-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .upload-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .file-info {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            display: none;
        }
        .error-message {
            color: #dc3545;
            margin-top: 10px;
        }
        .success-message {
            color: #28a745;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <header>
            <h1>Upload Excel File</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="upload.php" class="active">Upload Excel</a>
                <a href="patients.php">Patients</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <div class="upload-form">
                <h2>Upload Collection Report</h2>
                <p>Upload your Excel file containing patient transactions. The file should have the following columns:</p>
                <ul>
                    <li>UHID (Patient ID)</li>
                    <li>Name (Patient Name)</li>
                    <li>Phone Number</li>
                    <li>Amount (Payment Amount)</li>
                    <li>ReffNo (Transaction Reference Number)</li>
                </ul>

                <form id="uploadForm" method="POST" action="process_upload.php" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="excel_file">Select Excel File:</label>
                        <input type="file" id="excel_file" name="excel_file" accept=".xlsx" required>
                    </div>

                    <div class="file-info" id="fileInfo"></div>
                    <div class="error-message" id="errorMessage"></div>
                    <div class="success-message" id="successMessage"></div>

                    <button type="submit" class="button">Upload and Process</button>
                </form>
            </div>
        </main>
    </div>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <p>Processing Excel file...</p>
            <p id="processingStatus">Please wait while we process your file.</p>
        </div>
    </div>

    <script>
        document.getElementById('excel_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const fileInfo = document.getElementById('fileInfo');
            const errorMessage = document.getElementById('errorMessage');
            
            if (file) {
                if (file.type !== 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                    errorMessage.textContent = 'Please select a valid Excel (.xlsx) file';
                    e.target.value = '';
                    fileInfo.style.display = 'none';
                    return;
                }
                
                fileInfo.innerHTML = `
                    <strong>Selected file:</strong> ${file.name}<br>
                    <strong>Size:</strong> ${(file.size / 1024).toFixed(2)} KB<br>
                    <strong>Type:</strong> ${file.type}
                `;
                fileInfo.style.display = 'block';
                errorMessage.textContent = '';
            } else {
                fileInfo.style.display = 'none';
            }
        });

        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const fileInput = document.getElementById('excel_file');
            const file = fileInput.files[0];
            
            if (!file) {
                document.getElementById('errorMessage').textContent = 'Please select a file to upload';
                return;
            }

            const formData = new FormData(this);
            const loadingOverlay = document.getElementById('loadingOverlay');
            const processingStatus = document.getElementById('processingStatus');
            
            loadingOverlay.style.display = 'flex';
            
            fetch('process_upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('successMessage').textContent = 
                        `Successfully processed ${data.processed} transactions. ${data.skipped} transactions were skipped.`;
                    document.getElementById('uploadForm').reset();
                    document.getElementById('fileInfo').style.display = 'none';
                } else {
                    document.getElementById('errorMessage').textContent = data.error || 'Error processing file';
                }
            })
            .catch(error => {
                document.getElementById('errorMessage').textContent = 'Error uploading file: ' + error.message;
            })
            .finally(() => {
                loadingOverlay.style.display = 'none';
            });
        });
    </script>
</body>
</html>