#!/bin/bash
set -e

# Wait for MySQL to be ready
until mysqladmin ping -h "$DB_HOST" --silent; do
  echo 'Waiting for MySQL...'
  sleep 2
done

# Run install.php to set up the database (idempotent)
php install.php || true

# Start Apache
exec "$@"
