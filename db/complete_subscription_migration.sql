-- Complete Subscription System Migration
-- This script handles both new installations and existing databases

-- Step 1: Create subscription_plans table (if it doesn't exist)
CREATE TABLE IF NOT EXISTS subscription_plans (
    id SERIAL PRIMARY KEY,
    plan_id VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    monthly_price DECIMAL(10,2) NOT NULL,
    yearly_price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'GHS',
    features JSONB NOT NULL,
    limits JSONB NOT NULL,
    is_active BOOLEAN DEFAULT true,
    is_popular BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Step 2: Check if subscriptions table exists and add plan_id column
DO $$
BEGIN
    -- Check if subscriptions table exists
    IF EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'subscriptions') THEN
        -- Add plan_id column if it doesn't exist
        IF NOT EXISTS (SELECT FROM information_schema.columns WHERE table_name = 'subscriptions' AND column_name = 'plan_id') THEN
            ALTER TABLE subscriptions ADD COLUMN plan_id VARCHAR(50);
        END IF;
        
        -- Add foreign key constraint if it doesn't exist
        IF NOT EXISTS (SELECT FROM information_schema.table_constraints WHERE constraint_name = 'fk_subscriptions_plan_id') THEN
            ALTER TABLE subscriptions ADD CONSTRAINT fk_subscriptions_plan_id FOREIGN KEY (plan_id) REFERENCES subscription_plans(plan_id);
        END IF;
        
        -- Update existing subscriptions to have a default plan
        UPDATE subscriptions SET plan_id = 'starter' WHERE plan_id IS NULL;
        
        -- Make plan_id NOT NULL after setting default values
        ALTER TABLE subscriptions ALTER COLUMN plan_id SET NOT NULL;
    ELSE
        -- Create subscriptions table if it doesn't exist
        CREATE TABLE subscriptions (
            id SERIAL PRIMARY KEY,
            tenant_id UUID REFERENCES tenants(id) ON DELETE CASCADE,
            plan_id VARCHAR(50) REFERENCES subscription_plans(plan_id),
            status VARCHAR(20) DEFAULT 'active', -- active, cancelled, expired, pending
            billing_cycle VARCHAR(10) NOT NULL, -- monthly, yearly
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'GHS',
            paystack_reference VARCHAR(100),
            next_billing_date DATE,
            trial_ends_at TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    END IF;
END $$;

-- Step 3: Insert subscription plans (only if they don't exist)
INSERT INTO subscription_plans (plan_id, name, description, monthly_price, yearly_price, features, limits, is_popular) 
SELECT 'starter', 'Starter', 'Perfect for small businesses and startups getting started with POS', 120.00, 1200.00,
    '[
        "Basic POS functionality",
        "Up to 2 locations",
        "Basic inventory management",
        "Sales reports",
        "Customer management",
        "Basic analytics",
        "Email support",
        "Mobile app access",
        "Receipt printing",
        "Basic tax calculations"
    ]',
    '{
        "locations": 2,
        "users": 3,
        "products": 500,
        "customers": 1000,
        "transactions_per_month": 1000,
        "storage_gb": 5,
        "api_calls_per_month": 10000,
        "backup_retention_days": 30
    }',
    false
WHERE NOT EXISTS (SELECT 1 FROM subscription_plans WHERE plan_id = 'starter');

INSERT INTO subscription_plans (plan_id, name, description, monthly_price, yearly_price, features, limits, is_popular) 
SELECT 'professional', 'Professional', 'Ideal for growing businesses with multiple locations', 240.00, 2400.00,
    '[
        "Everything in Starter",
        "Up to 5 locations",
        "Advanced inventory management",
        "Advanced analytics & reporting",
        "Multi-user access",
        "Customer loyalty program",
        "Discount & coupon management",
        "Advanced tax management",
        "Integration with accounting software",
        "Priority email support",
        "Advanced security features",
        "Data export capabilities"
    ]',
    '{
        "locations": 5,
        "users": 10,
        "products": 2000,
        "customers": 5000,
        "transactions_per_month": 5000,
        "storage_gb": 20,
        "api_calls_per_month": 50000,
        "backup_retention_days": 90
    }',
    true
WHERE NOT EXISTS (SELECT 1 FROM subscription_plans WHERE plan_id = 'professional');

INSERT INTO subscription_plans (plan_id, name, description, monthly_price, yearly_price, features, limits, is_popular) 
SELECT 'business', 'Business', 'Comprehensive solution for established businesses', 360.00, 3600.00,
    '[
        "Everything in Professional",
        "Up to 10 locations",
        "Advanced customer analytics",
        "Multi-currency support",
        "Advanced reporting suite",
        "Inventory forecasting",
        "Supplier management",
        "Advanced user permissions",
        "API access",
        "Custom integrations",
        "Phone & email support",
        "Advanced security & compliance",
        "Data migration assistance"
    ]',
    '{
        "locations": 10,
        "users": 25,
        "products": 10000,
        "customers": 25000,
        "transactions_per_month": 25000,
        "storage_gb": 50,
        "api_calls_per_month": 100000,
        "backup_retention_days": 180
    }',
    false
WHERE NOT EXISTS (SELECT 1 FROM subscription_plans WHERE plan_id = 'business');

INSERT INTO subscription_plans (plan_id, name, description, monthly_price, yearly_price, features, limits, is_popular) 
SELECT 'enterprise', 'Enterprise', 'Full-featured solution for large enterprises and chains', 480.00, 4800.00,
    '[
        "Everything in Business",
        "Unlimited locations",
        "Advanced business intelligence",
        "Custom reporting",
        "White-label solutions",
        "Advanced API access",
        "Custom integrations",
        "Dedicated account manager",
        "24/7 priority support",
        "Advanced security & compliance",
        "Custom training sessions",
        "SLA guarantees",
        "Advanced backup & recovery"
    ]',
    '{
        "locations": -1,
        "users": 100,
        "products": -1,
        "customers": -1,
        "transactions_per_month": -1,
        "storage_gb": 200,
        "api_calls_per_month": 500000,
        "backup_retention_days": 365
    }',
    false
WHERE NOT EXISTS (SELECT 1 FROM subscription_plans WHERE plan_id = 'enterprise');

INSERT INTO subscription_plans (plan_id, name, description, monthly_price, yearly_price, features, limits, is_popular) 
SELECT 'premium', 'Premium', 'Ultimate solution with custom features and dedicated support', 600.00, 6000.00,
    '[
        "Everything in Enterprise",
        "Custom feature development",
        "Dedicated support team",
        "Custom integrations",
        "Advanced analytics & AI",
        "Multi-brand management",
        "Advanced security features",
        "Custom training programs",
        "Performance optimization",
        "Custom SLA agreements",
        "On-site support available",
        "Custom backup solutions"
    ]',
    '{
        "locations": -1,
        "users": -1,
        "products": -1,
        "customers": -1,
        "transactions_per_month": -1,
        "storage_gb": 500,
        "api_calls_per_month": 1000000,
        "backup_retention_days": 730
    }',
    false
WHERE NOT EXISTS (SELECT 1 FROM subscription_plans WHERE plan_id = 'premium');

-- Step 4: Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_subscription_plans_plan_id ON subscription_plans(plan_id);
CREATE INDEX IF NOT EXISTS idx_subscription_plans_active ON subscription_plans(is_active);
CREATE INDEX IF NOT EXISTS idx_subscriptions_tenant_id ON subscriptions(tenant_id);
CREATE INDEX IF NOT EXISTS idx_subscriptions_plan_id ON subscriptions(plan_id);
CREATE INDEX IF NOT EXISTS idx_subscriptions_status ON subscriptions(status);
CREATE INDEX IF NOT EXISTS idx_subscriptions_next_billing_date ON subscriptions(next_billing_date);

-- Step 5: Add comments for documentation
COMMENT ON TABLE subscription_plans IS 'Available subscription plans for tenants';
COMMENT ON TABLE subscriptions IS 'Active subscriptions for tenants';
COMMENT ON COLUMN subscription_plans.features IS 'JSON array of features included in this plan';
COMMENT ON COLUMN subscription_plans.limits IS 'JSON object defining usage limits for this plan';
COMMENT ON COLUMN subscriptions.status IS 'Subscription status: active, cancelled, expired, pending';
COMMENT ON COLUMN subscriptions.billing_cycle IS 'Billing frequency: monthly or yearly';
COMMENT ON COLUMN subscriptions.paystack_reference IS 'Paystack payment reference for tracking';
COMMENT ON COLUMN subscriptions.next_billing_date IS 'Next billing date for recurring payments';
COMMENT ON COLUMN subscriptions.trial_ends_at IS 'Trial period end date if applicable';
COMMENT ON COLUMN subscriptions.plan_id IS 'Reference to subscription plan from subscription_plans table';

-- Step 6: Verify the migration
SELECT 'Migration completed successfully!' as status;
SELECT COUNT(*) as subscription_plans_count FROM subscription_plans;
SELECT COUNT(*) as subscriptions_count FROM subscriptions;
