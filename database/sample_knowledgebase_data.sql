-- Sample Knowledge Base Data for Ardent POS Support Portal
-- This file contains curated articles covering all major platform features

-- First, check if categories exist and insert them if they don't
DO $$
BEGIN
    -- Insert categories only if they don't exist
    IF NOT EXISTS (SELECT 1 FROM knowledgebase_categories WHERE id = 1) THEN
        INSERT INTO knowledgebase_categories (id, name, slug, description, icon, sort_order) VALUES
        (1, 'Getting Started', 'getting-started', 'Essential guides for new users to get up and running quickly', 'help-circle', 1);
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM knowledgebase_categories WHERE id = 2) THEN
        INSERT INTO knowledgebase_categories (id, name, slug, description, icon, sort_order) VALUES
        (2, 'Sales & Transactions', 'sales-transactions', 'Everything you need to know about processing sales and managing transactions', 'shopping-cart', 2);
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM knowledgebase_categories WHERE id = 3) THEN
        INSERT INTO knowledgebase_categories (id, name, slug, description, icon, sort_order) VALUES
        (3, 'Inventory Management', 'inventory-management', 'Complete guide to managing your product catalog and stock levels', 'truck', 3);
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM knowledgebase_categories WHERE id = 4) THEN
        INSERT INTO knowledgebase_categories (id, name, slug, description, icon, sort_order) VALUES
        (4, 'Customer Management', 'customer-management', 'Tools and techniques for managing your customer database', 'users', 4);
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM knowledgebase_categories WHERE id = 5) THEN
        INSERT INTO knowledgebase_categories (id, name, slug, description, icon, sort_order) VALUES
        (5, 'Reports & Analytics', 'reports-analytics', 'Understanding your business data and generating insights', 'bar-chart-2', 5);
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM knowledgebase_categories WHERE id = 6) THEN
        INSERT INTO knowledgebase_categories (id, name, slug, description, icon, sort_order) VALUES
        (6, 'Hardware & Setup', 'hardware-setup', 'Setting up and configuring POS hardware and devices', 'monitor', 6);
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM knowledgebase_categories WHERE id = 7) THEN
        INSERT INTO knowledgebase_categories (id, name, slug, description, icon, sort_order) VALUES
        (7, 'Integrations', 'integrations', 'Connecting your POS with payment gateways and e-commerce platforms', 'settings', 7);
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM knowledgebase_categories WHERE id = 8) THEN
        INSERT INTO knowledgebase_categories (id, name, slug, description, icon, sort_order) VALUES
        (8, 'Security & Permissions', 'security-permissions', 'Managing user access, roles, and system security', 'shield', 8);
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM knowledgebase_categories WHERE id = 9) THEN
        INSERT INTO knowledgebase_categories (id, name, slug, description, icon, sort_order) VALUES
        (9, 'Troubleshooting', 'troubleshooting', 'Solutions for common issues and system maintenance', 'tool', 9);
    END IF;
END $$;

-- Insert sample knowledge base articles
INSERT INTO knowledgebase (category_id, title, content, slug, excerpt, tags, published, helpful_count, not_helpful_count) VALUES
-- Getting Started Category
(1, 'Getting Started with Ardent POS', 
'# Welcome to Ardent POS!

## Quick Setup Guide

### 1. Account Registration
- Visit our website and click "Get Started"
- Choose your business package (Starter, Professional, Business, Enterprise or Premium)
- Fill in your business details and create your account
- Verify your email address

### 2. Initial Configuration
- Set up your business profile with name, address, and contact information
- Configure your tax settings and currency preferences
- Add your first products or services
- Set up payment methods

### 3. Staff Management
- Invite team members to your account
- Assign roles and permissions
- Set up employee PINs for POS access

### 4. First Sale
- Open the POS interface
- Add items to cart
- Process payment
- Print or email receipt

## Need Help?
If you encounter any issues during setup, our support team is available 24/7. You can also use our live chat widget for instant assistance.', 
'getting-started-with-ardent-pos', 
'Complete guide to setting up your Ardent POS account and making your first sale', 
'getting started,setup,first sale,registration', 
true, 45, 2),

(1, 'Understanding Your Dashboard', 
'# Dashboard Overview

Your Ardent POS dashboard provides real-time insights into your business performance.

## Key Metrics
- **Total Sales**: Daily, weekly, and monthly revenue
- **Transaction Count**: Number of sales completed
- **Average Order Value**: Mean transaction amount
- **Top Products**: Best-selling items
- **Customer Growth**: New customer registrations

## Navigation
- **Sales**: View and manage transactions
- **Inventory**: Track stock levels and products
- **Customers**: Manage customer database
- **Reports**: Generate business insights
- **Settings**: Configure system preferences

## Quick Actions
- Process new sale
- Add new product
- View recent transactions
- Generate reports
- Manage staff

## Customization
You can customize your dashboard layout and choose which metrics to display prominently.', 
'understanding-your-dashboard', 
'Learn how to navigate and customize your Ardent POS dashboard for optimal business insights', 
'dashboard,metrics,navigation,overview', 
true, 32, 1),

-- Sales & Transactions Category
(2, 'Processing Sales Transactions', 
'# How to Process Sales

## Step-by-Step Guide

### 1. Open POS Interface
- Click "New Sale" from your dashboard
- Or use the POS terminal directly

### 2. Add Items
- Search for products by name, SKU, or barcode
- Click items to add to cart
- Adjust quantities as needed
- Apply discounts if applicable

### 3. Customer Information
- Select existing customer or create new one
- Add customer notes if needed
- Apply customer-specific discounts

### 4. Payment Processing
- Choose payment method (cash, card, mobile payment)
- Enter payment amount
- Process transaction
- Handle change if paying with cash

### 5. Receipt Options
- Print receipt
- Email receipt to customer
- Send SMS receipt
- Save to cloud

## Tips
- Use keyboard shortcuts for faster processing
- Set up favorite items for quick access
- Enable barcode scanning for efficiency
- Train staff on proper procedures', 
'processing-sales-transactions', 
'Complete guide to processing sales transactions in Ardent POS with step-by-step instructions', 
'sales,transactions,payment,receipt', 
true, 67, 3),

(2, 'Managing Refunds and Returns', 
'# Refunds and Returns

## Return Policy Setup
- Configure your return policy in Settings
- Set time limits for returns
- Define acceptable return conditions
- Set up refund methods

## Processing Returns

### 1. Locate Original Transaction
- Search by receipt number, date, or customer
- Verify purchase details
- Check return eligibility

### 2. Select Items for Return
- Choose items to return
- Specify return reason
- Adjust quantities if partial return

### 3. Process Refund
- Choose refund method (original payment or store credit)
- Apply restocking fees if applicable
- Process refund transaction
- Update inventory

### 4. Documentation
- Generate return receipt
- Update customer records
- Log return for reporting

## Best Practices
- Always verify original purchase
- Document return reasons
- Train staff on policy
- Monitor return patterns', 
'managing-refunds-and-returns', 
'Learn how to set up and process refunds and returns in Ardent POS', 
'refunds,returns,policy,refund processing', 
true, 28, 1),

-- Inventory Management Category
(3, 'Adding and Managing Products', 
'# Product Management

## Adding New Products

### 1. Basic Information
- Product name and description
- SKU or barcode
- Category and subcategory
- Brand and model

### 2. Pricing
- Cost price
- Selling price
- Tax settings
- Discount options

### 3. Inventory Settings
- Initial stock quantity
- Low stock alerts
- Reorder points
- Supplier information

### 4. Additional Details
- Product images
- Weight and dimensions
- Shipping settings
- SEO keywords

## Bulk Operations
- Import products via CSV
- Bulk price updates
- Mass category changes
- Inventory adjustments

## Product Variants
- Size variations
- Color options
- Material differences
- Custom attributes', 
'adding-and-managing-products', 
'Complete guide to adding and managing products in your Ardent POS inventory system', 
'products,inventory,SKU,pricing', 
true, 89, 4),

(3, 'Inventory Tracking and Alerts', 
'# Inventory Management

## Stock Tracking
- Real-time stock levels
- Automatic updates with sales
- Purchase order tracking
- Supplier deliveries

## Low Stock Alerts
- Set minimum stock levels
- Email notifications
- Dashboard warnings
- Automatic reorder suggestions

## Inventory Reports
- Stock value reports
- Movement history
- Dead stock analysis
- Turnover rates

## Physical Counts
- Schedule inventory counts
- Count sheets generation
- Variance reporting
- Adjustment processing

## Best Practices
- Regular stock counts
- Monitor fast-moving items
- Track seasonal trends
- Maintain accurate records', 
'inventory-tracking-and-alerts', 
'Learn how to track inventory levels and set up automated alerts for low stock', 
'inventory,tracking,alerts,stock levels', 
true, 56, 2),

-- Customer Management Category
(4, 'Customer Database Management', 
'# Customer Management

## Adding Customers

### 1. Manual Entry
- Name and contact information
- Address details
- Birthday and preferences
- Notes and tags

### 2. Import Options
- CSV file import
- Bulk customer creation
- Data validation
- Duplicate checking

## Customer Profiles
- Purchase history
- Preferences and notes
- Communication history
- Loyalty points

## Customer Segmentation
- By purchase value
- By frequency
- By location
- By preferences

## Communication Tools
- Email marketing
- SMS notifications
- Birthday greetings
- Promotional campaigns', 
'customer-database-management', 
'Learn how to manage your customer database, add new customers, and segment your customer base', 
'customers,database,segmentation,communication', 
true, 43, 1),

(4, 'Loyalty Programs and Rewards', 
'# Loyalty System

## Setting Up Loyalty Program
- Points per dollar spent
- Redemption rates
- Expiration policies
- Tier levels

## Customer Benefits
- Points accumulation
- Reward redemption
- Exclusive offers
- Birthday rewards

## Program Management
- Monitor participation
- Track redemptions
- Analyze effectiveness
- Adjust strategies

## Marketing Integration
- Email campaigns
- SMS notifications
- In-store promotions
- Social media integration', 
'loyalty-programs-and-rewards', 
'Set up and manage customer loyalty programs to increase retention and sales', 
'loyalty,rewards,points,retention', 
true, 34, 2),

-- Reports & Analytics Category
(5, 'Understanding Sales Reports', 
'# Sales Analytics

## Key Reports

### 1. Sales Summary
- Total revenue by period
- Transaction counts
- Average order value
- Growth trends

### 2. Product Performance
- Top-selling items
- Revenue by product
- Inventory turnover
- Profit margins

### 3. Customer Insights
- Customer acquisition
- Retention rates
- Lifetime value
- Purchase patterns

### 4. Staff Performance
- Sales by employee
- Transaction counts
- Commission tracking
- Productivity metrics

## Report Customization
- Date range selection
- Filter options
- Export formats
- Scheduled reports

## Data Visualization
- Charts and graphs
- Trend analysis
- Comparative views
- Interactive dashboards', 
'understanding-sales-reports', 
'Learn how to interpret sales reports and use analytics to improve your business performance', 
'reports,analytics,sales,performance', 
true, 78, 3),

(5, 'Financial Reporting and Tax', 
'# Financial Management

## Tax Configuration
- Tax rates by location
- Product tax categories
- Tax exemption rules
- Tax reporting periods

## Financial Reports
- Profit and loss statements
- Cash flow analysis
- Tax summaries
- Expense tracking

## Payment Reconciliation
- Daily cash counts
- Credit card settlements
- Bank deposits
- Discrepancy resolution

## Compliance
- Tax filing requirements
- Record keeping
- Audit trails
- Data retention

## Best Practices
- Regular reconciliations
- Accurate tax calculations
- Proper documentation
- Professional consultation', 
'financial-reporting-and-tax', 
'Configure tax settings and generate financial reports for compliance and business insights', 
'financial,tax,reports,compliance', 
true, 45, 1),

-- Hardware & Setup Category
(6, 'POS Hardware Setup', 
'# Hardware Configuration

## Essential Equipment
- Cash drawer
- Receipt printer
- Barcode scanner
- Payment terminal

## Connection Setup
- USB connections
- Network configuration
- Driver installation
- Device testing

## Printer Configuration
- Paper size settings
- Print quality
- Header/footer customization
- Logo printing

## Scanner Setup
- Barcode format support
- Scan speed settings
- Sound feedback
- Error handling

## Troubleshooting
- Connection issues
- Driver problems
- Hardware conflicts
- Performance optimization', 
'pos-hardware-setup', 
'Complete guide to setting up and configuring POS hardware including printers, scanners, and payment terminals', 
'hardware,setup,printer,scanner', 
true, 67, 4),

(6, 'Mobile POS Configuration', 
'# Mobile POS

## Device Requirements
- iOS or Android device
- Minimum specifications
- Operating system version
- Storage requirements

## App Installation
- Download from app store
- Account setup
- Device registration
- Sync with main system

## Offline Mode
- Local data storage
- Sync when online
- Conflict resolution
- Data integrity

## Security Features
- PIN protection
- Session timeouts
- Data encryption
- Remote wipe capability

## Best Practices
- Regular app updates
- Secure device usage
- Backup procedures
- Staff training', 
'mobile-pos-configuration', 
'Set up mobile POS devices for on-the-go sales and inventory management', 
'mobile,tablet,offline,security', 
true, 38, 2),

-- Integrations Category
(7, 'Payment Gateway Integration', 
'# Payment Processing

## Supported Gateways
- Stripe
- PayPal
- Square
- Authorize.net
- Local payment providers

## Setup Process
- Account creation
- API key configuration
- Webhook setup
- Test transactions

## Security Compliance
- PCI DSS requirements
- Data encryption
- Tokenization
- Fraud protection

## Transaction Types
- Credit/debit cards
- Digital wallets
- ACH transfers
- International payments

## Troubleshooting
- Declined transactions
- Processing errors
- Refund issues
- Settlement delays', 
'payment-gateway-integration', 
'Integrate payment gateways to accept various payment methods securely', 
'payment,gateway,stripe,security', 
true, 92, 5),

(7, 'E-commerce Integration', 
'# Online Store Integration

## Platform Support
- Shopify integration
- WooCommerce connection
- Custom API integration
- Multi-channel selling

## Sync Features
- Product synchronization
- Inventory updates
- Order processing
- Customer data

## Order Management
- Order status tracking
- Fulfillment processing
- Shipping integration
- Return management

## Analytics
- Cross-channel reporting
- Customer journey tracking
- Conversion analysis
- Performance metrics

## Best Practices
- Regular sync monitoring
- Data consistency checks
- Error handling
- Performance optimization', 
'e-commerce-integration', 
'Connect your POS system with e-commerce platforms for seamless online and offline sales', 
'ecommerce,integration,shopify,woocommerce', 
true, 41, 2),

-- Security & Permissions Category
(8, 'User Roles and Permissions', 
'# Access Control

## Role Types
- Super Admin: Full system access
- Manager: Sales and reporting access
- Cashier: Basic POS operations
- Inventory: Stock management only
- Reports: Read-only access

## Permission Settings
- Sales operations
- Inventory management
- Customer data access
- Financial reports
- System settings

## Security Features
- Password requirements
- Two-factor authentication
- Session management
- Activity logging

## Best Practices
- Principle of least privilege
- Regular access reviews
- Strong passwords
- Staff training

## Emergency Access
- Account recovery
- Emergency procedures
- Backup access
- Contact protocols', 
'user-roles-and-permissions', 
'Set up user roles and permissions to control access to different parts of your POS system', 
'security,roles,permissions,access control', 
true, 54, 2),

(8, 'Data Backup and Recovery', 
'# Data Protection

## Backup Types
- Automated daily backups
- Manual backups
- Cloud storage
- Local storage

## Backup Content
- Transaction data
- Customer information
- Product catalog
- System settings

## Recovery Procedures
- Data restoration
- System recovery
- Point-in-time recovery
- Disaster recovery

## Security Measures
- Encrypted backups
- Secure storage
- Access controls
- Regular testing

## Compliance
- Data retention policies
- Privacy regulations
- Audit requirements
- Legal obligations', 
'data-backup-and-recovery', 
'Implement comprehensive backup and recovery procedures to protect your business data', 
'backup,recovery,data protection,security', 
true, 36, 1),

-- Troubleshooting Category
(9, 'Common POS Issues and Solutions', 
'# Troubleshooting Guide

## Connection Problems
- Check internet connection
- Verify server status
- Test network connectivity
- Restart devices

## Printer Issues
- Paper jams
- Print quality problems
- Connection errors
- Driver issues

## Payment Processing
- Declined transactions
- Gateway errors
- Settlement issues
- Refund problems

## Performance Issues
- Slow system response
- Memory problems
- Cache clearing
- System optimization

## Data Issues
- Sync problems
- Missing transactions
- Duplicate entries
- Data corruption

## Getting Help
- Check knowledge base
- Contact support
- Submit ticket
- Live chat assistance', 
'common-pos-issues-and-solutions', 
'Quick solutions for common POS system issues and problems', 
'troubleshooting,issues,solutions,help', 
true, 156, 8),

(9, 'System Maintenance and Updates', 
'# System Maintenance

## Regular Maintenance
- Database optimization
- Cache clearing
- Log file management
- Performance monitoring

## Software Updates
- Automatic updates
- Manual updates
- Version compatibility
- Update testing

## Hardware Maintenance
- Device cleaning
- Connection checks
- Performance testing
- Replacement planning

## Preventive Measures
- Regular backups
- Security scans
- Performance monitoring
- Staff training

## Emergency Procedures
- System failures
- Data loss
- Hardware damage
- Recovery protocols', 
'system-maintenance-and-updates', 
'Maintain your POS system for optimal performance and reliability', 
'maintenance,updates,performance,reliability', 
true, 42, 2);

-- Update view counts to simulate real usage
UPDATE knowledgebase SET view_count = FLOOR(RANDOM() * 500) + 50 WHERE id > 0;

-- Update helpful counts to simulate user feedback
UPDATE knowledgebase SET helpful_count = FLOOR(RANDOM() * 100) + 10 WHERE id > 0;
UPDATE knowledgebase SET not_helpful_count = FLOOR(RANDOM() * 20) + 1 WHERE id > 0;
