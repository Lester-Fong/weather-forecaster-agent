@echo off
echo Installing Fly.io CLI...
powershell -Command "iwr https://fly.io/install.ps1 -useb | iex"
echo.
echo Installation complete. Please restart your terminal or command prompt.
echo Then run: fly auth login
pause
