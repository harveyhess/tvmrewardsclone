#!/bin/bash
set -e

# If DATABASE_URL is set, skip waiting for local MySQL
if [ -n "$DATABASE_URL" ]; then
  echo "DATABASE_URL is set, skipping local MySQL wait."
else
  # Wait for MySQL to be ready
  until mysqladmin ping -h "$DB_HOST" --silent; do
    echo 'Waiting for MySQL...'
    sleep 2
  done
fi

# Run install.php to set up the database (idempotent)
php install.php || true

# Start Apache
exec "$@"
