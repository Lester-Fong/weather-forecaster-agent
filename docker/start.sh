#!/bin/sh
set -e

# Create the .env file if it doesn't exist
if [ ! -f /var/www/html/.env ]; then
  echo "Creating .env file..."
  touch /var/www/html/.env
  
  # Add necessary environment variables
  echo "APP_NAME=\"Weather Forecaster Agent\"" >> /var/www/html/.env
  echo "APP_ENV=${APP_ENV:-production}" >> /var/www/html/.env
  echo "APP_KEY=${APP_KEY:-}" >> /var/www/html/.env
  echo "APP_DEBUG=${APP_DEBUG:-false}" >> /var/www/html/.env
  echo "APP_URL=${APP_URL:-https://weather-forecaster-agent.fly.dev}" >> /var/www/html/.env
  
  echo "LOG_CHANNEL=${LOG_CHANNEL:-stderr}" >> /var/www/html/.env
  echo "LOG_LEVEL=${LOG_LEVEL:-info}" >> /var/www/html/.env
  
  echo "DB_CONNECTION=${DB_CONNECTION:-sqlite}" >> /var/www/html/.env
  echo "SESSION_DRIVER=${SESSION_DRIVER:-file}" >> /var/www/html/.env
  
  echo "GEMINI_API_KEY=${GEMINI_API_KEY}" >> /var/www/html/.env
  echo "TIMEZONEDB_API_KEY=${TIMEZONEDB_API_KEY}" >> /var/www/html/.env
  
  # Generate app key if not already set
  if [ -z "${APP_KEY}" ]; then
    php /var/www/html/artisan key:generate --force
  fi
fi

# Create database directory if it doesn't exist
mkdir -p /var/www/html/database

# Ensure database file exists
touch /var/www/html/database/database.sqlite
chmod 666 /var/www/html/database/database.sqlite

# Ensure storage permissions
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
chmod -R 775 /var/www/html/storage
chown -R www-data:www-data /var/www/html/storage

# Run migrations if needed
php /var/www/html/artisan migrate --force

# Create storage link
php /var/www/html/artisan storage:link

# Start PHP-FPM
php-fpm -D

# Start Nginx
nginx -g "daemon off;"
