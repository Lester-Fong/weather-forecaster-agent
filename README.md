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

### Local Development

```bash
# Clone repository
git clone https://github.com/Lester-Fong/weather-forecaster-agent.git
cd weather-forecaster-agent

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Set up the database
touch database/database.sqlite
php artisan migrate

# Build frontend assets
npm run build

# Serve the application
php artisan serve
```

### Deploying with Docker

```bash
# Clone repository
git clone https://github.com/Lester-Fong/weather-forecaster-agent.git
cd weather-forecaster-agent

# Build and start Docker containers
docker-compose build
docker-compose up -d
```

### Deploying to Fly.io

```bash
# Install Fly CLI
curl -L https://fly.io/install.sh | sh

# Login to Fly.io
fly auth login

# Deploy the application
fly launch --dockerfile Dockerfile
fly volumes create weather_agent_data --size 1
fly deploy
```

Make sure to set your `GEMINI_API_KEY` in your environment or through the Fly.io secrets.

## Configuration

The application requires the following environment variables:

- `GEMINI_API_KEY`: Your Google Gemini API key for the AI functionality
- `APP_URL`: The URL where the application is hosted (used for API endpoints)
- `DB_CONNECTION`: Set to `sqlite` for the default configuration

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
