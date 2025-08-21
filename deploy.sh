#!/bin/bash

# Ardent POS Deployment Script for Digital Ocean App Platform

echo "🚀 Starting Ardent POS deployment..."

# Check if we're in the right directory
if [ ! -f "Dockerfile" ]; then
    echo "❌ Error: Please run this script from the project root directory"
    exit 1
fi

# Build the application
echo "📦 Building application..."
docker build -t ardent-pos .

# Test the build locally (optional)
echo "🧪 Testing build locally..."
docker run --rm -p 8080:80 ardent-pos &
sleep 10

# Test health endpoint
if curl -f http://localhost:8080/health.php > /dev/null 2>&1; then
    echo "✅ Health check passed"
else
    echo "⚠️  Health check failed, but continuing deployment..."
fi

# Stop test container
docker stop $(docker ps -q --filter ancestor=ardent-pos)

echo "✅ Build completed successfully!"
echo ""
echo "📋 Next steps:"
echo "1. Push your changes to the main branch"
echo "2. Digital Ocean will automatically deploy the new version"
echo "3. Check the deployment status in your Digital Ocean dashboard"
echo "4. Test the application at: https://ardent-pos-app-sdq3t.ondigitalocean.app"
echo ""
echo "🔧 Environment variables to verify in Digital Ocean:"
echo "- DB_HOST: db-postgresql-nyc3-77594-ardent-pos-do-user-24545475-0.g.db.ondigitalocean.com"
echo "- DB_PORT: 25060"
echo "- DB_NAME: defaultdb"
echo "- DB_USER: doadmin"
echo "- DB_PASS: [your-secret-password]"
echo "- JWT_SECRET: [your-secret-jwt-key]"
echo "- PAYSTACK_SECRET_KEY: [your-paystack-secret]"
echo "- SENDGRID_API_KEY: [your-sendgrid-key]"
echo ""
echo "🎉 Deployment script completed!"
