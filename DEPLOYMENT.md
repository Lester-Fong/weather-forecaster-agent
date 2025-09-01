# Weather Forecaster Agent Deployment Guide

This comprehensive guide provides instructions for deploying the Weather Forecaster Agent application in various environments.

## Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and NPM
- Git

## Environment Variables

The following environment variables need to be configured for any deployment:

```
GEMINI_API_KEY=your_gemini_api_key
```

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

## Traditional Server Deployment

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

## Docker Deployment

The Weather Forecaster Agent comes with Docker configuration for easy containerized deployment.

### Prerequisites

- Docker
- Docker Compose (for local development)

### Local Docker Development

1. Build and start the Docker containers:
```bash
docker-compose up -d
```

2. Access the application at http://localhost:8080

### Production Docker Deployment

Use the provided Dockerfile for production deployment:

```bash
docker build -t weather-forecaster-agent .
docker run -p 80:8080 -e GEMINI_API_KEY=your_api_key weather-forecaster-agent
```

## Fly.io Deployment (Recommended)

Deploying to Fly.io is the recommended approach for quickly getting your Weather Forecaster Agent online.

### Prerequisites

1. A GitHub account with your Weather Forecaster Agent repository
2. A Fly.io account (sign up at https://fly.io)
3. A Gemini API key for the weather forecasting functionality

### 1. Install the Fly CLI

#### Windows:
```powershell
iwr https://fly.io/install.ps1 -useb | iex
```

#### macOS/Linux:
```bash
curl -L https://fly.io/install.sh | sh
```

After installation, you may need to restart your terminal.

### 2. Log in to Fly.io

```bash
fly auth login
```
This will open a browser window for you to authenticate.

### 3. Navigate to Your Project Directory

```bash
cd path/to/weather-forecaster-agent
```

### 4. Initialize Your Fly.io App

```bash
fly launch --no-deploy
```

During this process, you'll be asked a series of questions:
- **App name**: Choose a name (e.g., "weather-forecaster-agent" or a custom name)
- **Organization**: Select your Fly.io organization
- **Region**: Choose a region closest to your users
- **PostgreSQL**: Choose "No"
- **Redis**: Choose "No"
- **Volume**: Choose "Yes" to create a volume
  - Name: "weather_agent_data"
  - Size: 1 GB
  - Destination: "/var/www/html/storage"

This command will create/update your `fly.toml` file with the correct settings.

### 5. Set Required Secrets

```bash
fly secrets set GEMINI_API_KEY=your_gemini_api_key
```

Replace `your_gemini_api_key` with your actual API key.

### 6. Deploy Your Application

```bash
fly deploy
```

This command will:
- Build your Docker image using your Dockerfile
- Push the image to Fly.io's registry
- Deploy the application
- Attach the volume
- Configure networking

### 7. Check Application Status

```bash
fly status
```

### 8. Access Your Application

Once deployed, visit your application at:
```
https://your-app-name.fly.dev
```

Replace `your-app-name` with the name you chose during setup.

### Viewing Logs

If you encounter issues, you can check the logs:

```bash
fly logs
```

### Updating Your Application

When you make changes to your code:

1. Commit and push to GitHub
2. Run `fly deploy` again

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
