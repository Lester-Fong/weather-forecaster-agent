# Deployment Guide for Weather Forecaster Agent

This guide provides step-by-step instructions for deploying the Weather Forecaster Agent application to various environments.

## Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and NPM
- Git
- Web server (Apache, Nginx, etc.)
- SSL certificate (recommended for production)

## Local Development Environment

1. Clone the repository
```bash
git clone https://github.com/Lester-Fong/weather-forecaster-agent.git
cd weather-forecaster-agent
```

2. Install dependencies
```bash
composer install
npm install
```

3. Set up environment variables
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure your `.env` file with the following required variables:
```
GEMINI_API_KEY=your_gemini_api_key
```

5. Set up the database
```bash
touch database/database.sqlite
php artisan migrate
```

6. Compile assets
```bash
npm run dev
```

7. Start the development server
```bash
php artisan serve
```

## Production Deployment

### Server Requirements

- Web server (Nginx recommended)
- PHP 8.2+
- Composer
- Node.js and NPM
- SSL certificate

### Deployment Steps

1. Set up your web server with the appropriate PHP version

2. Clone the repository on your server
```bash
git clone https://github.com/Lester-Fong/weather-forecaster-agent.git
cd weather-forecaster-agent
```

3. Install dependencies
```bash
composer install --no-dev --optimize-autoloader
npm install
```

4. Set up environment variables
```bash
cp .env.example .env
php artisan key:generate
```

5. Configure your `.env` file for production:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
GEMINI_API_KEY=your_gemini_api_key
```

6. Set up the database
```bash
touch database/database.sqlite
php artisan migrate
```

7. Compile assets for production
```bash
npm run build
```

8. Set proper permissions
```bash
chmod -R 755 storage bootstrap/cache
```

9. Configure your web server

#### Nginx Configuration Example
```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl;
    server_name your-domain.com;

    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;

    root /path/to/weather-forecaster-agent/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

10. Restart your web server
```bash
sudo service nginx restart
```

## Docker Deployment (Alternative)

Coming soon

## Troubleshooting

### Common Issues

1. **API Connection Issues**
   - Check your internet connection
   - Verify that your GEMINI_API_KEY is correct
   - Ensure that firewalls aren't blocking outgoing connections

2. **Location Detection Problems**
   - Make sure your site is using HTTPS (required for browser geolocation)
   - Check if the user has granted location permissions

3. **Database Issues**
   - Verify that the SQLite database file exists and is writable
   - Run `php artisan migrate:status` to check migration status

4. **Frontend Not Loading**
   - Check browser console for JavaScript errors
   - Verify that assets were compiled correctly with `npm run build`
   - Clear browser cache

### Getting Help

If you encounter issues not covered in this guide, please:
1. Check the GitHub repository issues section
2. Review Laravel and Vue.js documentation for general framework issues
3. Create a detailed bug report if you believe you've found a bug
