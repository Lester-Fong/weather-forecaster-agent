# Weather Forecaster Agent Deployment Guide

This document provides guidance on deploying the Weather Forecaster Agent application in various environments.

## Deployment Options

Choose the deployment option that best suits your needs:

1. [Traditional Deployment](DEPLOYMENT.md) - Deploy directly on a server with PHP, Composer, and Node.js
2. [Docker Deployment on AWS Free Tier](docs/docker-aws-deployment.md) - Deploy using Docker containers on AWS Free Tier
3. [Fly.io Deployment via CLI](docs/fly-cli-deployment.md) - Deploy using Fly.io Command Line Interface (Recommended)
4. [Fly.io Deployment via GitHub](docs/fly-io-github-deployment.md) - Deploy directly from GitHub using Fly.io's web interface

## Prerequisites for All Deployments

- PHP 8.2 or higher
- Composer
- Node.js and NPM
- Git

## Environment Variables

The following environment variables need to be configured for any deployment:

```
GEMINI_API_KEY=your_gemini_api_key
```

## Deployment Checklist

Regardless of deployment method, ensure you complete these steps:

1. Set up proper environment variables
2. Run database migrations
3. Compile frontend assets
4. Set proper permissions for storage and cache directories
5. Configure your web server (Apache, Nginx, etc.)
6. Set up SSL certificate for production

## Production Considerations

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Use a secure database solution
3. Set up proper caching
4. Configure error logging
5. Set up monitoring
6. Implement regular backups

## Scaling Considerations

As your application grows, consider:

1. Load balancing
2. Database scaling
3. Caching strategies
4. Content delivery networks (CDNs)
5. Container orchestration for Docker deployments
