# Patient Loyalty Rewards System - Railway Production Setup

## Overview
This application is a PHP-based patient loyalty rewards system. It supports uploading patient transactions via CSV and Excel files, manages patient tiers and rewards, and is ready for production deployment on Railway.

---

## Features
- Upload and parse both CSV and Excel files (using headers: UHID, PATIENTNAME, PATIENTNUMBER, Amount, ReffNo)
- Prevent duplicate transactions using ReffNo
- Admin can register new users (patients) directly
- Tier and reward management with automatic updates
- All secrets and configuration are managed via `.env`
- Ready for Railway hosting

---

## Prerequisites
- PHP 8.0+
- Composer
- MySQL database (already hosted, update credentials in `.env`)
- Railway account

---

## Environment Variables
Create a `.env` file in the project root with the following:

```
DB_HOST=your-db-host
DB_USER=your-db-user
DB_PASS=your-db-password
DB_NAME=your-db-name
SITE_URL=https://your-railway-app-url
SESSION_NAME=your-session-name
SESSION_LIFETIME=3600
PORT=2500
DEFAULT_POINTS_RATE=100
```

---

## Installation & Deployment

1. **Clone the repository**
   ```sh
   git clone <your-repo-url>
   cd <project-folder>
   ```

2. **Install dependencies**
   ```sh
   composer install
   ```

3. **Set up environment variables**
   - Copy `.env.example` to `.env` and fill in your Railway and DB credentials.

4. **Push to Railway**
   - Initialize a Railway project and connect your repo.
   - Set all environment variables in Railway dashboard as per your `.env`.
   - Deploy.

5. **Start the application**
   - Railway will run `start.sh` as the entry point.
   - The app will be available at your Railway-provided URL.

---

## Usage
- Admins can upload CSV/Excel files and register new patients from the dashboard.
- The system will parse files, prevent duplicate payments, and update points/tiers automatically.
- All secrets and DB credentials are managed via Railway environment variables.

---

## Notes
- Ensure your MySQL database is accessible from Railway.
- For any issues, check Railway logs and the `logs/` directory in the project.
- For local development, use `php start.php` or `./start.sh`.

---

## Support
For help, contact the project maintainer or open an issue in your repository.
