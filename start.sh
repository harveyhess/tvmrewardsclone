#!/bin/bash

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "PHP is not installed. Please install PHP first."
    exit 1
fi

# Kill any process using port 2500
lsof -ti:2500 | xargs kill -9 2>/dev/null

# Start the application
php start.php 