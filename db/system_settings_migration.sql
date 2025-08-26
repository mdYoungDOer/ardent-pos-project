-- System Settings Migration
-- This migration creates the system_settings table for global configuration

-- Create system_settings table if it doesn't exist
CREATE TABLE IF NOT EXISTS system_settings (
    id SERIAL PRIMARY KEY,
    key VARCHAR(255) UNIQUE NOT NULL,
    value TEXT,
    description TEXT,
    category VARCHAR(100) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default system settings
INSERT INTO system_settings (key, value, description, category) VALUES
-- General Settings
('general_site_name', 'Ardent POS', 'Site name displayed throughout the application', 'general'),
('general_site_description', 'Enterprise Point of Sale System', 'Site description for SEO and branding', 'general'),
('general_timezone', 'UTC', 'Default timezone for the application', 'general'),
('general_maintenance_mode', 'false', 'Enable maintenance mode to restrict access', 'general'),

-- Email Settings (SendGrid)
('email_smtp_host', 'smtp.sendgrid.net', 'SMTP host for email delivery', 'email'),
('email_smtp_port', '587', 'SMTP port for email delivery', 'email'),
('email_smtp_username', '', 'SMTP username (SendGrid API key)', 'email'),
('email_smtp_password', '', 'SMTP password (SendGrid API key)', 'email'),
('email_from_email', 'noreply@ardentpos.com', 'Default from email address', 'email'),
('email_from_name', 'Ardent POS', 'Default from name for emails', 'email'),
('email_verification', 'true', 'Require email verification for new users', 'email'),

-- Payment Settings (Paystack)
('payment_paystack_public_key', '', 'Paystack public key for payment processing', 'payment'),
('payment_paystack_secret_key', '', 'Paystack secret key for payment processing', 'payment'),
('payment_paystack_webhook_secret', '', 'Paystack webhook secret for payment verification', 'payment'),
('payment_currency', 'GHS', 'Default currency for payments', 'payment'),
('payment_currency_symbol', '₵', 'Currency symbol for display', 'payment'),

-- Security Settings
('security_session_timeout', '3600', 'Session timeout in seconds', 'security'),
('security_max_login_attempts', '5', 'Maximum failed login attempts before lockout', 'security'),
('security_require_2fa', 'false', 'Require two-factor authentication', 'security'),
('security_password_min_length', '8', 'Minimum password length', 'security'),
('security_password_require_special', 'true', 'Require special characters in passwords', 'security'),

-- Notification Settings
('notifications_email_notifications', 'true', 'Enable email notifications', 'notifications'),
('notifications_push_notifications', 'true', 'Enable push notifications', 'notifications'),
('notifications_sms_notifications', 'false', 'Enable SMS notifications', 'notifications')

ON CONFLICT (key) DO UPDATE SET
    value = EXCLUDED.value,
    description = EXCLUDED.description,
    category = EXCLUDED.category,
    updated_at = CURRENT_TIMESTAMP;

-- Create index for better performance
CREATE INDEX IF NOT EXISTS idx_system_settings_category ON system_settings(category);
CREATE INDEX IF NOT EXISTS idx_system_settings_key ON system_settings(key);

-- Add comments for documentation
COMMENT ON TABLE system_settings IS 'Global system configuration settings';
COMMENT ON COLUMN system_settings.key IS 'Setting key (format: category_setting_name)';
COMMENT ON COLUMN system_settings.value IS 'Setting value (stored as text)';
COMMENT ON COLUMN system_settings.category IS 'Setting category for organization';
COMMENT ON COLUMN system_settings.description IS 'Human-readable description of the setting';
