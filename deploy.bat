@echo off
REM Ardent POS Deployment Script for Digital Ocean App Platform

echo 🚀 Starting Ardent POS deployment...

REM Check if we're in the right directory
if not exist "Dockerfile" (
    echo ❌ Error: Please run this script from the project root directory
    exit /b 1
)

REM Build the application
echo 📦 Building application...
docker build -t ardent-pos .

if %errorlevel% neq 0 (
    echo ❌ Build failed!
    exit /b 1
)

echo ✅ Build completed successfully!
echo.
echo 📋 Next steps:
echo 1. Push your changes to the main branch
echo 2. Digital Ocean will automatically deploy the new version
echo 3. Check the deployment status in your Digital Ocean dashboard
echo 4. Test the application at: https://ardent-pos-app-sdq3t.ondigitalocean.app
echo.
echo 🔧 Environment variables to verify in Digital Ocean:
echo - DB_HOST: db-postgresql-nyc3-77594-ardent-pos-do-user-24545475-0.g.db.ondigitalocean.com
echo - DB_PORT: 25060
echo - DB_NAME: defaultdb
echo - DB_USER: doadmin
echo - DB_PASS: [your-secret-password]
echo - JWT_SECRET: [your-secret-jwt-key]
echo - PAYSTACK_SECRET_KEY: [your-paystack-secret]
echo - SENDGRID_API_KEY: [your-sendgrid-key]
echo.
echo 🎉 Deployment script completed!
pause
