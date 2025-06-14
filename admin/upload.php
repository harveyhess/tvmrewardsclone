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
    <title>Upload CSV - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../src/assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <header>
            <h1>Upload CSV</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="patients.php">Patients</a>
                <a href="upload.php" class="active">Upload CSV</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <section class="upload-section">
                <h2>Upload Patient Data</h2>
                <form method="POST" action="process_upload.php" enctype="multipart/form-data" id="uploadForm">
                    <div class="form-group">
                        <label for="csv_file">Select CSV or Excel File:</label>
                        <input type="file" id="csv_file" name="csv_file" accept=".csv,.xlsx" required>
                        <div id="fileError" class="error" style="display: none;"></div>
                    </div>
                    <div class="form-group">
                        <label for="points_rate">Points Rate (points per KES):</label>
                        <input type="number" id="points_rate" name="points_rate" value="100" min="1" required>
                        <small>Example: 100 means 1 point per 100 KES spent</small>
                    </div>
                    <button type="submit" class="button" id="uploadButton">Upload and Process</button>
                </form>
            </section>

            <section class="instructions">
                <h3>CSV/Excel Format Requirements</h3>
                <p>The file must contain columns for:</p>
                <ul>
                    <li>UHID (or PatientID, Unique ID, etc.)</li>
                    <li>Name (or Patient Name, Full Name, etc.)</li>
                    <li>PhoneNumber (or Patient Number, Contact, etc.)</li>
                    <li>Amount (or Amount Paid, Payment, etc.)</li>
                    <li>ReffNo (or TransactionID, RefNo, Reference, etc.)</li>
                </ul>
                <p>Column order and case do not matter. Additional columns will be ignored. If any required column is missing, you will see a clear error message listing the headers found in your file.</p>
            </section>

            <a href="cron/process_transactions.php" class="button">Run Cron Job</a>

            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="flash-message <?php echo $_SESSION['flash_message']['type']; ?>">
                    <?php 
                    echo $_SESSION['flash_message']['message'];
                    unset($_SESSION['flash_message']);
                    ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('uploadForm');
        const fileInput = document.getElementById('csv_file');
        const fileError = document.getElementById('fileError');
        const uploadButton = document.getElementById('uploadButton');

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Reset error message
            fileError.style.display = 'none';
            
            // Validate file
            if (!fileInput.files.length) {
                fileError.textContent = 'Please select a CSV or Excel file';
                fileError.style.display = 'block';
                return;
            }

            const file = fileInput.files[0];
            if (!file.name.toLowerCase().endsWith('.csv') && !file.name.toLowerCase().endsWith('.xlsx')) {
                fileError.textContent = 'Only CSV or Excel (.xlsx) files are allowed';
                fileError.style.display = 'block';
                return;
            }

            // Disable button and show loading state
            uploadButton.disabled = true;
            uploadButton.textContent = 'Uploading...';

            // Submit the form
            this.submit();
        });
    });
    </script>
</body>
</html>