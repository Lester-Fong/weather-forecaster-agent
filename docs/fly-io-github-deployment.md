# Deploying to Fly.io via GitHub Using the Web Interface

This guide provides step-by-step instructions for deploying your Weather Forecaster Agent to Fly.io directly from GitHub using Fly.io's web interface.

## Prerequisites

1. A GitHub account with your Weather Forecaster Agent repository
2. A Fly.io account (sign up at https://fly.io)
3. A Gemini API key for the weather forecasting functionality
4. A TimeZoneDB API key (get a free one from https://timezonedb.com/api)

## Deployment Steps

### 1. Log in to Fly.io

Visit [https://fly.io/dashboard](https://fly.io/dashboard) and log in to your account.

### 2. Create a New Application

1. Click the "Create App" button in the Fly.io dashboard
2. Choose "Deploy from GitHub repository"
3. Select your GitHub account (you may need to authorize Fly.io if this is your first time)
4. Select the "weather-forecaster-agent" repository
5. Choose the branch you want to deploy (typically "master" or "main")

### 3. Configure Deployment Settings

1. Review the automatically detected settings
2. Make sure it detects the application as a Laravel application
3. Confirm the following settings are correctly set:
   - Organization (your Fly.io organization)
   - App Name: "weather-forecaster-agent" (or choose a different name)
   - Region: Choose a region closest to your users (default is "sea" in fly.toml)
   - VM Resources: 1 vCPU / 1 GB RAM (as configured in fly.toml)

### 4. Set Environment Variables

Click "Add Secret" to add these essential environment variables:
   - `GEMINI_API_KEY`: Your Google Gemini API key
   - `TIMEZONEDB_API_KEY`: Your TimeZoneDB API key
   - Any other environment variables you need (most are already in fly.toml)

### 5. Configure Volume

1. Choose "Create and attach a volume"
2. Volume name: "weather_agent_data" (as defined in fly.toml)
3. Size: 1 GB (adjust as needed)
4. Mount point: "/var/www/html/storage" (as defined in fly.toml)

### 6. Deploy the Application

1. Click "Create App" or "Deploy" button
2. Fly.io will:
   - Clone your GitHub repository
   - Build the Docker image using your Dockerfile
   - Deploy the application
   - Attach the volume
   - Configure networking

### 7. Monitor Deployment

1. You'll be redirected to the deployment logs
2. Monitor the build and deployment process
3. Wait for the deployment to complete (should take a few minutes)

### 8. Verify Deployment

1. Once deployment is complete, visit your application at:
   `https://weather-forecaster-agent.fly.dev` (or your custom app name)
2. Verify that the application is working correctly

## Troubleshooting

### Common Issues

1. **App Type Detection Error**:
   If you see an error like "launch manifest was created for a app, but this is a Laravel app":
   - The repository already includes metadata in `.fly/config.json` to address this
   - If you still encounter this issue, try deploying using the Fly CLI instead of the web interface

2. **Build Failure**:
   - Check the build logs for specific errors
   - Ensure your Dockerfile is correctly formatted
   - Verify that all required files are in the repository

3. **Runtime Errors**:
   - Check application logs in the Fly.io dashboard
   - Verify environment variables are set correctly
   - Ensure the volume is properly mounted

4. **Database Issues**:
   - Verify the SQLite database is being created correctly
   - Check file permissions in the container

### Viewing Logs

1. In the Fly.io dashboard, navigate to your app
2. Click on the "Monitoring" tab
3. Select "Logs" to view application logs

## Updating Your Application

When you make changes to your GitHub repository:

1. Go to your app in the Fly.io dashboard
2. Click the "Deploy" button
3. Select the branch and commit you want to deploy
4. Click "Deploy" to start the deployment process

Alternatively, you can set up automatic deployments:

1. In your app settings, go to "GitHub"
2. Enable "Automatic Deployments"
3. Select the branch you want to auto-deploy

## Resources

- [Fly.io Documentation](https://fly.io/docs/)
- [Fly.io GitHub Integration](https://fly.io/docs/apps/deploy/github/)
- [Laravel on Fly.io](https://fly.io/docs/laravel/)
