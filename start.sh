#!/bin/bash

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "PHP is not installed. Please install PHP first."
    exit 1
fi

# Kill any process using port 2500
lsof -ti:2500 | xargs kill -9 2>/dev/null

# Start the app in the background and log output
nohup php start.php > /home/pqqlulab/repositories/tvmrewardsclone/app.log 2>&1 &
echo "App started in background on port 8000"
