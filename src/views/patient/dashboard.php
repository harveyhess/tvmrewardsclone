<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/src/assets/css/style.css">
    <style>
        :root {
            --primary-color: #2ecc71;
            --primary-dark: #27ae60;
            --primary-light: #a9dfbf;
        }
        
        .tier-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 1.3em;
            color: #27ae60 !important;
            background: #eafaf1;
            border: 2px solid #27ae60;
        }
        
        .tier-bronze { 
            background: linear-gradient(135deg, #cd7f32, #b87333);
            color: white;
        }
        
        .tier-silver { 
            background: linear-gradient(135deg, #c0c0c0, #a9a9a9);
            color: white;
        }
        
        .tier-gold { 
            background: linear-gradient(135deg, #ffd700, #daa520);
            color: black;
        }
        
        .tier-platinum { 
            background: linear-gradient(135deg, #e5e4e2, #b4b4b4);
            color: black;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .points {
            font-size: 2em;
            color: var(--primary-color);
            font-weight: bold;
            margin: 10px 0;
        }

        .uhid {
            font-family: monospace;
            background: #f5f5f5;
            padding: 8px;
            border-radius: 4px;
            margin: 10px 0;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f5f5f5;
            font-weight: bold;
        }

        tr:hover {
            background: #f9f9f9;
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
    </style>
</head>
<body>
    <?php include __DIR__ . '/../shared/loading.php'; ?>

    <div class="container">
        <nav class="navbar">
            <div class="nav-brand"><?php echo SITE_NAME; ?></div>
            <div class="nav-menu">
                <span>Welcome, <?php echo htmlspecialchars($patient['name']); ?></span>
                <a href="/patient/dashboard.php" class="active">Dashboard</a>
                <a href="/patient/transactions.php">Transactions</a>
                <a href="/patient/rewards.php">Rewards</a>
                <a href="/patient/logout.php">Logout</a>
            </div>
            </nav>

        <div class="dashboard-content">
            <div class="card">
                <h2>Total Points</h2>
                <div class="points"><?php echo number_format($patient['total_points']); ?></div>
                
                <h2>Current Tier</h2>
                <div class="tier-badge tier-<?php echo strtolower(str_replace(' ', '-', $patient['tier_name'])); ?>">
                    <?php echo htmlspecialchars($patient['tier_name']); ?>
                </div>

                <h2>UHID</h2>
                <div class="uhid"><?php echo htmlspecialchars($patient['UHID']); ?></div>
            </div>

            <div class="card">
                <h2>Recent Transactions</h2>
                <?php if (empty($transactions)): ?>
                    <p>No recent transactions</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                                    <th>Amount</th>
                                    <th>Points</th>
                                    <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                        <td><?php echo date('Y-m-d', strtotime($transaction['transaction_date'])); ?></td>
                                        <td>₹<?php echo number_format($transaction['Amount'], 2); ?></td>
                                        <td><?php echo number_format($transaction['points_earned']); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['ReffNo']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 