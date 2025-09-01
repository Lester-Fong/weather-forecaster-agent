# Fly.io Deployment Instructions

## Prerequisites

1. A GitHub account with your Weather Forecaster Agent repository
2. A Fly.io account (sign up at https://fly.io)
3. A Gemini API key for weather forecasting functionality
4. A TimeZoneDB API key (get one from https://timezonedb.com/api)

## Manual Deployment Steps

Due to permission issues in some environments, here are the steps to deploy manually:

### 1. Install Fly CLI

Open a Command Prompt as Administrator and run:

```
powershell -Command "iwr https://fly.io/install.ps1 -useb | iex"
```

### 2. Log in to Fly.io

```
flyctl auth login
```

### 3. Navigate to your project directory

```
cd C:\xampp\htdocs\weather-forecaster-agent
```

### 4. Launch your application (but don't deploy yet)

```
flyctl launch --no-deploy
```

When prompted:
- Choose your app name (e.g., "weather-forecaster-agent")
- Choose your organization
- Choose a region closest to your users
- Say NO to PostgreSQL
- Say NO to Redis
- Say YES to creating a volume
  - Name: "weather_agent_data"
  - Size: 1 GB
  - Destination: "/var/www/html/storage"

### 5. Set required secrets

```
flyctl secrets set GEMINI_API_KEY=your_gemini_api_key
flyctl secrets set TIMEZONEDB_API_KEY=your_timezonedb_api_key
```

### 6. Deploy your application

```
flyctl deploy
```

### 7. Check application status

```
flyctl status
```

### 8. Access your application

Visit your application at:
```
https://your-app-name.fly.dev
```

Replace `your-app-name` with the name you chose during setup.
