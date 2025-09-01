# Docker Deployment Guide for Weather Forecaster Agent on AWS Free Tier

This guide covers the deployment of the Weather Forecaster Agent application on AWS Free Tier using Docker.

## Prerequisites

- AWS account (Free Tier eligible)
- Docker and Docker Compose installed on your local machine
- AWS CLI installed and configured on your local machine
- Basic knowledge of AWS services
- Git

## Part 1: Local Docker Setup and Testing

Before deploying to AWS, let's test our Docker setup locally.

1. **Clone the repository**:
   ```bash
   git clone https://github.com/Lester-Fong/weather-forecaster-agent.git
   cd weather-forecaster-agent
   ```

2. **Set up environment variables**:
   ```bash
   cp .env.example .env
   ```
   
3. **Edit your `.env` file**:
   - Set `APP_ENV=production`
   - Set `APP_DEBUG=false`
   - Set `GEMINI_API_KEY=your_gemini_api_key`
   - Configure other necessary environment variables

4. **Build and start Docker containers**:
   ```bash
   docker-compose build
   docker-compose up -d
   ```

5. **Initialize the application**:
   ```bash
   docker-compose exec app php artisan key:generate
   docker-compose exec app php artisan migrate
   docker-compose exec app php artisan storage:link
   ```

6. **Test the application**:
   - Visit `http://localhost` in your browser
   - Ensure all features are working correctly

7. **Stop Docker containers**:
   ```bash
   docker-compose down
   ```

## Part 2: AWS Setup

### Step 1: Create an EC2 Instance

1. **Log in to AWS Console**:
   - Go to https://aws.amazon.com/console/
   - Sign in to your AWS account

2. **Launch an EC2 Instance**:
   - Go to EC2 Dashboard
   - Click "Launch Instance"
   - Choose Amazon Linux 2 AMI (Free tier eligible)
   - Select t2.micro instance type (Free tier eligible)
   - Configure instance details (use defaults for basic setup)
   - Add Storage (8GB is sufficient for Free tier)
   - Add Tags (optional)
   - Configure Security Group:
     - Allow SSH (port 22) from your IP
     - Allow HTTP (port 80) from anywhere
     - Allow HTTPS (port 443) from anywhere
   - Review and Launch
   - Create a new key pair, download it, and save it securely
   - Launch the instance

3. **Allocate an Elastic IP (optional but recommended)**:
   - Go to EC2 Dashboard > Elastic IPs
   - Click "Allocate Elastic IP address"
   - Click "Allocate"
   - Select the newly allocated IP, click "Actions" > "Associate Elastic IP address"
   - Select your instance and click "Associate"

### Step 2: Set Up the EC2 Instance

1. **Connect to your EC2 instance**:
   ```bash
   chmod 400 your-key-pair.pem
   ssh -i your-key-pair.pem ec2-user@your-instance-public-ip
   ```

2. **Update the system and install dependencies**:
   ```bash
   sudo yum update -y
   sudo yum install -y git
   ```

3. **Install Docker and Docker Compose**:
   ```bash
   sudo amazon-linux-extras install docker -y
   sudo service docker start
   sudo systemctl enable docker
   sudo usermod -a -G docker ec2-user
   
   # Log out and log back in to apply group changes
   exit
   # Connect again
   ssh -i your-key-pair.pem ec2-user@your-instance-public-ip
   
   # Install Docker Compose
   sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
   sudo chmod +x /usr/local/bin/docker-compose
   ```

### Step 3: Deploy the Application

1. **Clone the repository**:
   ```bash
   git clone https://github.com/Lester-Fong/weather-forecaster-agent.git
   cd weather-forecaster-agent
   ```

2. **Set up environment variables**:
   ```bash
   cp .env.example .env
   nano .env
   ```
   
   Configure your `.env` file:
   - Set `APP_ENV=production`
   - Set `APP_DEBUG=false`
   - Set `APP_URL=http://your-instance-public-ip` (or your domain if you have one)
   - Set `GEMINI_API_KEY=your_gemini_api_key`
   - Configure other necessary environment variables

3. **Build and start Docker containers**:
   ```bash
   docker-compose build
   docker-compose up -d
   ```

4. **Initialize the application**:
   ```bash
   docker-compose exec app php artisan key:generate
   docker-compose exec app php artisan migrate
   docker-compose exec app php artisan storage:link
   ```

5. **Set up SSL with Let's Encrypt (optional but recommended)**:
   
   If you have a domain name pointed to your instance:
   
   ```bash
   # Install Certbot
   sudo amazon-linux-extras install epel -y
   sudo yum install -y certbot
   
   # Get SSL certificate
   sudo certbot certonly --standalone -d yourdomain.com -d www.yourdomain.com
   
   # Copy certificates to Nginx SSL directory
   sudo cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem /path/to/weather-forecaster-agent/docker/nginx/ssl/
   sudo cp /etc/letsencrypt/live/yourdomain.com/privkey.pem /path/to/weather-forecaster-agent/docker/nginx/ssl/
   
   # Update Nginx configuration to use SSL
   # Edit docker/nginx/conf.d/app.conf to include SSL configuration
   
   # Restart containers
   docker-compose down
   docker-compose up -d
   ```

6. **Set up automatic renewal for SSL certificates (optional)**:
   ```bash
   echo "0 0,12 * * * root python -c 'import random; import time; time.sleep(random.random() * 3600)' && certbot renew -q" | sudo tee -a /etc/crontab > /dev/null
   ```

## Part 3: Maintenance and Updates

### Updating the Application

1. **Pull the latest changes**:
   ```bash
   cd ~/weather-forecaster-agent
   git pull origin master
   ```

2. **Rebuild and restart containers**:
   ```bash
   docker-compose down
   docker-compose build
   docker-compose up -d
   ```

3. **Run migrations if needed**:
   ```bash
   docker-compose exec app php artisan migrate
   ```

### Monitoring

1. **Check container status**:
   ```bash
   docker-compose ps
   ```

2. **View logs**:
   ```bash
   docker-compose logs
   ```

3. **View specific container logs**:
   ```bash
   docker-compose logs app
   docker-compose logs webserver
   ```

### Backup

1. **Backup the database**:
   ```bash
   docker-compose exec app php artisan db:backup
   ```

2. **Backup your entire application**:
   ```bash
   # From your local machine
   rsync -avz -e "ssh -i your-key-pair.pem" ec2-user@your-instance-public-ip:/path/to/weather-forecaster-agent /path/to/local/backup
   ```

## Part 4: Scaling and Load Balancing (Future Expansion)

As your application grows beyond the Free Tier, consider:

1. **Using Amazon RDS** for database management
2. **Setting up a load balancer** (AWS ELB)
3. **Implementing AWS Auto Scaling** for EC2 instances
4. **Using Amazon CloudFront** for content delivery
5. **Migrating to ECS/Fargate** for container orchestration

## Troubleshooting

### Common Issues and Solutions

1. **Container won't start**:
   ```bash
   docker-compose logs app
   ```
   
   Check for error messages and fix accordingly.

2. **Permission issues**:
   ```bash
   docker-compose exec app chown -R www-data:www-data /var/www/html/storage
   docker-compose exec app chown -R www-data:www-data /var/www/html/bootstrap/cache
   ```

3. **Database connection issues**:
   - Verify your `.env` configuration
   - Ensure the database file exists
   ```bash
   docker-compose exec app touch database/database.sqlite
   docker-compose exec app php artisan migrate
   ```

4. **Nginx configuration issues**:
   ```bash
   docker-compose exec webserver nginx -t
   ```
   
   This will test your Nginx configuration for syntax errors.

## Security Considerations

1. **Keep your system updated**:
   ```bash
   sudo yum update -y
   ```

2. **Regularly update your application dependencies**:
   ```bash
   docker-compose exec app composer update
   docker-compose exec app npm update
   ```

3. **Set up a firewall** (AWS Security Groups should handle this)

4. **Enable automated backups**

5. **Monitor your application logs for suspicious activity**
