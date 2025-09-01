#!/bin/bash

# Docker Deployment Script for Weather Forecaster Agent
# Usage: ./deploy-docker.sh [build|start|stop|restart|logs|status]

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Set working directory to the project root
cd "$(dirname "$0")"

# Function to build and start containers
build_and_start() {
    echo "Building and starting containers..."
    docker-compose build
    docker-compose up -d
    echo "Containers are now running."
}

# Function to stop containers
stop_containers() {
    echo "Stopping containers..."
    docker-compose down
    echo "Containers stopped."
}

# Function to restart containers
restart_containers() {
    echo "Restarting containers..."
    docker-compose down
    docker-compose up -d
    echo "Containers restarted."
}

# Function to view logs
view_logs() {
    echo "Viewing logs (press Ctrl+C to exit)..."
    docker-compose logs -f
}

# Function to check container status
check_status() {
    echo "Checking container status..."
    docker-compose ps
}

# Main script logic
case "$1" in
    build)
        build_and_start
        ;;
    start)
        echo "Starting containers..."
        docker-compose up -d
        echo "Containers are now running."
        ;;
    stop)
        stop_containers
        ;;
    restart)
        restart_containers
        ;;
    logs)
        view_logs
        ;;
    status)
        check_status
        ;;
    *)
        echo "Usage: $0 [build|start|stop|restart|logs|status]"
        echo "  build   - Build and start containers"
        echo "  start   - Start containers"
        echo "  stop    - Stop containers"
        echo "  restart - Restart containers"
        echo "  logs    - View container logs"
        echo "  status  - Check container status"
        exit 1
esac

exit 0
