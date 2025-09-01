@echo off
echo ==================================
echo Weather Forecaster Agent Deployment
echo ==================================
echo.

echo Step 1: Logging in to Fly.io...
echo If you're not already logged in, a browser window will open.
call C:\Users\LesterFong\.fly\bin\flyctl.exe auth login
echo.

echo Step 2: Launching your application (with interactive setup)...
echo When prompted:
echo - Choose your app name (e.g., weather-forecaster-agent)
echo - Choose your organization
echo - Choose a region close to your users
echo - Say NO to PostgreSQL
echo - Say NO to Redis
echo - Say YES to creating a volume for storage
echo - Name the volume: weather_agent_data
echo - Size: 1 (GB)
echo - Destination: /var/www/html/storage
echo.
cd C:\xampp\htdocs\weather-forecaster-agent
call C:\Users\LesterFong\.fly\bin\flyctl.exe launch --no-deploy
echo.

echo Step 3: Setting required secrets...
echo.
set /p GEMINI_API_KEY="Enter your Gemini API Key: "
set /p TIMEZONEDB_API_KEY="Enter your TimeZoneDB API Key: "

echo Setting secrets...
call C:\Users\LesterFong\.fly\bin\flyctl.exe secrets set GEMINI_API_KEY=%GEMINI_API_KEY%
call C:\Users\LesterFong\.fly\bin\flyctl.exe secrets set TIMEZONEDB_API_KEY=%TIMEZONEDB_API_KEY%
echo.

echo Step 4: Deploying the application...
call C:\Users\LesterFong\.fly\bin\flyctl.exe deploy
echo.

echo Step 5: Checking application status...
call C:\Users\LesterFong\.fly\bin\flyctl.exe status
echo.

echo Deployment complete! Your application should be accessible at:
echo https://YOUR-APP-NAME.fly.dev
echo.
echo (Replace YOUR-APP-NAME with the name you chose during setup)
echo.

pause
