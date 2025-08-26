-- Sub-Categories System Migration
-- This migration adds sub-category functionality to the existing category system
-- Run this script in pgAdmin to set up the sub-categories system

-- First, add parent_id column to existing categories table to support hierarchical structure
ALTER TABLE categories ADD COLUMN IF NOT EXISTS parent_id UUID REFERENCES categories(id) ON DELETE CASCADE;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS level INTEGER DEFAULT 1 CHECK (level >= 1);
ALTER TABLE categories ADD COLUMN IF NOT EXISTS path TEXT; -- Store the full path like "Electronics/Phones/Smartphones"
ALTER TABLE categories ADD COLUMN IF NOT EXISTS sort_order INTEGER DEFAULT 0;

-- Create sub_categories table for more detailed sub-category management
CREATE TABLE IF NOT EXISTS sub_categories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    category_id UUID NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#e41e5b' CHECK (color ~ '^#[0-9A-Fa-f]{6}$'),
    image_url TEXT,
    sort_order INTEGER DEFAULT 0,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP
);

-- Create category_hierarchy table for flexible hierarchical relationships
CREATE TABLE IF NOT EXISTS category_hierarchy (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    parent_id UUID REFERENCES categories(id) ON DELETE CASCADE,
    child_id UUID NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    level INTEGER NOT NULL DEFAULT 1 CHECK (level >= 1),
    path TEXT, -- Store the full path like "Electronics/Phones/Smartphones"
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(parent_id, child_id)
);

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_categories_parent_id ON categories(parent_id);
CREATE INDEX IF NOT EXISTS idx_categories_level ON categories(level);
CREATE INDEX IF NOT EXISTS idx_categories_path ON categories(path);
CREATE INDEX IF NOT EXISTS idx_categories_sort_order ON categories(sort_order);

CREATE INDEX IF NOT EXISTS idx_sub_categories_tenant_id ON sub_categories(tenant_id);
CREATE INDEX IF NOT EXISTS idx_sub_categories_category_id ON sub_categories(category_id);
CREATE INDEX IF NOT EXISTS idx_sub_categories_status ON sub_categories(status);
CREATE INDEX IF NOT EXISTS idx_sub_categories_sort_order ON sub_categories(sort_order);

CREATE INDEX IF NOT EXISTS idx_category_hierarchy_tenant_id ON category_hierarchy(tenant_id);
CREATE INDEX IF NOT EXISTS idx_category_hierarchy_parent_id ON category_hierarchy(parent_id);
CREATE INDEX IF NOT EXISTS idx_category_hierarchy_child_id ON category_hierarchy(child_id);
CREATE INDEX IF NOT EXISTS idx_category_hierarchy_level ON category_hierarchy(level);
CREATE INDEX IF NOT EXISTS idx_category_hierarchy_path ON category_hierarchy(path);

-- Add trigger to update updated_at timestamp for sub_categories
CREATE OR REPLACE FUNCTION update_sub_categories_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_sub_categories_updated_at
    BEFORE UPDATE ON sub_categories
    FOR EACH ROW EXECUTE FUNCTION update_sub_categories_updated_at();

-- Function to update category paths when hierarchy changes
CREATE OR REPLACE FUNCTION update_category_path()
RETURNS TRIGGER AS $$
DECLARE
    parent_path TEXT;
    new_path TEXT;
BEGIN
    -- If this is a root category (no parent)
    IF NEW.parent_id IS NULL THEN
        NEW.path = NEW.name;
        NEW.level = 1;
    ELSE
        -- Get parent path
        SELECT path INTO parent_path FROM categories WHERE id = NEW.parent_id;
        IF parent_path IS NOT NULL THEN
            NEW.path = parent_path || '/' || NEW.name;
            NEW.level = array_length(string_to_array(NEW.path, '/'), 1);
        ELSE
            NEW.path = NEW.name;
            NEW.level = 1;
        END IF;
    END IF;
    
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Create trigger for categories table
CREATE TRIGGER update_category_path_trigger
    BEFORE INSERT OR UPDATE ON categories
    FOR EACH ROW EXECUTE FUNCTION update_category_path();

-- Function to get all subcategories of a category (recursive)
CREATE OR REPLACE FUNCTION get_subcategories(category_uuid UUID, tenant_uuid UUID)
RETURNS TABLE (
    id UUID,
    name VARCHAR(255),
    description TEXT,
    color VARCHAR(7),
    image_url TEXT,
    level INTEGER,
    path TEXT,
    sort_order INTEGER,
    status VARCHAR(20),
    created_at TIMESTAMP
) AS $$
BEGIN
    RETURN QUERY
    WITH RECURSIVE category_tree AS (
        -- Base case: the starting category
        SELECT 
            c.id,
            c.name,
            c.description,
            c.color,
            c.image_url,
            c.level,
            c.path,
            c.sort_order,
            c.status,
            c.created_at
        FROM categories c
        WHERE c.id = category_uuid AND c.tenant_id = tenant_uuid
        
        UNION ALL
        
        -- Recursive case: child categories
        SELECT 
            child.id,
            child.name,
            child.description,
            child.color,
            child.image_url,
            child.level,
            child.path,
            child.sort_order,
            child.status,
            child.created_at
        FROM categories child
        INNER JOIN category_tree ct ON child.parent_id = ct.id
        WHERE child.tenant_id = tenant_uuid
    )
    SELECT * FROM category_tree ORDER BY path, sort_order;
END;
$$ language 'plpgsql';

-- Function to get category breadcrumb
CREATE OR REPLACE FUNCTION get_category_breadcrumb(category_uuid UUID, tenant_uuid UUID)
RETURNS TABLE (
    id UUID,
    name VARCHAR(255),
    level INTEGER
) AS $$
BEGIN
    RETURN QUERY
    WITH RECURSIVE breadcrumb AS (
        -- Start with the target category
        SELECT 
            c.id,
            c.name,
            c.level,
            c.parent_id
        FROM categories c
        WHERE c.id = category_uuid AND c.tenant_id = tenant_uuid
        
        UNION ALL
        
        -- Get parent categories
        SELECT 
            parent.id,
            parent.name,
            parent.level,
            parent.parent_id
        FROM categories parent
        INNER JOIN breadcrumb b ON parent.id = b.parent_id
        WHERE parent.tenant_id = tenant_uuid
    )
    SELECT 
        bc.id,
        bc.name,
        bc.level
    FROM breadcrumb bc
    ORDER BY bc.level;
END;
$$ language 'plpgsql';

-- Update products table to support sub-categories
ALTER TABLE products ADD COLUMN IF NOT EXISTS sub_category_id UUID REFERENCES sub_categories(id) ON DELETE SET NULL;

-- Create index for products sub_category_id
CREATE INDEX IF NOT EXISTS idx_products_sub_category_id ON products(sub_category_id);

-- Add comments for documentation
COMMENT ON TABLE sub_categories IS 'Detailed sub-categories for better product organization';
COMMENT ON TABLE category_hierarchy IS 'Flexible hierarchical relationships between categories';
COMMENT ON COLUMN categories.parent_id IS 'Parent category ID for hierarchical structure';
COMMENT ON COLUMN categories.level IS 'Hierarchy level (1 = root, 2 = sub-category, etc.)';
COMMENT ON COLUMN categories.path IS 'Full category path (e.g., "Electronics/Phones/Smartphones")';
COMMENT ON COLUMN categories.sort_order IS 'Display order within the same level';
COMMENT ON COLUMN products.sub_category_id IS 'Reference to specific sub-category for detailed classification';

-- Insert some sample hierarchical categories for testing
INSERT INTO categories (id, tenant_id, name, description, color, parent_id, level, path, sort_order) VALUES
-- Electronics (Root Category)
('550e8400-e29b-41d4-a716-446655440001', '00000000-0000-0000-0000-000000000000', 'Electronics', 'Electronic devices and accessories', '#3b82f6', NULL, 1, 'Electronics', 1),
-- Phones (Sub-category of Electronics)
('550e8400-e29b-41d4-a716-446655440002', '00000000-0000-0000-0000-000000000000', 'Phones', 'Mobile phones and accessories', '#10b981', '550e8400-e29b-41d4-a716-446655440001', 2, 'Electronics/Phones', 1),
-- Smartphones (Sub-category of Phones)
('550e8400-e29b-41d4-a716-446655440003', '00000000-0000-0000-0000-000000000000', 'Smartphones', 'Smart mobile phones', '#f59e0b', '550e8400-e29b-41d4-a716-446655440002', 3, 'Electronics/Phones/Smartphones', 1),
-- Feature Phones (Sub-category of Phones)
('550e8400-e29b-41d4-a716-446655440004', '00000000-0000-0000-0000-000000000000', 'Feature Phones', 'Basic mobile phones', '#ef4444', '550e8400-e29b-41d4-a716-446655440002', 3, 'Electronics/Phones/Feature Phones', 2),
-- Computers (Sub-category of Electronics)
('550e8400-e29b-41d4-a716-446655440005', '00000000-0000-0000-0000-000000000000', 'Computers', 'Desktop and laptop computers', '#8b5cf6', '550e8400-e29b-41d4-a716-446655440001', 2, 'Electronics/Computers', 2),
-- Laptops (Sub-category of Computers)
('550e8400-e29b-41d4-a716-446655440006', '00000000-0000-0000-0000-000000000000', 'Laptops', 'Portable computers', '#06b6d4', '550e8400-e29b-41d4-a716-446655440005', 3, 'Electronics/Computers/Laptops', 1),
-- Desktops (Sub-category of Computers)
('550e8400-e29b-41d4-a716-446655440007', '00000000-0000-0000-0000-000000000000', 'Desktops', 'Desktop computers', '#84cc16', '550e8400-e29b-41d4-a716-446655440005', 3, 'Electronics/Computers/Desktops', 2),

-- Clothing (Root Category)
('550e8400-e29b-41d4-a716-446655440008', '00000000-0000-0000-0000-000000000000', 'Clothing', 'Apparel and fashion items', '#10b981', NULL, 1, 'Clothing', 2),
-- Men's Clothing (Sub-category of Clothing)
('550e8400-e29b-41d4-a716-446655440009', '00000000-0000-0000-0000-000000000000', 'Men''s Clothing', 'Clothing for men', '#f97316', '550e8400-e29b-41d4-a716-446655440008', 2, 'Clothing/Men''s Clothing', 1),
-- Women's Clothing (Sub-category of Clothing)
('550e8400-e29b-41d4-a716-446655440010', '00000000-0000-0000-0000-000000000000', 'Women''s Clothing', 'Clothing for women', '#ec4899', '550e8400-e29b-41d4-a716-446655440008', 2, 'Clothing/Women''s Clothing', 2),
-- Kids' Clothing (Sub-category of Clothing)
('550e8400-e29b-41d4-a716-446655440011', '00000000-0000-0000-0000-000000000000', 'Kids'' Clothing', 'Clothing for children', '#6366f1', '550e8400-e29b-41d4-a716-446655440008', 2, 'Clothing/Kids'' Clothing', 3),

-- Food & Beverages (Root Category)
('550e8400-e29b-41d4-a716-446655440012', '00000000-0000-0000-0000-000000000000', 'Food & Beverages', 'Food items and drinks', '#f59e0b', NULL, 1, 'Food & Beverages', 3),
-- Beverages (Sub-category of Food)
('550e8400-e29b-41d4-a716-446655440013', '00000000-0000-0000-0000-000000000000', 'Beverages', 'Drinks and beverages', '#ef4444', '550e8400-e29b-41d4-a716-446655440012', 2, 'Food & Beverages/Beverages', 1),
-- Snacks (Sub-category of Food)
('550e8400-e29b-41d4-a716-446655440014', '00000000-0000-0000-0000-000000000000', 'Snacks', 'Snack foods', '#8b5cf6', '550e8400-e29b-41d4-a716-446655440012', 2, 'Food & Beverages/Snacks', 2)
ON CONFLICT (id) DO NOTHING;

-- Insert sample sub-categories
INSERT INTO sub_categories (id, tenant_id, category_id, name, description, color, sort_order) VALUES
-- Sub-categories for Smartphones
('550e8400-e29b-41d4-a716-446655440015', '00000000-0000-0000-0000-000000000000', '550e8400-e29b-41d4-a716-446655440003', 'Android Phones', 'Android smartphones', '#3b82f6', 1),
('550e8400-e29b-41d4-a716-446655440016', '00000000-0000-0000-0000-000000000000', '550e8400-e29b-41d4-a716-446655440003', 'iPhones', 'Apple iPhones', '#10b981', 2),
('550e8400-e29b-41d4-a716-446655440017', '00000000-0000-0000-0000-000000000000', '550e8400-e29b-41d4-a716-446655440003', 'Windows Phones', 'Windows smartphones', '#f59e0b', 3),

-- Sub-categories for Laptops
('550e8400-e29b-41d4-a716-446655440018', '00000000-0000-0000-0000-000000000000', '550e8400-e29b-41d4-a716-446655440006', 'Gaming Laptops', 'High-performance gaming laptops', '#ef4444', 1),
('550e8400-e29b-41d4-a716-446655440019', '00000000-0000-0000-0000-000000000000', '550e8400-e29b-41d4-a716-446655440006', 'Business Laptops', 'Professional business laptops', '#8b5cf6', 2),
('550e8400-e29b-41d4-a716-446655440020', '00000000-0000-0000-0000-000000000000', '550e8400-e29b-41d4-a716-446655440006', 'Student Laptops', 'Affordable laptops for students', '#06b6d4', 3),

-- Sub-categories for Men's Clothing
('550e8400-e29b-41d4-a716-446655440021', '00000000-0000-0000-0000-000000000000', '550e8400-e29b-41d4-a716-446655440009', 'Shirts', 'Men''s shirts and tops', '#84cc16', 1),
('550e8400-e29b-41d4-a716-446655440022', '00000000-0000-0000-0000-000000000000', '550e8400-e29b-41d4-a716-446655440009', 'Pants', 'Men''s pants and trousers', '#f97316', 2),
('550e8400-e29b-41d4-a716-446655440023', '00000000-0000-0000-0000-000000000000', '550e8400-e29b-41d4-a716-446655440009', 'Shoes', 'Men''s footwear', '#ec4899', 3),

-- Sub-categories for Beverages
('550e8400-e29b-41d4-a716-446655440024', '00000000-0000-0000-0000-000000000000', '550e8400-e29b-41d4-a716-446655440013', 'Soft Drinks', 'Carbonated soft drinks', '#6366f1', 1),
('550e8400-e29b-41d4-a716-446655440025', '00000000-0000-0000-0000-000000000000', '550e8400-e29b-41d4-a716-446655440013', 'Juices', 'Fruit juices and drinks', '#3b82f6', 2),
('550e8400-e29b-41d4-a716-446655440026', '00000000-0000-0000-0000-000000000000', '550e8400-e29b-41d4-a716-446655440013', 'Water', 'Bottled water and beverages', '#10b981', 3)
ON CONFLICT (id) DO NOTHING;

-- Success message
SELECT 'Sub-categories migration completed successfully!' as status;
