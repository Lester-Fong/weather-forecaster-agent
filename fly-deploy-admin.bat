@echo off
echo =========================================
echo Run this script as Administrator
echo =========================================
echo.
echo This script will install Fly CLI and deploy your application
echo.
echo Step 1: Installing Fly CLI...
powershell -Command "iwr https://fly.io/install.ps1 -useb | iex"
echo.
echo Fly CLI installed. Now you need to login.
echo.
echo Step 2: Logging into Fly.io...
flyctl auth login
echo.
echo Step 3: Setting up your application...
cd C:\xampp\htdocs\weather-forecaster-agent
flyctl launch --no-deploy
echo.
echo Step 4: Setting required secrets...
set /p GEMINI_API_KEY="Enter your Gemini API Key: "
set /p TIMEZONEDB_API_KEY="Enter your TimeZoneDB API Key: "
echo.
echo Setting secrets...
flyctl secrets set GEMINI_API_KEY=%GEMINI_API_KEY%
flyctl secrets set TIMEZONEDB_API_KEY=%TIMEZONEDB_API_KEY%
echo.
echo Step 5: Deploying your application...
flyctl deploy
echo.
echo Step 6: Checking application status...
flyctl status
echo.
echo Deployment complete! Your application should be accessible at:
echo https://YOUR-APP-NAME.fly.dev
echo.
echo (Replace YOUR-APP-NAME with the name you chose during setup)
echo.
pause
