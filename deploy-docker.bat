@echo off
:: Docker Deployment Script for Weather Forecaster Agent
:: Usage: deploy-docker.bat [build|start|stop|restart|logs|status]

:: Check if Docker is installed
docker --version >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo Docker is not installed. Please install Docker first.
    exit /b 1
)

:: Check if Docker Compose is installed
docker-compose --version >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo Docker Compose is not installed. Please install Docker Compose first.
    exit /b 1
)

:: Set working directory to the project root
cd /d "%~dp0"

:: Main script logic
if "%1"=="build" (
    echo Building and starting containers...
    docker-compose build
    docker-compose up -d
    echo Containers are now running.
) else if "%1"=="start" (
    echo Starting containers...
    docker-compose up -d
    echo Containers are now running.
) else if "%1"=="stop" (
    echo Stopping containers...
    docker-compose down
    echo Containers stopped.
) else if "%1"=="restart" (
    echo Restarting containers...
    docker-compose down
    docker-compose up -d
    echo Containers restarted.
) else if "%1"=="logs" (
    echo Viewing logs (press Ctrl+C to exit)...
    docker-compose logs -f
) else if "%1"=="status" (
    echo Checking container status...
    docker-compose ps
) else (
    echo Usage: %0 [build^|start^|stop^|restart^|logs^|status]
    echo   build   - Build and start containers
    echo   start   - Start containers
    echo   stop    - Stop containers
    echo   restart - Restart containers
    echo   logs    - View container logs
    echo   status  - Check container status
    exit /b 1
)

exit /b 0
