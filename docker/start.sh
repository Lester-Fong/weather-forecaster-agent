#!/bin/sh
set -e

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
