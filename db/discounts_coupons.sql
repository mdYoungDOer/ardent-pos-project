-- Discounts and Coupons System Migration
-- This migration creates the necessary tables for managing discounts and coupons

-- Create discounts table
CREATE TABLE IF NOT EXISTS discounts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type VARCHAR(20) NOT NULL CHECK (type IN ('percentage', 'fixed')),
    value DECIMAL(10,2) NOT NULL CHECK (value >= 0),
    scope VARCHAR(50) NOT NULL CHECK (scope IN ('all_products', 'category', 'product', 'location')),
    scope_ids JSONB, -- Array of IDs for the scope (category_ids, product_ids, location_ids)
    min_amount DECIMAL(10,2) CHECK (min_amount >= 0),
    max_discount DECIMAL(10,2) CHECK (max_discount >= 0),
    start_date TIMESTAMP,
    end_date TIMESTAMP,
    usage_limit INTEGER CHECK (usage_limit > 0),
    used_count INTEGER DEFAULT 0 CHECK (used_count >= 0),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'expired')),
    created_by UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP
);

-- Create coupons table
CREATE TABLE IF NOT EXISTS coupons (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type VARCHAR(20) NOT NULL CHECK (type IN ('percentage', 'fixed')),
    value DECIMAL(10,2) NOT NULL CHECK (value >= 0),
    scope VARCHAR(50) NOT NULL CHECK (scope IN ('all_products', 'category', 'product', 'location')),
    scope_ids JSONB, -- Array of IDs for the scope (category_ids, product_ids, location_ids)
    min_amount DECIMAL(10,2) CHECK (min_amount >= 0),
    max_discount DECIMAL(10,2) CHECK (max_discount >= 0),
    start_date TIMESTAMP,
    end_date TIMESTAMP,
    usage_limit INTEGER CHECK (usage_limit > 0),
    used_count INTEGER DEFAULT 0 CHECK (used_count >= 0),
    per_customer_limit INTEGER CHECK (per_customer_limit > 0),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'expired')),
    created_by UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP
);

-- Create coupon_usage table to track coupon usage per customer
CREATE TABLE IF NOT EXISTS coupon_usage (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    coupon_id UUID NOT NULL REFERENCES coupons(id) ON DELETE CASCADE,
    customer_id UUID REFERENCES customers(id) ON DELETE CASCADE,
    sale_id UUID REFERENCES sales(id) ON DELETE CASCADE,
    used_at TIMESTAMP DEFAULT NOW(),
    discount_amount DECIMAL(10,2) NOT NULL CHECK (discount_amount >= 0)
);

-- Create discount_usage table to track discount usage
CREATE TABLE IF NOT EXISTS discount_usage (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    discount_id UUID NOT NULL REFERENCES discounts(id) ON DELETE CASCADE,
    sale_id UUID REFERENCES sales(id) ON DELETE CASCADE,
    used_at TIMESTAMP DEFAULT NOW(),
    discount_amount DECIMAL(10,2) NOT NULL CHECK (discount_amount >= 0)
);

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_discounts_tenant_id ON discounts(tenant_id);
CREATE INDEX IF NOT EXISTS idx_discounts_status ON discounts(status);
CREATE INDEX IF NOT EXISTS idx_discounts_dates ON discounts(start_date, end_date);
CREATE INDEX IF NOT EXISTS idx_discounts_scope ON discounts(scope);

CREATE INDEX IF NOT EXISTS idx_coupons_tenant_id ON coupons(tenant_id);
CREATE INDEX IF NOT EXISTS idx_coupons_code ON coupons(code);
CREATE INDEX IF NOT EXISTS idx_coupons_status ON coupons(status);
CREATE INDEX IF NOT EXISTS idx_coupons_dates ON coupons(start_date, end_date);
CREATE INDEX IF NOT EXISTS idx_coupons_scope ON coupons(scope);

CREATE INDEX IF NOT EXISTS idx_coupon_usage_coupon_id ON coupon_usage(coupon_id);
CREATE INDEX IF NOT EXISTS idx_coupon_usage_customer_id ON coupon_usage(customer_id);
CREATE INDEX IF NOT EXISTS idx_coupon_usage_sale_id ON coupon_usage(sale_id);

CREATE INDEX IF NOT EXISTS idx_discount_usage_discount_id ON discount_usage(discount_id);
CREATE INDEX IF NOT EXISTS idx_discount_usage_sale_id ON discount_usage(sale_id);

-- Add unique constraint for coupon codes per tenant
ALTER TABLE coupons ADD CONSTRAINT unique_coupon_code_per_tenant UNIQUE (tenant_id, code);

-- Add trigger to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_discounts_updated_at BEFORE UPDATE ON discounts
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_coupons_updated_at BEFORE UPDATE ON coupons
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Insert sample data for testing
INSERT INTO discounts (tenant_id, name, description, type, value, scope, min_amount, max_discount, start_date, end_date, usage_limit, status, created_by) VALUES
('550e8400-e29b-41d4-a716-446655440000', 'Summer Sale', '20% off all summer items', 'percentage', 20, 'all_products', 50, 100, '2025-06-01', '2025-08-31', 1000, 'active', '550e8400-e29b-41d4-a716-446655440001'),
('550e8400-e29b-41d4-a716-446655440000', 'Bulk Purchase', '10% off orders over $200', 'percentage', 10, 'all_products', 200, 50, '2025-01-01', '2025-12-31', NULL, 'active', '550e8400-e29b-41d4-a716-446655440001'),
('550e8400-e29b-41d4-a716-446655440000', 'Location Specific', '15% off at Main Store', 'percentage', 15, 'location', 25, 30, '2025-01-01', '2025-12-31', 500, 'active', '550e8400-e29b-41d4-a716-446655440001')
ON CONFLICT DO NOTHING;

INSERT INTO coupons (tenant_id, code, name, description, type, value, scope, min_amount, max_discount, start_date, end_date, usage_limit, per_customer_limit, status, created_by) VALUES
('550e8400-e29b-41d4-a716-446655440000', 'WELCOME20', 'Welcome Discount', '20% off for new customers', 'percentage', 20, 'all_products', 50, 100, '2025-01-01', '2025-12-31', 1000, 1, 'active', '550e8400-e29b-41d4-a716-446655440001'),
('550e8400-e29b-41d4-a716-446655440000', 'SAVE10', 'Save 10', '10% off any purchase', 'percentage', 10, 'all_products', 25, 50, '2025-01-01', '2025-12-31', 500, 3, 'active', '550e8400-e29b-41d4-a716-446655440001'),
('550e8400-e29b-41d4-a716-446655440000', 'FIXED5', 'Fixed Discount', '5 GHS off any purchase', 'fixed', 5, 'all_products', 20, 5, '2025-01-01', '2025-12-31', 200, 1, 'active', '550e8400-e29b-41d4-a716-446655440001')
ON CONFLICT DO NOTHING;
