FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libsqlite3-dev \
    nginx \
    gnupg2

# Add a label to explicitly identify this as a Laravel application
LABEL com.fly.app.type="laravel"
LABEL com.fly.app.framework="laravel"
LABEL com.fly.app.language="php"
LABEL com.fly.app.php_version="8.2"

# Install Node.js
RUN curl -sL https://deb.nodesource.com/setup_20.x | bash -
RUN apt-get install -y nodejs

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip
RUN docker-php-ext-install pdo_sqlite

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy configuration files
COPY docker/nginx/conf.d/app.conf /etc/nginx/conf.d/default.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/start.sh /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container

# Copy application files
COPY . /var/www/html

# Ensure icons are properly copied
COPY docker/icons/*.png /var/www/html/public/icons/

# Create Laravel marker file
RUN touch /var/www/html/.laravel

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Install Node.js dependencies and build frontend assets
WORKDIR /var/www/html
RUN npm install
RUN npm run build
RUN chmod -R 755 /var/www/html/public/build

# Generate application key
RUN cp .env.example .env \
    && php artisan key:generate \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Set proper permissions for storage and cache directories
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
RUN mkdir -p /var/www/html/storage/framework/sessions
RUN chmod -R 777 /var/www/html/storage/framework/sessions

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Install dependencies and build frontend
RUN npm install --no-audit --no-fund
RUN npm run build

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Create basic .env file for key generation
RUN if [ -f .env.example ]; then cp .env.example .env; else echo "APP_KEY=" > .env; fi

# Generate key and optimize application
RUN php artisan key:generate --force
RUN php artisan optimize:clear

# Remove .env file (will be created at runtime with proper env vars)
RUN rm -f .env

# Set file permissions
RUN mkdir -p /var/www/html/storage/logs /var/www/html/storage/app/public /var/www/html/storage/framework/cache /var/www/html/storage/framework/sessions /var/www/html/storage/framework/views
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html/storage

# Install curl for healthcheck
RUN apt-get update && apt-get install -y curl && apt-get clean

# Add healthcheck
HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/ || exit 1

# Expose port 8080 for Fly.io
EXPOSE 8080

# Start Nginx and PHP-FPM
CMD ["/usr/local/bin/start-container"]