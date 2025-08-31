# 🚀 Digital Ocean App Platform Deployment Guide

## 📋 **Prerequisites**

1. **Digital Ocean Account** with App Platform access
2. **GitHub Repository** connected to your Digital Ocean account
3. **Database** (PostgreSQL) already set up on Digital Ocean
4. **doctl CLI** installed (optional, for command-line deployment)

## 🎯 **Deployment Steps**

### **Step 1: Prepare Your Repository**

1. **Push the latest code** to your GitHub repository:
   ```bash
   git add .
   git commit -m "Deploy: Digital Ocean App Platform configuration"
   git push origin main
   ```

### **Step 2: Create App in Digital Ocean Dashboard**

1. **Go to Digital Ocean Dashboard**
   - Navigate to Apps → Create App
   - Connect your GitHub repository

2. **Configure the App**
   - **App Name**: `ardent-pos`
   - **Repository**: `mdYoungDOer/ardent-pos-project`
   - **Branch**: `main`

### **Step 3: Configure Services**

#### **Frontend Service**
- **Source Directory**: `/frontend`
- **Build Command**: `npm install && npm run build`
- **Run Command**: `npm run preview`
- **Environment**: `Node.js`
- **Instance Size**: `Basic XXS`
- **Routes**: `/` (root)

#### **Backend Service**
- **Source Directory**: `/backend`
- **Build Command**: `composer install --no-dev --optimize-autoloader`
- **Run Command**: `php -S 0.0.0.0:8000 -t public`
- **Environment**: `PHP`
- **Instance Size**: `Basic XXS`
- **Routes**: `/backend`

### **Step 4: Set Environment Variables**

In the Digital Ocean App Platform dashboard, set these environment variables:

#### **Backend Environment Variables**
```
DB_HOST=db-postgresql-nyc3-77594-ardent-pos-do-user-24545475-0.g.db.ondigitalocean.com
DB_PORT=25060
DB_NAME=defaultdb
DB_USERNAME=doadmin
DB_PASSWORD=your_actual_database_password
JWT_SECRET=ardent_pos_super_secret_jwt_key_2024_very_long_and_secure_for_production_use
APP_URL=https://your-app-url.ondigitalocean.app
CORS_ALLOWED_ORIGINS=https://your-app-url.ondigitalocean.app
```

#### **Frontend Environment Variables**
```
VITE_API_BASE_URL=https://your-app-url.ondigitalocean.app/backend
NODE_ENV=production
```

### **Step 5: Deploy**

1. **Click "Create Resources"** in the Digital Ocean dashboard
2. **Wait for deployment** to complete (usually 5-10 minutes)
3. **Note the app URL** provided by Digital Ocean

## 🔧 **Post-Deployment Configuration**

### **Step 1: Test the Application**

1. **Health Check**: Visit `https://your-app-url.ondigitalocean.app/backend/public/system-health-check.php`
2. **Super Admin Login**: Visit `https://your-app-url.ondigitalocean.app/auth/super-admin`
3. **Support Page**: Visit `https://your-app-url.ondigitalocean.app/support`

### **Step 2: Verify Environment Variables**

1. **Check the health check results**
2. **Verify database connection**
3. **Test authentication endpoints**

### **Step 3: Set Up Custom Domain (Optional)**

1. **Add custom domain** in Digital Ocean App Platform
2. **Update DNS records** to point to your app
3. **Update environment variables** with new domain

## 🚨 **Troubleshooting**

### **Common Issues**

1. **Build Failures**
   - Check build logs in Digital Ocean dashboard
   - Verify all dependencies are in package.json/composer.json

2. **Database Connection Issues**
   - Verify database credentials
   - Check database firewall settings
   - Ensure database is accessible from App Platform

3. **Frontend Not Loading**
   - Check if frontend build completed successfully
   - Verify API base URL configuration
   - Check browser console for errors

4. **API Endpoints Not Working**
   - Verify backend service is running
   - Check environment variables
   - Test endpoints directly

### **Debugging Commands**

```bash
# Check app status
doctl apps list

# View app logs
doctl apps logs your-app-id

# Update app
doctl apps update your-app-id --spec .do/app.yaml
```

## 📞 **Support**

If you encounter issues:

1. **Check Digital Ocean App Platform logs**
2. **Run the health check script**
3. **Verify all environment variables are set correctly**
4. **Contact support** with specific error messages

## 🎉 **Success Indicators**

Your deployment is successful when:

- ✅ Health check shows "HEALTHY" status
- ✅ Super admin login works
- ✅ Support page loads without blank screen
- ✅ All API endpoints respond correctly
- ✅ Frontend assets load properly

---

**Last Updated**: August 31, 2025
**Status**: Ready for deployment
