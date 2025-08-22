# Ardent POS - Complete Rewrite

A modern, simplified Point of Sale system built with React frontend and PHP backend.

## Architecture

### Frontend
- **React 18** with Vite for fast development
- **Tailwind CSS** for styling with custom color scheme
- **React Router** for navigation
- **Zustand** for state management
- **Axios** for API calls

### Backend
- **PHP 8.2** with Apache
- **PostgreSQL** database
- **JWT** for authentication
- **Simple REST API** without complex routing

### Color Scheme
- **Primary**: `#e41e5b` (buttons, CTAs, headers)
- **Accent 1**: `#9a0864` (secondary buttons, accents)
- **Dark**: `#2c2c2c` (text, dark backgrounds)
- **Neutral**: `#746354` (borders, subtle backgrounds)
- **Highlight**: `#a67c00` (alerts, success messages)

## Quick Start

1. **Clone the repository**
   ```bash
   git clone https://github.com/mdYoungDOer/ardent-pos-project.git
   cd ardent-pos-project
   ```

2. **Set up environment variables in Digital Ocean**
   ```
   APP_URL=https://your-app-url.ondigitalocean.app
   DB_HOST=your-db-host
   DB_PORT=25060
   DB_NAME=defaultdb
   DB_USERNAME=doadmin
   DB_PASSWORD=your-db-password
   JWT_SECRET=your-secret-key
   CORS_ALLOWED_ORIGINS=https://your-app-url.ondigitalocean.app
   ```

3. **Deploy to Digital Ocean App Platform**
   - Connect your GitHub repository
   - Set environment variables
   - Deploy

4. **Create Super Admin**
   - Visit: `https://your-app-url.ondigitalocean.app/setup-admin.php`
   - Use secret key: `ardent-pos-2024`
   - Create admin account

## Features

- ✅ **Authentication**: Login/Register with JWT
- ✅ **Dashboard**: Sales overview and analytics
- ✅ **Products**: Add, edit, delete products
- ✅ **Sales**: Process transactions
- ✅ **Customers**: Manage customer database
- ✅ **Reports**: Sales and inventory reports
- ✅ **Settings**: App configuration

## API Endpoints

### Authentication
- `POST /auth/login.php` - User login
- `POST /auth/register.php` - User registration
- `POST /auth/verify.php` - Verify JWT token

### Products
- `GET /api/products.php` - List products
- `POST /api/products.php` - Create product
- `PUT /api/products.php` - Update product
- `DELETE /api/products.php` - Delete product

### Sales
- `GET /api/sales.php` - List sales
- `POST /api/sales.php` - Create sale
- `GET /api/sales/reports.php` - Sales reports

### Customers
- `GET /api/customers.php` - List customers
- `POST /api/customers.php` - Create customer
- `PUT /api/customers.php` - Update customer

## Development

### Frontend Development
```bash
cd frontend
npm install
npm run dev
```

### Backend Development
```bash
cd backend
composer install
php -S localhost:8000 -t public
```

## Deployment

The app is configured for Digital Ocean App Platform deployment with:
- Multi-stage Docker build
- Apache web server
- PostgreSQL database
- Environment-based configuration

## License

MIT License
