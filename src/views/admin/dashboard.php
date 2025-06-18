<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/src/assets/css/style.css">
    <style>
        :root {
            --primary-color: #2ecc71;
            --primary-dark: #27ae60;
            --primary-light: #a9dfbf;
        }

        .navbar {
            background: var(--primary-color);
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .nav-brand {
            color: white;
            font-size: 1.5em;
            font-weight: bold;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .nav-menu a:hover {
            background: var(--primary-dark);
        }

        .nav-menu a.active {
            background: var(--primary-dark);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card h3 {
            color: #666;
            margin: 0 0 10px 0;
            font-size: 1.1em;
        }

        .stat-value {
            font-size: 2em;
            color: var(--primary-color);
            font-weight: bold;
            margin: 10px 0;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .action-button {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            text-align: center;
            text-decoration: none;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .action-button:hover {
            background: var(--primary-dark);
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../shared/loading.php'; ?>

    <div class="container">
        <nav class="navbar">
            <div class="nav-brand"><?php echo SITE_NAME; ?></div>
            <div class="nav-menu">
                <a href="/admin/dashboard.php" class="active">Dashboard</a>
                <a href="/admin/patients.php">Patients</a>
                <a href="/admin/transactions.php">Transactions</a>
                <a href="/admin/rewards.php">Rewards</a>
                <a href="/admin/tiers.php">Tiers</a>
                <a href="/admin/logout.php">Logout</a>
            </div>
            </nav>

        <div class="dashboard-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Patients</h3>
                    <div class="stat-value"><?php echo number_format($stats['total_patients']); ?></div>
                </div>
                <!-- Add more stat cards as needed -->
                </div>
               
            <div class="card">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="/admin/register_patient.php" class="action-button">Register New Patient</a>
                    <a href="/admin/upload.php" class="action-button">Upload Patient Data</a>
                    <a href="/admin/patients.php" class="action-button">View All Patients</a>
                    <a href="/admin/transactions.php" class="action-button">View Transactions</a>
                </div>
            </div>
                </div>
    </div>
</body>
</html>