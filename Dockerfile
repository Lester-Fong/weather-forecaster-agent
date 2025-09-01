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

# Create Laravel marker file
RUN touch /var/www/html/.laravel

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Install dependencies and build frontend
RUN npm install --no-audit --no-fund
RUN npm run build

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Create basic .env file from .env.example
RUN cp .env.example .env

# Generate key and optimize application
RUN php artisan key:generate --force
RUN php artisan optimize:clear

# Remove .env file (will be created at runtime with proper env vars)
RUN rm .env

# Expose port 8080 for Fly.io
EXPOSE 8080

# Start Nginx and PHP-FPM
CMD ["/usr/local/bin/start-container"]