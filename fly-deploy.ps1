# PowerShell script for deploying to Fly.io
Write-Host "=================================="
Write-Host "Weather Forecaster Agent Deployment"
Write-Host "=================================="
Write-Host ""

Write-Host "Step 1: Logging in to Fly.io..."
Write-Host "If you're not already logged in, a browser window will open."
& "$env:USERPROFILE\.fly\bin\flyctl.exe" auth login
Write-Host ""

Write-Host "Step 2: Launching your application (with interactive setup)..."
Write-Host "When prompted:"
Write-Host "- Choose your app name (e.g., weather-forecaster-agent)"
Write-Host "- Choose your organization"
Write-Host "- Choose a region close to your users"
Write-Host "- Say NO to PostgreSQL"
Write-Host "- Say NO to Redis"
Write-Host "- Say YES to creating a volume for storage"
Write-Host "- Name the volume: weather_agent_data"
Write-Host "- Size: 1 (GB)"
Write-Host "- Destination: /var/www/html/storage"
Write-Host ""
Set-Location "C:\xampp\htdocs\weather-forecaster-agent"
& "$env:USERPROFILE\.fly\bin\flyctl.exe" launch --no-deploy
Write-Host ""

Write-Host "Step 3: Setting required secrets..."
Write-Host ""
$GEMINI_API_KEY = Read-Host "Enter your Gemini API Key"
$TIMEZONEDB_API_KEY = Read-Host "Enter your TimeZoneDB API Key"

Write-Host "Setting secrets..."
& "$env:USERPROFILE\.fly\bin\flyctl.exe" secrets set GEMINI_API_KEY="$GEMINI_API_KEY"
& "$env:USERPROFILE\.fly\bin\flyctl.exe" secrets set TIMEZONEDB_API_KEY="$TIMEZONEDB_API_KEY"
Write-Host ""

Write-Host "Step 4: Deploying the application..."
& "$env:USERPROFILE\.fly\bin\flyctl.exe" deploy
Write-Host ""

Write-Host "Step 5: Checking application status..."
& "$env:USERPROFILE\.fly\bin\flyctl.exe" status
Write-Host ""

Write-Host "Deployment complete! Your application should be accessible at:"
Write-Host "https://YOUR-APP-NAME.fly.dev"
Write-Host ""
Write-Host "(Replace YOUR-APP-NAME with the name you chose during setup)"
Write-Host ""

Read-Host "Press Enter to continue..."
