#!/bin/bash

# Script to build and push Docker image to Docker Hub
# Usage: ./docker-hub-deploy.sh yourusername

if [ -z "$1" ]; then
  echo "Please provide your Docker Hub username"
  echo "Usage: ./docker-hub-deploy.sh yourusername"
  exit 1
fi

USERNAME="$1"
IMAGE_NAME="weather-forecaster-agent"
TAG="latest"

echo "Building Docker image: $USERNAME/$IMAGE_NAME:$TAG"
docker build -t $USERNAME/$IMAGE_NAME:$TAG .

echo "Pushing Docker image to Docker Hub"
docker push $USERNAME/$IMAGE_NAME:$TAG

echo "Done! Your image is available at: $USERNAME/$IMAGE_NAME:$TAG"
echo "You can now deploy this image on Fly.io using the web interface"
