#!/bin/bash

# Ardent POS Digital Ocean App Platform Deployment Script
echo "🚀 Starting Ardent POS Deployment..."

# Check if doctl is installed
if ! command -v doctl &> /dev/null; then
    echo "❌ doctl CLI is not installed. Please install it first:"
    echo "   https://docs.digitalocean.com/reference/doctl/how-to/install/"
    exit 1
fi

# Check if user is authenticated
if ! doctl auth list &> /dev/null; then
    echo "❌ Please authenticate with Digital Ocean first:"
    echo "   doctl auth init"
    exit 1
fi

# Build frontend
echo "📦 Building frontend..."
cd frontend
npm install
npm run build
cd ..

# Commit and push changes
echo "📤 Pushing changes to GitHub..."
git add .
git commit -m "Deploy: Update Digital Ocean App Platform configuration"
git push origin main

# Deploy to Digital Ocean App Platform
echo "🌊 Deploying to Digital Ocean App Platform..."
doctl apps create --spec .do/app.yaml

echo "✅ Deployment initiated!"
echo "📋 Next steps:"
echo "   1. Check your Digital Ocean dashboard for deployment status"
echo "   2. Set up environment variables in the App Platform dashboard"
echo "   3. Test the application once deployment is complete"
echo ""
echo "🔗 Your app will be available at the URL provided in the Digital Ocean dashboard"
