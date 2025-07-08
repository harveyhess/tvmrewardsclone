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

$controller = new AdminController();
$stats = $controller->getDashboardStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .stat-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            min-height: 120px;
        }
        .stat-card h3 {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }
        .stat-card p {
            color: #333;
            font-size: 2rem;
            font-weight: bold;
            margin: 0;
        }
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            text-align: center;
            transition: background 0.3s;
        }
        .button:hover {
            background: #28a745;
            color: white;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .loading.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <header class="d-flex justify-content-between align-items-center mb-4">
            <h1>Admin Dashboard</h1>
            <nav>
                <a href="dashboard.php" class="btn btn-primary active">Dashboard</a>
                <a href="patients.php" class="btn btn-outline-primary">Patients</a>
                <a href="transactions.php" class="btn btn-outline-primary">Transactions</a>
                <a href="tiers.php" class="btn btn-outline-primary">Tiers</a>
                <a href="rewards.php" class="btn btn-outline-primary">Rewards</a>
                <a href="redemptions.php" class="btn btn-outline-primary">Redemptions</a>
                <a href="logout.php" class="btn btn-outline-danger">Logout</a>
            </nav>
        </header>

        <main>
            <div class="row">
                <div class="col-md-6">
                    <div class="stat-card">
                        <h3>Total Patients</h3>
                        <p id="totalPatients"><?php echo number_format($stats['total_patients']); ?></p>
                    </div>
                </div>
            </div>

            <section class="mt-4">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="upload.php" class="button">Upload Excel</a>
                    <a href="patients.php" class="button">View Patients</a>
                    <a href="transactions.php" class="button">View Transactions</a>
                    <a href="tiers.php" class="button">Manage Tiers</a>
                    <a href="rewards.php" class="button">Manage Rewards</a>
                </div>
            </section>

            <div id="loading" class="loading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> mt-4">
                    <?php 
                    echo $_SESSION['flash_message']['message'];
                    unset($_SESSION['flash_message']);
                    ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to update dashboard stats
        function updateDashboardStats() {
            const loading = document.getElementById('loading');
            loading.classList.add('active');

            fetch('get_dashboard_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update total points
                        const pointsElement = document.getElementById('totalPoints');
                        if (pointsElement) {
                            pointsElement.textContent = new Intl.NumberFormat().format(data.stats.total_points);
                        } else {
                            const pointsCard = document.createElement('div');
                            pointsCard.className = 'col-md-6';
                            pointsCard.innerHTML = `
                                <div class="stat-card">
                                    <h3>Total Points</h3>
                                    <p id="totalPoints">${new Intl.NumberFormat().format(data.stats.total_points)}</p>
                                </div>
                            `;
                            document.querySelector('.row').appendChild(pointsCard);
                        }

                        // Update total patients
                        const patientsElement = document.getElementById('totalPatients');
                        if (patientsElement) {
                            patientsElement.textContent = new Intl.NumberFormat().format(data.stats.total_patients);
                        }

                        // Update recent transactions
                        const transactionsSection = document.getElementById('recentTransactions');
                        if (transactionsSection) {
                            const tbody = transactionsSection.querySelector('tbody');
                            tbody.innerHTML = data.stats.recent_transactions.map(t => `
                                <tr>
                                    <td>${t.patient_name}</td>
                                    <td>${t.ReffNo}</td>
                                    <td>${new Intl.NumberFormat().format(t.Amount)}</td>
                                    <td>${new Intl.NumberFormat().format(t.points_earned)}</td>
                                    <td>${new Date(t.created_at).toLocaleString()}</td>
                                </tr>
                            `).join('');
                        } else {
                            const transactionsCard = document.createElement('div');
                            transactionsCard.className = 'col-12 mt-4';
                            transactionsCard.innerHTML = `
                                <div class="card">
                                    <div class="card-header">
                                        <h3>Recent Transactions</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Patient</th>
                                                        <th>Reference</th>
                                                        <th>Amount</th>
                                                        <th>Points</th>
                                                        <th>Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="recentTransactions">
                                                    ${data.stats.recent_transactions.map(t => `
                                                        <tr>
                                                            <td>${t.patient_name}</td>
                                                            <td>${t.ReffNo}</td>
                                                            <td>${new Intl.NumberFormat().format(t.Amount)}</td>
                                                            <td>${new Intl.NumberFormat().format(t.points_earned)}</td>
                                                            <td>${new Date(t.created_at).toLocaleString()}</td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            `;
                            document.querySelector('main').appendChild(transactionsCard);
                        }

                        // Update points by tier
                        const tiersSection = document.getElementById('pointsByTier');
                        if (tiersSection) {
                            const tbody = tiersSection.querySelector('tbody');
                            tbody.innerHTML = data.stats.points_by_tier.map(t => `
                                <tr>
                                    <td>${t.tier_name || 'No Tier'}</td>
                                    <td>${new Intl.NumberFormat().format(t.patient_count)}</td>
                                    <td>${new Intl.NumberFormat().format(t.total_points)}</td>
                                </tr>
                            `).join('');
                        } else {
                            const tiersCard = document.createElement('div');
                            tiersCard.className = 'col-12 mt-4';
                            tiersCard.innerHTML = `
                                <div class="card">
                                    <div class="card-header">
                                        <h3>Points Distribution by Tier</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Tier</th>
                                                        <th>Patients</th>
                                                        <th>Total Points</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="pointsByTier">
                                                    ${data.stats.points_by_tier.map(t => `
                                                        <tr>
                                                            <td>${t.tier_name || 'No Tier'}</td>
                                                            <td>${new Intl.NumberFormat().format(t.patient_count)}</td>
                                                            <td>${new Intl.NumberFormat().format(t.total_points)}</td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            `;
                            document.querySelector('main').appendChild(tiersCard);
                        }
                    }
                })
                .catch(error => console.error('Error loading stats:', error))
                .finally(() => {
                    loading.classList.remove('active');
                });
        }

        // Update stats on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateDashboardStats();
            
            // Update stats every 5 minutes
            setInterval(updateDashboardStats, 3000000);
        });
    </script>
</body>
</html> 