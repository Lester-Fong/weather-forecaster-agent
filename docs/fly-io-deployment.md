# Deploying Weather Forecaster Agent to Fly.io

This guide provides step-by-step instructions for deploying the Weather Forecaster Agent application to Fly.io's free tier.

## Prerequisites

- [Fly.io account](https://fly.io/app/sign-up)
- [Fly CLI installed](https://fly.io/docs/hands-on/install-flyctl/)
- Git
- Your Gemini API key

## Deployment Steps

### 1. Authenticate with Fly.io

```bash
fly auth login
```

### 2. Clone the Repository

```bash
git clone https://github.com/Lester-Fong/weather-forecaster-agent.git
cd weather-forecaster-agent
```

### 3. Configure Environment Variables

Edit the `.env.fly` file and add your Gemini API key:

```
GEMINI_API_KEY=your_gemini_api_key
```

### 4. Deploy the Application

```bash
# Launch the app - first time only
fly launch --copy-config --dockerfile Dockerfile --env-file .env.fly

# For subsequent deployments
fly deploy
```

During the launch process, Fly.io will ask you several questions:
- App name (you can use the suggested name or choose your own)
- Region (choose the closest to you or your users)
- Setup a PostgreSQL database (choose "No" since we're using SQLite)
- Setup a Redis database (choose "No")

### 5. Create a Volume for Persistent Storage

```bash
fly volumes create weather_agent_data --size 1 --app weather-forecaster-agent
```

### 6. Set Secrets (Optional)

If you prefer not to include your API key in the .env.fly file:

```bash
fly secrets set GEMINI_API_KEY=your_gemini_api_key
```

### 7. Deploy the Application

```bash
fly deploy
```

### 8. Open the Application

```bash
fly open
```

## Monitoring and Maintenance

### Check Application Status

```bash
fly status
```

### View Logs

```bash
fly logs
```

### SSH into the Application

```bash
fly ssh console
```

### Scale the Application

If you need more resources (note: this will exceed the free tier):

```bash
fly scale memory 2048
fly scale vm shared-cpu-2x
```

## Troubleshooting

### Common Issues

1. **Application fails to start**:
   - Check logs: `fly logs`
   - Ensure your Gemini API key is correct
   - Verify your SQLite database is properly configured

2. **Deployment fails**:
   - Check if you have committed the correct Dockerfile
   - Ensure your fly.toml file is correctly formatted

3. **SSL/HTTPS issues**:
   - Fly.io handles SSL certificates automatically, but it may take a few minutes to propagate

4. **Storage issues**:
   - Make sure you've created the volume and it's mounted correctly
   - Check permissions on the storage directory

## Free Tier Limits

Fly.io's free tier includes:
- 3 shared-CPU VMs with 256MB RAM
- 3GB persistent volume storage
- 160GB outbound data transfer

Our configuration uses:
- 1 shared-CPU VM with 1GB RAM
- 1GB persistent volume storage

This keeps us well within the free tier limits while providing enough resources for a small-to-medium traffic application.

## Additional Resources

- [Fly.io Documentation](https://fly.io/docs/)
- [Laravel Deployment Best Practices](https://laravel.com/docs/10.x/deployment)
- [Docker Optimization Tips](https://docs.docker.com/develop/develop-images/dockerfile_best-practices/)
