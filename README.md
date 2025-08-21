# Ardent POS

A robust, mobile-first SaaS Point of Sale platform for small to medium businesses.

## Features

- **Multi-tenant Architecture**: Isolated business accounts with role-based access
- **Mobile-First Design**: Optimized for touch interfaces and mobile devices
- **Real-time Inventory**: Track stock levels with automated alerts
- **Payment Processing**: Integrated with Paystack for secure transactions
- **Subscription Management**: Flexible pricing tiers with automated billing
- **Comprehensive Reporting**: Sales analytics and business insights

## Tech Stack

- **Frontend**: React.js with Vite, Tailwind CSS
- **Backend**: PHP with Composer, RESTful API
- **Database**: PostgreSQL with tenant isolation
- **Payments**: Paystack integration
- **Email**: SendGrid for notifications
- **Deployment**: Docker on Digital Ocean App Platform

## Project Structure

```
/
├── frontend/          # React.js application
├── backend/           # PHP API server
├── db/               # Database migrations and documentation
├── docker-compose.yml # Development environment
├── Dockerfile        # Production container
├── .do/              # Digital Ocean App Platform config
└── README.md         # This file
```

## Quick Start

### Development Setup

1. Clone the repository:
```bash
git clone https://github.com/mdYoungDOer/ardent-pos-project.git
cd ardent-pos-project
```

2. Start development environment:
```bash
docker-compose up -d
```

3. Install dependencies:
```bash
# Frontend
cd frontend
npm install
npm run dev

# Backend
cd ../backend
composer install
```

4. Set up environment variables:
```bash
cp .env.example .env
# Edit .env with your configuration
```

### Environment Variables

Create a `.env` file in the root directory:

```env
# Database
DB_HOST=localhost
DB_PORT=5432
DB_NAME=ardent_pos
DB_USER=postgres
DB_PASS=password

# JWT
JWT_SECRET=your-secret-key

# SendGrid
SENDGRID_API_KEY=your-sendgrid-key
SENDGRID_FROM_EMAIL=noreply@ardentpos.com

# Paystack
PAYSTACK_PUBLIC_KEY=pk_test_xxx
PAYSTACK_SECRET_KEY=sk_test_xxx

# App
APP_URL=http://localhost:3000
API_URL=http://localhost:8000
```

## Deployment to Digital Ocean App Platform

### Prerequisites

1. Digital Ocean account with App Platform access
2. Managed PostgreSQL database
3. Environment variables configured

### Deployment Steps

1. **Push to GitHub**: Ensure your code is pushed to the main branch

2. **Configure Environment Variables** in Digital Ocean App Platform:
   ```
   APP_URL=https://ardent-pos-app-sdq3t.ondigitalocean.app
   DB_HOST=db-postgresql-nyc3-77594-ardent-pos-do-user-24545475-0.g.db.ondigitalocean.com
   DB_PORT=25060
   DB_NAME=defaultdb
   DB_USER=doadmin
   DB_PASS=[your-secret-password]
   JWT_SECRET=[your-secret-jwt-key]
   CORS_ALLOWED_ORIGINS=https://ardent-pos-app-sdq3t.ondigitalocean.app
   PAYSTACK_PUBLIC_KEY=pk_test_af9bf6a2a8cf6ac7693e03ce00aeb5fdd48c25f3
   PAYSTACK_SECRET_KEY=[your-paystack-secret]
   SENDGRID_API_KEY=[your-sendgrid-key]
   SENDGRID_FROM_EMAIL=notify@ardentwebservices.com
   JWT_EXPIRY=3600
   API_URL=https://ardent-pos-app-sdq3t.ondigitalocean.app/api
   APP_DEBUG=false
   DEFAULT_CURRENCY=GHS
   DEFAULT_LOCALE=en-GH
   ```

3. **Deploy**: Digital Ocean will automatically build and deploy from your GitHub repository

4. **Verify Deployment**: Check the health endpoint at `/health.php`

### Troubleshooting

- **Database Connection Issues**: Verify PostgreSQL connection string and credentials
- **CORS Errors**: Check CORS_ALLOWED_ORIGINS environment variable
- **Build Failures**: Check Docker build logs in Digital Ocean dashboard
- **Environment Variables**: Ensure all required variables are set in App Platform

## User Roles

- **Super Admin**: Platform management
- **Admin**: Full tenant access
- **Manager**: Sales, inventory, customers, reports
- **Cashier**: Sales and checkout only
- **Inventory Staff**: Inventory management only
- **Viewer**: Reports access only

## API Documentation

API documentation is available at `/api/docs` when running the development server.

## Testing

```bash
# Frontend tests
cd frontend
npm test

# Backend tests
cd backend
composer test

# E2E tests
npm run test:e2e
```

## Health Checks

The application provides health check endpoints:
- `/health.php` - Basic health check
- `/api/health` - Detailed API health check

## License

MIT License - see LICENSE file for details.
