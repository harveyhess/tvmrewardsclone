<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <header>
            <h1>Admin Dashboard</h1>
            <nav>
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="patients.php">Patients</a>
                <a href="upload.php">Upload Excel</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <section class="stats">
                <div class="stat-card">
                    <h3>Total Patients</h3>
                    <p><?php echo $stats['total_patients']; ?></p>
                </div>
               
            </section>

            <section class="actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="upload.php" class="button">Upload Excel</a>
                    <a href="patients.php" class="button">View Patients</a>
                   
                    <a href="register_patient.php" class="button">Register New Patient</a>
                </div>
            </section>

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

    <script src="../assets/js/admin.js"></script>
</body>
</html>