<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['is_admin'])) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: patient/dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="src/assets/css/style.css">
    <style>
        body {
            background: #e6ffcc;
        }
        .main-flex {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: stretch;
            min-height: 100vh;
            max-height: 100vh;
            max-width: 1200px;
            margin: 0 auto;
            overflow: hidden;
        }
        .branding-section {
            background: #d4ffb2;
            border-right: 2px solid #4bb543;
            min-width: 300px;
            max-width: 350px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
        }
        .brand-logo, #logoPlaceholder {
            width: 90px;
            height: 90px;
            margin-bottom: 16px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        #logoPlaceholder {
            background: #249c2c;
            color: #fff;
            border-radius: 50%;
            font-size: 2.2rem;
            font-weight: bold;
            position: static;
            box-sizing: border-box;
        }
        #logoContainer {
            width: 90px;
            height: 90px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 16px;
        }
        .brand-title {
            font-size: 1.8rem;
            color: #249c2c;
            font-weight: bold;
            margin-bottom: 6px;
            text-align: center;
            line-height: 1.2;
        }
        .brand-sub {
            color: #249c2c;
            font-size: 1rem;
            margin-bottom: 16px;
            text-align: center;
        }
        .content-section {
            flex: 1;
            background: #f6fff0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 32px 28px;
            overflow-y: auto;
            max-height: 100vh;
        }
        .info-section {
            background: #fff;
            border-radius: 8px;
            padding: 16px 16px;
            margin-bottom: 16px;
            color: #333;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .info-section h2 {
            color: #249c2c;
            font-size: 1rem;
            margin-bottom: 6px;
        }
        .info-section p {
            font-size: 0.95rem;
            line-height: 1.4;
        }
        .login-btn {
            display: inline-block;
            background: #249c2c;
            color: #fff;
            font-size: 1rem;
            font-weight: bold;
            padding: 10px 24px;
            border-radius: 6px;
            text-decoration: none;
            margin-top: 16px;
            box-shadow: 0 2px 8px rgba(36,156,44,0.10);
            transition: background 0.2s;
        }
        .login-btn:hover {
            background: #1e7e22;
        }
        .login-btns {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 16px;
        }
        #adminLoginBtnContainer {
            margin-top: 24px;
        }
        @media (max-width: 900px) {
            .main-flex {
                flex-direction: column;
                min-height: unset;
                max-height: unset;
                overflow: visible;
            }
            .branding-section {
                border-right: none;
                border-bottom: 2px solid #4bb543;
                min-width: unset;
                max-width: unset;
                padding: 24px 12px;
            }
            .content-section {
                padding: 24px 12px;
                overflow-y: visible;
                max-height: unset;
            }
            #logoContainer, .brand-logo, #logoPlaceholder {
                width: 70px;
                height: 70px;
                font-size: 1.4rem;
            }
            .brand-title {
                font-size: 1.6rem;
            }
            .brand-sub {
                font-size: 0.95rem;
            }
        }
        @media (min-width: 1400px) {
            .main-flex {
                max-width: 1400px;
            }
            .branding-section {
                min-width: 320px;
                max-width: 380px;
            }
        }
    </style>
</head>
<body>
    <div class="main-flex">
        <div class="branding-section">
            <div id="logoContainer">
                <img src="src/assets/images/veteran_logo.png" alt="Veteran Mission Hospitals Logo" class="brand-logo" id="brandLogo" onerror="this.style.display='none';document.getElementById('logoPlaceholder').style.display='flex';" />
                <div id="logoPlaceholder" style="display:none;">VMH</div>
            </div>
            <div class="brand-title">Welcome to the Veteran Mission Hospitals</div>
            <div class="brand-sub">Loyalty Rewards Program</div>
        </div>
        <div class="content-section">
            <div class="info-section">
                <h2>What is our Loyalty Program?</h2>
                <p>Our loyalty rewards program is designed to thank our valued patients for choosing Veteran Mission Hospitals for their healthcare needs. Every visit earns you points that can be redeemed for exciting rewards and benefits.</p>
            </div>
            <div class="info-section">
                <h2>Why Join?</h2>
                <p>Join our loyalty program to earn points on every visit, receive exclusive benefits, and enjoy special rewards. The more you visit, the more you earn - it's our way of saying thank you for your trust in us.</p>
            </div>
            <div class="info-section">
                <h2>What Can You Redeem?</h2>
                <p>Redeem your points for discounts on services, priority appointments, health check-ups, wellness packages, and exclusive hospital benefits. Your loyalty deserves to be rewarded!</p>
            </div>
            <div class="login-btns" style="display:flex;gap:18px;flex-wrap:wrap;margin-top:18px;">
                <a href="patient/login.php" class="login-btn">For patients, please log in here</a>
                <a href="admin/login.php" class="login-btn" style="background:#155724;">For admins, please log in here</a>
            </div>
        </div>
    </div>
</body>
</html> 