# Patient Loyalty Rewards System

A full-stack web application for managing patient loyalty rewards in a hospital setting. The system allows administrators to import patient data, manage points, and generate QR codes for patient access.

## Features

- Admin dashboard with secure login
- CSV import for patient data
- Points management system
- QR code generation for patient access
- Patient dashboard with transaction history
- Responsive design

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP (recommended for local development)
- Web server (Apache/Nginx)

## Installation

1. Clone the repository to your web server's document root:
   ```bash
   git clone <repository-url>
   cd patient-loyalty
   ```

2. Create a new MySQL database:
   ```sql
   CREATE DATABASE patient_loyalty;
   ```

3. Update the database configuration in `config/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', '');
   define('DB_PASS', '');
   define('DB_NAME', 'patient_loyalty');
   ```

4. Run the installation script:
   ```bash
   php install.php
   ```

5. Set up your web server:
   - For Apache, ensure mod_rewrite is enabled
   - Point your document root to the project directory
   - Ensure the `uploads` directory is writable

## Default Login Credentials

### Admin
- Username: admin
- Password: admin123

**Important:** Change these credentials after first login.

## Usage

### Admin Access
1. Navigate to `/admin/login.php`
2. Log in with admin credentials
3. Use the dashboard to:
   - Upload patient data via CSV
   - View and manage patient points
   - Generate QR codes
   - Export patient data

### Patient Access
1. Patients can access their dashboard via:
   - QR code scan
   - Login with UHID or Phone Number
2. View points and transaction history

## CSV Import Format

The system expects CSV files with the following columns:
- UHID (required)
- Name (required)
- PhoneNumber (required)
- amount (required, numeric)

Additional columns will be ignored.

## Security Notes

- All user input is sanitized
- Passwords are hashed
- SQL injection prevention
- Secure session handling
- File upload restrictions

## Directory Structure

```
patient-loyalty/
├── admin/              # Admin interface files
├── config/            # Configuration files
├── includes/          # Core classes and utilities
├── patient/           # Patient interface files
├── src/
│   ├── assets/       # CSS, JS, and images
│   ├── controllers/  # Controller classes
│   ├── models/       # Model classes
│   └── views/        # View templates
├── uploads/          # CSV upload directory
├── install.php       # Installation script
└── README.md         # This file
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details. # tvmrewardsclone
