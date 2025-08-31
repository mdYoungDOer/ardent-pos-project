# Ardent POS - Enterprise Multi-Tenant Point of Sale System

A modern, enterprise-grade Point of Sale system built with React frontend and PHP backend, featuring multi-tenant architecture, comprehensive business management tools, and robust security.

## 🏗️ Architecture

### Frontend
- **React 18** with Vite for fast development
- **Tailwind CSS** for styling with custom color scheme
- **React Router** for navigation
- **Zustand** for state management
- **Axios** for API calls with automatic authentication
- **Responsive Design** with mobile-first approach

### Backend
- **PHP 8.2** with Apache
- **PostgreSQL** database with UUID-based schema
- **JWT Authentication** with unified token system
- **Multi-tenant Architecture** with data isolation
- **Role-Based Access Control** (RBAC)
- **RESTful API** with comprehensive endpoints

### Database
- **PostgreSQL** with UUID primary keys
- **Multi-tenant schema** with proper isolation
- **Performance indexes** for optimal query speed
- **Audit logging** for security compliance
- **Foreign key constraints** for data integrity

### Color Scheme
- **Primary**: `#e41e5b` (buttons, CTAs, headers)
- **Accent 1**: `#9a0864` (secondary buttons, accents)
- **Dark**: `#2c2c2c` (text, dark backgrounds)
- **Neutral**: `#746354` (borders, subtle backgrounds)
- **Highlight**: `#a67c00` (alerts, success messages)

## 🚀 Quick Start

1. **Clone the repository**
   ```bash
   git clone https://github.com/mdYoungDOer/ardent-pos-project.git
   cd ardent-pos-project
   ```

2. **Set up environment variables**
   ```
   APP_URL=https://your-app-url.ondigitalocean.app
   DB_HOST=your-db-host
   DB_PORT=25060
   DB_NAME=defaultdb
   DB_USERNAME=doadmin
   DB_PASSWORD=your-db-password
   JWT_SECRET=your-secret-key
   CORS_ALLOWED_ORIGINS=https://your-app-url.ondigitalocean.app
   PAYSTACK_SECRET_KEY=your-paystack-secret
   PAYSTACK_PUBLIC_KEY=your-paystack-public
   ```

3. **Initialize Database**
   - Access: `https://your-app-url.ondigitalocean.app/backend/public/setup-unified-database.php`
   - Creates all tables, indexes, and default data
   - Sets up Super Admin account automatically

4. **Access the System**
   - **Super Admin**: Use the credentials created during setup
   - **Client Registration**: Available at the main landing page

## 🎯 Core Features

### 🔐 Authentication & Security
- **Multi-role Authentication**: Super Admin, Admin, Manager, Cashier, Inventory Staff, Viewer
- **JWT Token System**: Secure, stateless authentication
- **Unified Auth System**: Supports both JWT and legacy tokens
- **Role-Based Access Control**: Granular permissions per role
- **Tenant Isolation**: Complete data separation between businesses
- **Audit Logging**: Comprehensive activity tracking

### 🏢 Multi-Tenant Architecture
- **Business Isolation**: Each tenant has completely separate data
- **Custom Subdomains**: Unique URLs for each business
- **Shared Infrastructure**: Efficient resource utilization
- **Scalable Design**: Easy to add new tenants

### 💼 Client Dashboard Features
- **📊 Dashboard**: Real-time analytics and business overview
- **🛒 POS Terminal**: Full-featured point of sale with full-page mode
- **📦 Product Management**: Complete CRUD operations
- **🏷️ Category Management**: Hierarchical product organization
- **📍 Location Management**: Multi-location support
- **📋 Inventory Management**: Stock tracking and adjustments
- **💰 Sales Processing**: Complete transaction workflow
- **👥 Customer Management**: CRM with customer database
- **📈 Reports & Analytics**: Comprehensive business insights
- **👤 User Management**: Staff accounts and permissions
- **🏷️ Sub-categories**: Detailed product organization
- **🎫 Discounts & Coupons**: Promotional management
- **🎫 Support System**: Integrated help desk
- **⚙️ Settings**: Business configuration

### 🎛️ Super Admin Dashboard Features
- **📊 System Analytics**: Platform-wide statistics
- **🏢 Tenant Management**: Business account oversight
- **👥 User Management**: System-wide user administration
- **💳 Subscription Management**: Plan and billing oversight
- **💰 Billing Overview**: Financial management
- **📄 Invoice Management**: Payment tracking
- **📧 Contact Submissions**: Customer inquiries
- **📚 Knowledgebase Management**: Help content creation
- **🎫 Support Ticket Management**: System-wide support
- **🔧 System Health**: Platform monitoring
- **📋 Activity Logs**: Comprehensive audit trail
- **🔒 Security Management**: System security oversight
- **⚙️ System Settings**: Platform configuration

### 🛒 POS Terminal Features
- **Full-Page Mode**: Immersive sales experience
- **Product Search**: Quick item lookup
- **Barcode Scanning**: Hardware integration ready
- **Payment Processing**: Multiple payment methods
- **Receipt Generation**: Professional invoices
- **Customer Management**: Integrated CRM
- **Discount Application**: Promotional pricing
- **Tax Calculation**: Automatic tax handling
- **Inventory Updates**: Real-time stock management

### 📊 Reporting & Analytics
- **Sales Reports**: Daily, weekly, monthly, yearly
- **Inventory Reports**: Stock levels and movements
- **Customer Reports**: Purchase history and behavior
- **Financial Reports**: Revenue and profit analysis
- **Performance Metrics**: Business KPIs
- **Export Capabilities**: PDF and CSV formats

### 💳 Payment & Billing
- **Paystack Integration**: Secure payment processing
- **Subscription Plans**: Multiple pricing tiers
- **Invoice Management**: Professional billing
- **Payment Tracking**: Transaction history
- **Webhook Support**: Real-time payment updates

### 🎫 Support & Knowledgebase
- **Help Desk System**: Ticket creation and management
- **Knowledge Base**: Self-service help articles
- **Category Organization**: Structured help content
- **Search Functionality**: Quick help lookup
- **Internal Notes**: Staff communication

## 🔧 Technical Features

### API Endpoints
- **Authentication**: Login, register, verify, refresh
- **Dashboard**: Analytics and statistics
- **Products**: Full CRUD operations
- **Categories**: Hierarchical management
- **Inventory**: Stock tracking and adjustments
- **Sales**: Transaction processing
- **Customers**: CRM operations
- **Users**: Staff management
- **Reports**: Analytics and exports
- **Support**: Ticket management
- **Knowledgebase**: Help content
- **Billing**: Subscription and payment management

### Security Features
- **Input Validation**: Comprehensive data sanitization
- **SQL Injection Protection**: Prepared statements
- **XSS Protection**: Output encoding
- **CSRF Protection**: Token-based validation
- **Rate Limiting**: API abuse prevention
- **Error Handling**: Secure error messages
- **Logging**: Comprehensive audit trails

### Performance Features
- **Database Indexing**: Optimized queries
- **Caching**: Response optimization
- **Lazy Loading**: Efficient data loading
- **Pagination**: Large dataset handling
- **Compression**: Reduced bandwidth usage

## 🛠️ Development

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

### Database Setup
```bash
# Access the setup script via web browser
# Creates all tables, indexes, and sample data
```

## 🚀 Deployment

The app is configured for Digital Ocean App Platform deployment with:
- **Multi-stage Docker build** for optimized images
- **Apache web server** with proper configuration
- **PostgreSQL database** with connection pooling
- **Environment-based configuration** for flexibility
- **SSL/TLS encryption** for security
- **CDN integration** for performance

## 📋 System Requirements

### Server Requirements
- **PHP**: 8.2 or higher
- **PostgreSQL**: 12 or higher
- **Apache**: 2.4 or higher
- **Memory**: 512MB minimum, 1GB recommended
- **Storage**: 10GB minimum

### Client Requirements
- **Browser**: Modern browsers (Chrome, Firefox, Safari, Edge)
- **JavaScript**: Enabled
- **Network**: Stable internet connection

## 🔒 Security Considerations

- **Environment Variables**: All sensitive data stored securely
- **Database Credentials**: Encrypted and isolated
- **API Keys**: Protected and rotated regularly
- **User Passwords**: Hashed with industry-standard algorithms
- **Session Management**: Secure token handling
- **Data Encryption**: Sensitive data encrypted at rest

## 📈 Scalability

- **Horizontal Scaling**: Multi-server deployment ready
- **Database Optimization**: Efficient query patterns
- **Caching Strategy**: Redis integration ready
- **Load Balancing**: Multiple instance support
- **CDN Integration**: Global content delivery

## 🤝 Support

- **Documentation**: Comprehensive guides and tutorials
- **Knowledge Base**: Self-service help articles
- **Support Tickets**: Integrated help desk system
- **Community**: User forums and discussions

## 📄 License

MIT License - See LICENSE file for details

## 🏆 Enterprise Features

- **Multi-tenant Architecture**: Complete business isolation
- **Role-Based Access Control**: Granular permissions
- **Audit Logging**: Comprehensive activity tracking
- **API Integration**: Third-party system connectivity
- **Customization**: Flexible configuration options
- **Backup & Recovery**: Data protection strategies
- **Monitoring**: System health and performance tracking

---

**Ardent POS** - Empowering businesses with modern, secure, and scalable point of sale solutions.
