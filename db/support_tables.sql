-- Support Portal Database Tables

-- Knowledgebase categories table
CREATE TABLE IF NOT EXISTS knowledgebase_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Knowledgebase articles table
CREATE TABLE IF NOT EXISTS knowledgebase (
    id SERIAL PRIMARY KEY,
    category_id INTEGER REFERENCES knowledgebase_categories(id) ON DELETE SET NULL,
    title VARCHAR(500) NOT NULL,
    content TEXT NOT NULL,
    tags TEXT,
    published BOOLEAN DEFAULT true,
    view_count INTEGER DEFAULT 0,
    helpful_count INTEGER DEFAULT 0,
    not_helpful_count INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Support tickets table
CREATE TABLE IF NOT EXISTS support_tickets (
    id SERIAL PRIMARY KEY,
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    tenant_id UUID REFERENCES tenants(id) ON DELETE CASCADE,
    ticket_number VARCHAR(50) UNIQUE NOT NULL,
    subject VARCHAR(500) NOT NULL,
    message TEXT NOT NULL,
    priority VARCHAR(20) DEFAULT 'medium', -- low, medium, high, urgent
    status VARCHAR(20) DEFAULT 'open', -- open, pending, resolved, closed
    category VARCHAR(100),
    assigned_to UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Support chat sessions table
CREATE TABLE IF NOT EXISTS support_chat_sessions (
    id SERIAL PRIMARY KEY,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    tenant_id UUID REFERENCES tenants(id) ON DELETE SET NULL,
    status VARCHAR(20) DEFAULT 'active', -- active, closed, archived
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Support chat messages table
CREATE TABLE IF NOT EXISTS support_chat_messages (
    id SERIAL PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    sender_type VARCHAR(20) NOT NULL, -- user, bot, agent
    sender_id UUID REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Contact submissions table (for public contact form)
CREATE TABLE IF NOT EXISTS contact_submissions (
    id SERIAL PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    company VARCHAR(255),
    subject VARCHAR(500) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'new', -- new, read, replied, closed
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default knowledgebase categories
INSERT INTO knowledgebase_categories (name, slug, description, sort_order) VALUES
('Getting Started', 'getting-started', 'Basic setup and configuration guides', 1),
('Sales & Transactions', 'sales-transactions', 'How to process sales and manage transactions', 2),
('Inventory Management', 'inventory-management', 'Product and stock management', 3),
('Customer Management', 'customer-management', 'Managing customer data and relationships', 4),
('Reports & Analytics', 'reports-analytics', 'Understanding your business data', 5),
('Hardware & Setup', 'hardware-setup', 'POS hardware configuration and troubleshooting', 6),
('Integrations', 'integrations', 'Third-party integrations and APIs', 7),
('Security & Permissions', 'security-permissions', 'User roles and security settings', 8),
('Troubleshooting', 'troubleshooting', 'Common issues and solutions', 9)
ON CONFLICT (slug) DO NOTHING;

-- Insert sample knowledgebase articles
INSERT INTO knowledgebase (category_id, title, content, tags, published) VALUES
(1, 'How to Set Up Your Ardent POS System', '# Setting Up Your Ardent POS System

## Prerequisites
- A computer or tablet with internet access
- Your business information ready
- Payment method for subscription

## Step-by-Step Setup

### 1. Create Your Account
1. Visit [ardentpos.com](https://ardentpos.com)
2. Click "Get Started" or "Sign Up"
3. Fill in your business details
4. Choose your subscription plan
5. Complete the registration process

### 2. Configure Your Business
1. Log in to your dashboard
2. Go to Settings > Business Information
3. Enter your business details:
   - Business name
   - Address
   - Contact information
   - Tax information
4. Save your settings

### 3. Add Your First Products
1. Navigate to Products > Add Product
2. Enter product details:
   - Name
   - Description
   - Price
   - Category
   - SKU or barcode
3. Upload product images (optional)
4. Save the product

### 4. Set Up Payment Methods
1. Go to Settings > Payment Methods
2. Configure your preferred payment options:
   - Cash
   - Card payments
   - Mobile money
3. Test your payment setup

### 5. Invite Your Team
1. Go to Users > Invite User
2. Enter team member details
3. Assign appropriate roles and permissions
4. Send invitations

## Next Steps
- Read our "Processing Your First Sale" guide
- Set up inventory tracking
- Configure customer management
- Explore reporting features

## Need Help?
If you encounter any issues during setup, contact our support team or check our troubleshooting guides.', 'setup, installation, configuration, getting started', true),

(2, 'How to Process a Sale', '# Processing a Sale with Ardent POS

## Quick Sale Process

### 1. Start a New Sale
1. Click "New Sale" or "POS Terminal"
2. The sale screen will open with an empty cart

### 2. Add Products
**Option A: Search by Name**
1. Type the product name in the search bar
2. Select the product from the results
3. Enter quantity if needed

**Option B: Scan Barcode**
1. Use your barcode scanner
2. Scan the product barcode
3. Product will be added automatically

**Option C: Browse Categories**
1. Click on product categories
2. Find and select your product
3. Add to cart

### 3. Apply Discounts (Optional)
1. Select items in the cart
2. Click "Apply Discount"
3. Choose discount type:
   - Percentage discount
   - Fixed amount discount
   - Buy one get one
4. Enter discount amount
5. Apply to cart

### 4. Add Customer (Optional)
1. Click "Add Customer"
2. Search for existing customer or create new
3. Customer details will be linked to the sale

### 5. Process Payment
1. Review the total amount
2. Click "Process Payment"
3. Choose payment method:
   - Cash
   - Card
   - Mobile money
4. Enter payment details
5. Complete transaction

### 6. Print Receipt
1. Sale will be automatically saved
2. Receipt will be generated
3. Print or email receipt to customer
4. Return to new sale screen

## Tips for Efficient Sales
- Use keyboard shortcuts for faster operation
- Set up product favorites for quick access
- Configure default payment methods
- Enable receipt auto-printing

## Troubleshooting
- If scanner doesn''t work, check USB connection
- If payment fails, verify payment method setup
- If receipt doesn''t print, check printer connection', 'sales, transactions, checkout, payment, receipt', true),

(3, 'Managing Your Inventory', '# Inventory Management Guide

## Adding Products

### Basic Product Information
1. Go to Products > Add Product
2. Fill in required fields:
   - **Name**: Product name
   - **Description**: Product details
   - **Category**: Product category
   - **Price**: Selling price
   - **Cost**: Purchase cost (for profit tracking)
   - **SKU**: Stock keeping unit
   - **Barcode**: Product barcode

### Inventory Settings
- **Track Inventory**: Enable to monitor stock levels
- **Initial Stock**: Starting quantity
- **Low Stock Alert**: Minimum stock level for alerts
- **Reorder Point**: When to reorder

### Product Images
1. Click "Upload Image"
2. Select product photos
3. Set primary image
4. Add multiple images for different angles

## Managing Stock Levels

### View Current Stock
1. Go to Inventory > Stock Levels
2. View all products with current quantities
3. Sort by stock level, category, or name
4. Filter by low stock items

### Adjust Stock Levels
1. Select product from inventory list
2. Click "Adjust Stock"
3. Choose adjustment type:
   - **Add Stock**: Increase quantity
   - **Remove Stock**: Decrease quantity
   - **Set Stock**: Set specific quantity
4. Enter quantity and reason
5. Save adjustment

### Stock Transfers
1. Go to Inventory > Stock Transfers
2. Select source and destination locations
3. Choose products to transfer
4. Enter quantities
5. Process transfer

## Inventory Reports

### Stock Valuation
1. Go to Reports > Inventory Valuation
2. View total inventory value
3. Filter by category or date range
4. Export report for accounting

### Low Stock Report
1. Go to Reports > Low Stock
2. View products below reorder point
3. Generate purchase orders
4. Set up automatic alerts

### Stock Movement
1. Go to Reports > Stock Movement
2. Track all inventory changes
3. Filter by date, product, or type
4. Identify trends and patterns

## Best Practices
- Conduct regular stock counts
- Set up automatic low stock alerts
- Use barcodes for accurate tracking
- Keep product information updated
- Review inventory reports regularly

## Troubleshooting
- If stock levels are incorrect, check for pending sales
- If alerts aren''t working, verify notification settings
- If transfers fail, check location permissions', 'inventory, stock, products, management, tracking', true);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_knowledgebase_category ON knowledgebase(category_id);
CREATE INDEX IF NOT EXISTS idx_knowledgebase_published ON knowledgebase(published);
CREATE INDEX IF NOT EXISTS idx_knowledgebase_tags ON knowledgebase USING gin(to_tsvector('english', tags));
CREATE INDEX IF NOT EXISTS idx_support_tickets_user ON support_tickets(user_id);
CREATE INDEX IF NOT EXISTS idx_support_tickets_tenant ON support_tickets(tenant_id);
CREATE INDEX IF NOT EXISTS idx_support_tickets_status ON support_tickets(status);
CREATE INDEX IF NOT EXISTS idx_support_chat_sessions_user ON support_chat_sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_support_chat_messages_session ON support_chat_messages(session_id);
CREATE INDEX IF NOT EXISTS idx_contact_submissions_status ON contact_submissions(status);
CREATE INDEX IF NOT EXISTS idx_contact_submissions_created ON contact_submissions(created_at);

-- Create triggers for updated_at timestamps
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_knowledgebase_categories_updated_at BEFORE UPDATE ON knowledgebase_categories FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_knowledgebase_updated_at BEFORE UPDATE ON knowledgebase FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_support_tickets_updated_at BEFORE UPDATE ON support_tickets FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_support_chat_sessions_updated_at BEFORE UPDATE ON support_chat_sessions FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_contact_submissions_updated_at BEFORE UPDATE ON contact_submissions FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
