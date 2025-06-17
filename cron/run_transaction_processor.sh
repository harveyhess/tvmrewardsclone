#!/bin/bash

# Change to the project directory
cd "$(dirname "$0")/.."

# Run the PHP script
php cron/process_transactions.php

# Log the execution
echo "$(date): Transaction processor executed" >> logs/cron.log 