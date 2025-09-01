#!/bin/bash

# Setup script for Weather Forecaster Agent

# Install dependencies
composer install
npm install
npm run build

# Create storage link
php artisan storage:link

# Run migrations
php artisan migrate --force

# Generate key if needed
php artisan key:generate --force

echo "Setup complete!"
