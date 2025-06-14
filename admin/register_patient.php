<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $UHID = trim($_POST['UHID'] ?? '');
    $name = trim($_POST['PATIENTNAME'] ?? '');
    $phone = trim($_POST['PATIENTNUMBER'] ?? '');
    if ($UHID && $name && $phone) {
        $db = Database::getInstance();
        $existing = $db->fetch("SELECT * FROM patients WHERE UHID = ?", [$UHID]);
        if ($existing) {
            $message = '<span class="error">Patient with this UHID already exists.</span>';
        } else {
            $db->insert('patients', [
                'UHID' => $UHID,
                'name' => $name,
                'phone_number' => $phone,
                'total_points' => 0,
                'qr_token' => bin2hex(random_bytes(16))
            ]);
            $message = '<span class="success">Patient registered successfully!</span>';
        }
    } else {
        $message = '<span class="error">All fields are required.</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register New Patient - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../src/assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <header>
            <h1>Register New Patient</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="patients.php">Patients</a>
                <a href="upload.php">Upload CSV/Excel</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>
        <main>
            <section class="register-section">
                <h2>Register Patient</h2>
                <?php if ($message) echo '<div class="flash-message">' . $message . '</div>'; ?>
                <form method="POST" action="" id="registerForm">
                    <div class="form-group">
                        <label for="UHID">UHID:</label>
                        <input type="text" id="UHID" name="UHID" required>
                    </div>
                    <div class="form-group">
                        <label for="PATIENTNAME">Patient Name:</label>
                        <input type="text" id="PATIENTNAME" name="PATIENTNAME" required>
                    </div>
                    <div class="form-group">
                        <label for="PATIENTNUMBER">Patient Number:</label>
                        <input type="text" id="PATIENTNUMBER" name="PATIENTNUMBER" required>
                    </div>
                    <button type="submit" class="button">Register</button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
