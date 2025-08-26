-- Store/Location Management Tables
-- This allows businesses to manage multiple stores, restaurants, or locations

-- Create locations table
CREATE TABLE locations (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'store', -- 'store', 'restaurant', 'warehouse', 'office'
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Ghana',
    phone VARCHAR(20),
    email VARCHAR(255),
    manager_id UUID REFERENCES users(id),
    timezone VARCHAR(50) DEFAULT 'Africa/Accra',
    currency VARCHAR(3) DEFAULT 'GHS',
    tax_rate DECIMAL(5,2) DEFAULT 15.00,
    status VARCHAR(20) DEFAULT 'active', -- 'active', 'inactive', 'maintenance'
    settings JSONB DEFAULT '{}',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(tenant_id, name)
);

-- Create location_users table (many-to-many relationship)
CREATE TABLE location_users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    location_id UUID NOT NULL REFERENCES locations(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role VARCHAR(50) NOT NULL DEFAULT 'staff', -- 'manager', 'staff', 'cashier'
    permissions JSONB DEFAULT '{}',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(location_id, user_id)
);

-- Create location_inventory table (inventory per location)
CREATE TABLE location_inventory (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    location_id UUID NOT NULL REFERENCES locations(id) ON DELETE CASCADE,
    product_id UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL DEFAULT 0,
    min_stock INTEGER DEFAULT 0,
    max_stock INTEGER,
    last_counted TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(location_id, product_id)
);

-- Create location_sales table (sales per location)
CREATE TABLE location_sales (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    location_id UUID NOT NULL REFERENCES locations(id) ON DELETE CASCADE,
    sale_id UUID NOT NULL REFERENCES sales(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(location_id, sale_id)
);

-- Add location_id to existing tables
ALTER TABLE sales ADD COLUMN location_id UUID REFERENCES locations(id);
ALTER TABLE inventory ADD COLUMN location_id UUID REFERENCES locations(id);

-- Create indexes for better performance
CREATE INDEX idx_locations_tenant_id ON locations(tenant_id);
CREATE INDEX idx_locations_status ON locations(status);
CREATE INDEX idx_location_users_location_id ON location_users(location_id);
CREATE INDEX idx_location_users_user_id ON location_users(user_id);
CREATE INDEX idx_location_inventory_location_id ON location_inventory(location_id);
CREATE INDEX idx_location_inventory_product_id ON location_inventory(product_id);
CREATE INDEX idx_location_sales_location_id ON location_sales(location_id);
CREATE INDEX idx_location_sales_sale_id ON location_sales(sale_id);

-- Insert default location for existing tenants
INSERT INTO locations (id, tenant_id, name, type, address, city, state, country, status)
SELECT 
    uuid_generate_v4(),
    t.id,
    t.name || ' - Main Store',
    'store',
    'Main Address',
    'Accra',
    'Greater Accra',
    'Ghana',
    'active'
FROM tenants t
WHERE NOT EXISTS (SELECT 1 FROM locations l WHERE l.tenant_id = t.id);

-- Assign all existing users to their default location
INSERT INTO location_users (id, location_id, user_id, role)
SELECT 
    uuid_generate_v4(),
    l.id,
    u.id,
    u.role
FROM users u
JOIN locations l ON l.tenant_id = u.tenant_id
WHERE NOT EXISTS (SELECT 1 FROM location_users lu WHERE lu.user_id = u.id AND lu.location_id = l.id);

-- Update existing sales with location_id
UPDATE sales s
SET location_id = l.id
FROM locations l
WHERE l.tenant_id = s.tenant_id
AND s.location_id IS NULL;

-- Update existing inventory with location_id
UPDATE inventory i
SET location_id = l.id
FROM locations l
WHERE l.tenant_id = i.tenant_id
AND i.location_id IS NULL;
