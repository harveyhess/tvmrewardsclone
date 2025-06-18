<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../src/controllers/AdminController.php';

session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: /admin/login.php');
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $uhid = trim($_POST['uhid'] ?? '');

    if (empty($name) || empty($uhid)) {
        $error = 'Name and UHID are required';
    } else {
        try {
        $db = Database::getInstance();
            
            // Check if UHID already exists
            $existing = $db->fetch(
                "SELECT id FROM patients WHERE UHID = ?",
                [$uhid]
            );

        if ($existing) {
                $error = 'UHID already exists';
        } else {
                // Insert new patient
                $result = $db->insert('patients', [
                    'UHID' => $uhid,
                'name' => $name,
                    'total_points' => 0
                ]);

                if ($result) {
                    $success = 'Patient registered successfully';
                } else {
                    $error = 'Failed to register patient';
                }
            }
        } catch (Exception $e) {
            $error = 'An error occurred: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Patient - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/src/assets/css/style.css">
    <style>
        :root {
            --primary-color: #2ecc71;
            --primary-dark: #27ae60;
            --primary-light: #a9dfbf;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            background: white;
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .close-modal {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .error { 
            color: #e74c3c;
            padding: 10px;
            background: #fde8e8;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .success { 
            color: #2ecc71;
            padding: 10px;
            background: #e8f8e8;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .button {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .button:hover {
            background: var(--primary-dark);
        }

        .open-modal {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 20px 0;
        }

        .open-modal:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../src/views/shared/loading.php'; ?>

    <div class="container">
        <nav class="navbar">
            <div class="nav-brand"><?php echo SITE_NAME; ?></div>
            <div class="nav-menu">
                <a href="/admin/dashboard.php">Dashboard</a>
                <a href="/admin/patients.php">Patients</a>
                <a href="/admin/register_patient.php" class="active">Register Patient</a>
                <a href="/admin/logout.php">Logout</a>
            </div>
            </nav>

        <div class="dashboard-content">
            <button class="open-modal" onclick="openModal()">Register New Patient</button>

            <div id="registerModal" class="modal">
                <div class="modal-content">
                    <span class="close-modal" onclick="closeModal()">&times;</span>
                    <h2>Register New Patient</h2>
                    
                    <?php if ($error): ?>
                        <div class="error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" onsubmit="showLoading()">
                    <div class="form-group">
                            <label for="name">Patient Name</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                            <label for="uhid">UHID</label>
                            <input type="text" id="uhid" name="uhid" required
                                   value="<?php echo isset($_POST['uhid']) ? htmlspecialchars($_POST['uhid']) : ''; ?>">
                        </div>

                        <button type="submit" class="button">Register Patient</button>
                    </form>
                </div>
                    </div>
                    </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('registerModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('registerModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('registerModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Show modal if there's an error or success message
        <?php if ($error || $success): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openModal();
        });
        <?php endif; ?>
    </script>
</body>
</html>
