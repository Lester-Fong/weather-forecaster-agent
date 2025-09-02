#!/bin/bash

# This script checks if the Gemini API key is set in Fly.io secrets

# Check if fly CLI is installed
if ! command -v fly &> /dev/null; then
    echo "Error: fly CLI is not installed. Please install it first."
    exit 1
fi

# Check if logged in to Fly.io
echo "Checking Fly.io login status..."
fly auth whoami || {
    echo "Not logged in to Fly.io. Please run 'fly auth login' first."
    exit 1
}

# Get the app name
APP_NAME="weather-forecaster-agent"
echo "Checking secrets for app: $APP_NAME"

# List secrets
echo "Listing secrets (names only):"
fly secrets list -a $APP_NAME

# Check if GEMINI_API_KEY exists
echo "Checking for GEMINI_API_KEY:"
if fly secrets list -a $APP_NAME | grep -q "GEMINI_API_KEY"; then
    echo "✅ GEMINI_API_KEY is set in Fly.io secrets"
else
    echo "❌ GEMINI_API_KEY is NOT set in Fly.io secrets"
    
    # Prompt to set the secret
    read -p "Do you want to set the GEMINI_API_KEY now? (y/n): " answer
    if [[ "$answer" == "y" || "$answer" == "Y" ]]; then
        read -p "Enter your Gemini API key: " gemini_key
        fly secrets set GEMINI_API_KEY="$gemini_key" -a $APP_NAME
        echo "Secret set. You should redeploy the app for the changes to take effect."
        read -p "Do you want to redeploy now? (y/n): " deploy_answer
        if [[ "$deploy_answer" == "y" || "$deploy_answer" == "Y" ]]; then
            fly deploy -a $APP_NAME
        fi
    fi
fi

echo "Secret check complete."
