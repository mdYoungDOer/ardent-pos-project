-- Add plan_id column to existing subscriptions table
-- This migration adds the missing plan_id column to the subscriptions table

-- Add plan_id column to subscriptions table
ALTER TABLE subscriptions 
ADD COLUMN IF NOT EXISTS plan_id VARCHAR(50);

-- Add foreign key constraint to subscription_plans table
ALTER TABLE subscriptions 
ADD CONSTRAINT IF NOT EXISTS fk_subscriptions_plan_id 
FOREIGN KEY (plan_id) REFERENCES subscription_plans(plan_id);

-- Add index for better performance
CREATE INDEX IF NOT EXISTS idx_subscriptions_plan_id_new ON subscriptions(plan_id);

-- Update existing subscriptions to have a default plan (if any exist)
-- This sets existing subscriptions to 'starter' plan if they don't have one
UPDATE subscriptions 
SET plan_id = 'starter' 
WHERE plan_id IS NULL;

-- Make plan_id NOT NULL after setting default values
ALTER TABLE subscriptions 
ALTER COLUMN plan_id SET NOT NULL;

-- Add comment for documentation
COMMENT ON COLUMN subscriptions.plan_id IS 'Reference to subscription plan from subscription_plans table';
