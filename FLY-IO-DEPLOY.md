# Fly.io GitHub Deployment for Weather Forecaster Agent

This is a quick reference guide for deploying the Weather Forecaster Agent to Fly.io using GitHub integration through the web interface.

## Quick Deployment Steps

1. Log in to [Fly.io Dashboard](https://fly.io/dashboard)
2. Click "Create App" > "Deploy from GitHub repository"
3. Select the "weather-forecaster-agent" repository
4. Configure:
   - App name: "weather-forecaster-agent" (or custom name)
   - Region: Choose closest to your users
   - VM Resources: 1 vCPU / 1 GB RAM
5. Add secrets:
   - `GEMINI_API_KEY`: Your Google Gemini API key
   - `TIMEZONEDB_API_KEY`: Your TimeZoneDB API key
   - (Optional) `APP_KEY`: A Laravel application key (will be auto-generated if not provided)
6. Create and attach volume:
   - Name: "weather_agent_data"
   - Size: 1 GB
   - Destination: "/var/www/html/storage"
7. Deploy!

## Troubleshooting App Type Detection

If you encounter "launch manifest was created for a app, but this is a Laravel app" error:
- The repository now includes metadata in `.fly/config.json` to address this
- If you still encounter this issue, try deploying using the Fly CLI

## Checking Deployment Status

Once deployed, visit your app at:
`https://weather-forecaster-agent.fly.dev` (or your custom app name)

## More Information

For detailed instructions, see [Fly.io GitHub Deployment Guide](docs/fly-io-github-deployment.md)
