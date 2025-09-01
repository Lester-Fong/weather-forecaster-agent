# Weather Forecaster Agent

An AI-powered weather forecasting assistant that allows users to have natural language conversations about weather conditions for any location and time.

![Weather Forecaster Agent](https://github.com/Lester-Fong/weather-forecaster-agent/raw/master/screenshot.png)

## Features

- **Natural Language Interface**: Ask about weather in plain English
- **Location Awareness**: Automatic detection of user's location
- **Conversational AI**: Powered by Google Gemini Pro
- **Multi-location Support**: Get weather for any location worldwide
- **Date Understanding**: Ask about past, present, or future weather
- **Mobile-first Design**: Fully responsive across all devices
- **Visual Weather Display**: See temperatures and conditions at a glance

## Technology Stack

- **Backend**: Laravel 12
- **Frontend**: Vue 3 with Quasar Framework
- **Database**: SQLite
- **Weather Data**: Open-Meteo API
- **AI/LLM**: Google Gemini Pro
- **Location Services**: Browser Geolocation + Open-Meteo Geocoding

## Deployment Options

### Local Development with Docker

```bash
# Clone repository
git clone https://github.com/Lester-Fong/weather-forecaster-agent.git
cd weather-forecaster-agent

# Build and start Docker containers
docker-compose build
docker-compose up -d

# Run migrations and setup
docker-compose exec app php artisan migrate
docker-compose exec app php artisan storage:link
```

For more details, see the [Docker deployment guide](docs/docker-aws-deployment.md).

### Deploying to Fly.io (Free Tier)

```bash
# Install Fly CLI
curl -L https://fly.io/install.sh | sh

# Login to Fly.io
fly auth login

# Deploy the application
fly launch --copy-config --dockerfile Dockerfile --env-file .env.fly
fly volumes create weather_agent_data --size 1
fly deploy
```

For detailed instructions, see the [Fly.io deployment guide](docs/fly-io-deployment.md).

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js and NPM
- SQLite

### Installation

1. Clone the repository
```bash
git clone https://github.com/Lester-Fong/weather-forecaster-agent.git
cd weather-forecaster-agent
```

2. Install PHP dependencies
```bash
composer install
```

3. Install JavaScript dependencies
```bash
npm install
```

4. Copy the example environment file and set up your environment variables
```bash
cp .env.example .env
```

5. Generate application key
```bash
php artisan key:generate
```

6. Set up the database
```bash
php artisan migrate
```

7. Build the frontend assets
```bash
npm run build
```

8. Serve the application
```bash
php artisan serve
```

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
