# Deploying Weather Forecaster Agent Using Fly CLI

This guide provides step-by-step instructions for deploying your Weather Forecaster Agent to Fly.io using the Command Line Interface (CLI) method, which is more reliable than the web interface for Laravel applications.

## Prerequisites

1. A GitHub account with your Weather Forecaster Agent repository
2. A Fly.io account (sign up at https://fly.io)
3. A Gemini API key for the weather forecasting functionality
4. A TimeZoneDB API key (get a free one from https://timezonedb.com/api)

## Installation Steps

### 1. Install the Fly CLI

#### Windows:
Run the provided `install-fly-cli.bat` script or run this in PowerShell:
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
fly secrets set TIMEZONEDB_API_KEY=your_timezonedb_api_key
```

Replace `your_gemini_api_key` and `your_timezonedb_api_key` with your actual API keys.

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

## Troubleshooting

### Viewing Logs

If you encounter issues, you can check the logs:

```bash
fly logs
```

### Restarting the Application

If needed, you can restart your application:

```bash
fly apps restart
```

### Checking Volume Status

To verify your volume is properly attached:

```bash
fly volumes list
```

### SSH into the Application

For advanced troubleshooting, you can SSH into your application:

```bash
fly ssh console
```

## Updating Your Application

When you make changes to your code:

1. Commit and push to GitHub
2. Run `fly deploy` again

## Resources

- [Fly CLI Documentation](https://fly.io/docs/flyctl/)
- [Laravel on Fly.io](https://fly.io/docs/laravel/)
- [Fly.io Volumes](https://fly.io/docs/reference/volumes/)
