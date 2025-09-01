#!/bin/bash

echo "Installing Fly.io CLI..."

# Determine OS
if [[ "$OSTYPE" == "msys"* || "$OSTYPE" == "cygwin"* ]]; then
    # Windows Git Bash or Cygwin
    echo "Detected Windows Git Bash/MINGW environment"
    echo "Installing Fly.io CLI for Windows..."
    
    # Create a temporary PowerShell script
    TMP_PS_SCRIPT="/tmp/install_fly.ps1"
    echo "Invoke-WebRequest -Uri https://fly.io/install.ps1 -UseBasicParsing | Invoke-Expression" > "$TMP_PS_SCRIPT"
    
    # Execute the PowerShell script
    powershell -ExecutionPolicy Bypass -File "$TMP_PS_SCRIPT"
    
    # Clean up
    rm -f "$TMP_PS_SCRIPT"
    
    # Update PATH for current session
    export PATH="$PATH:$USERPROFILE/.fly/bin"
    
    echo "Added Fly.io CLI to PATH for this session: $USERPROFILE/.fly/bin"
    echo "For permanent access, you might need to add this to your PATH manually."
    
elif [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    echo "Detected macOS, installing Fly.io CLI..."
    curl -L https://fly.io/install.sh | sh
    
    # Update PATH for current session
    export PATH="$PATH:$HOME/.fly/bin"
    
elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
    # Linux
    echo "Detected Linux, installing Fly.io CLI..."
    curl -L https://fly.io/install.sh | sh
    
    # Update PATH for current session
    export PATH="$PATH:$HOME/.fly/bin"
fi

echo ""
echo "Installation complete. You may need to restart your terminal or add Fly.io to your PATH."
echo "After that, run: fly auth login"
echo ""
echo "Press Enter to continue..."
read
