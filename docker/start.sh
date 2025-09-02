#!/bin/sh
set -e

echo "Starting Docker container initialization..."

# Function to log errors and warnings
log_message() {
  level=$1
  message=$2
  echo "[${level}] ${message}"
}

# Debug environment variables
echo "Environment variables check:"
echo "GEMINI_API_KEY exists: $(if [ -n "$GEMINI_API_KEY" ]; then echo "YES"; else echo "NO"; fi)"
echo "GEMINI_API_KEY length: ${#GEMINI_API_KEY}"
echo "APP_ENV: ${APP_ENV:-production}"

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
  
  # Generate app key if not already set
  if [ -z "${APP_KEY}" ]; then
    php /var/www/html/artisan key:generate --force
  fi
  
  echo ".env file created successfully."
else
  echo ".env file already exists, updating critical values..."
  # Update critical values in case they've changed
  sed -i "s|^GEMINI_API_KEY=.*|GEMINI_API_KEY=${GEMINI_API_KEY}|" /var/www/html/.env
  sed -i "s|^APP_ENV=.*|APP_ENV=${APP_ENV:-production}|" /var/www/html/.env
  sed -i "s|^APP_DEBUG=.*|APP_DEBUG=${APP_DEBUG:-false}|" /var/www/html/.env
  echo ".env file updated."
fi

# Verify environment variables are in .env
echo "Verifying .env file content:"
grep -i "GEMINI_API_KEY" /var/www/html/.env || echo "GEMINI_API_KEY not found in .env!"
grep -i "APP_ENV" /var/www/html/.env || echo "APP_ENV not found in .env!"

# Create database directory if it doesn't exist
mkdir -p /var/www/html/database
echo "Database directory created."

# Ensure database file exists
touch /var/www/html/database/database.sqlite
chmod 666 /var/www/html/database/database.sqlite
echo "Database file initialized with permissions."

# Ensure storage permissions
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
chmod -R 775 /var/www/html/storage
chown -R www-data:www-data /var/www/html/storage
echo "Storage directories prepared with correct permissions."

# Run migrations if needed
echo "Running database migrations..."
php /var/www/html/artisan migrate --force
echo "Migrations complete."

# Create storage link
echo "Creating storage link..."
php /var/www/html/artisan storage:link
echo "Storage link created."

# Clear config cache to ensure new environment variables are loaded
echo "Clearing configuration cache..."
php /var/www/html/artisan config:clear
echo "Configuration cache cleared."

# Clear cached values
php /var/www/html/artisan cache:clear
echo "Application cache cleared."

# Skip env debug log creation in production due to storage mount issues
if [ "${APP_ENV}" != "production" ]; then
  # Dump environment variables for debugging (without exposing secrets)
  mkdir -p /var/www/html/storage/logs
  touch /var/www/html/storage/logs/env-debug.log
  chmod 666 /var/www/html/storage/logs/env-debug.log
  env | grep -v "_KEY\|_TOKEN\|_SECRET\|PASSWORD" > /var/www/html/storage/logs/env-debug.log
  echo "Environment variables dumped to logs for debugging."
else
  echo "Skipping environment variables dump in production."
fi

# Create a simple server to listen on port 8080
echo "Starting a simple HTTP server on port 8080..."
php -S 0.0.0.0:8080 -t /var/www/html/public
