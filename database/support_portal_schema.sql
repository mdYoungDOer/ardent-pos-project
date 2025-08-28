-- Support Portal Database Schema
-- Knowledgebase Categories
CREATE TABLE IF NOT EXISTS knowledgebase_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Knowledgebase Articles
CREATE TABLE IF NOT EXISTS knowledgebase (
    id SERIAL PRIMARY KEY,
    category_id INTEGER REFERENCES knowledgebase_categories(id) ON DELETE SET NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    tags TEXT,
    author_id UUID REFERENCES users(id) ON DELETE SET NULL,
    published BOOLEAN DEFAULT false,
    featured BOOLEAN DEFAULT false,
    view_count INTEGER DEFAULT 0,
    helpful_count INTEGER DEFAULT 0,
    not_helpful_count INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Support Tickets
CREATE TABLE IF NOT EXISTS support_tickets (
    id SERIAL PRIMARY KEY,
    ticket_number VARCHAR(50) UNIQUE NOT NULL,
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    tenant_id UUID REFERENCES tenants(id) ON DELETE CASCADE,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority VARCHAR(20) DEFAULT 'medium' CHECK (priority IN ('low', 'medium', 'high', 'urgent')),
    category VARCHAR(100) NOT NULL,
    status VARCHAR(20) DEFAULT 'open' CHECK (status IN ('open', 'in_progress', 'waiting_for_customer', 'resolved', 'closed')),
    assigned_to UUID REFERENCES users(id) ON DELETE SET NULL,
    assigned_at TIMESTAMP,
    resolved_at TIMESTAMP,
    closed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Support Ticket Replies
CREATE TABLE IF NOT EXISTS support_ticket_replies (
    id SERIAL PRIMARY KEY,
    ticket_id INTEGER REFERENCES support_tickets(id) ON DELETE CASCADE,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    message TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Support Chat Sessions
CREATE TABLE IF NOT EXISTS support_chat_sessions (
    id SERIAL PRIMARY KEY,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    tenant_id UUID REFERENCES tenants(id) ON DELETE SET NULL,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'closed', 'escalated')),
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Support Chat Messages
CREATE TABLE IF NOT EXISTS support_chat_messages (
    id SERIAL PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    sender_type VARCHAR(20) NOT NULL CHECK (sender_type IN ('user', 'bot', 'agent')),
    sender_id UUID REFERENCES users(id) ON DELETE SET NULL,
    is_read BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Support Ticket Attachments
CREATE TABLE IF NOT EXISTS support_ticket_attachments (
    id SERIAL PRIMARY KEY,
    ticket_id INTEGER REFERENCES support_tickets(id) ON DELETE CASCADE,
    reply_id INTEGER REFERENCES support_ticket_replies(id) ON DELETE CASCADE,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INTEGER NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Support Portal Settings
CREATE TABLE IF NOT EXISTS support_portal_settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(255) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'string',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default knowledgebase categories
INSERT INTO knowledgebase_categories (name, slug, description, icon, sort_order) VALUES
('Getting Started', 'getting-started', 'Basic setup and configuration guides', 'FiPlay', 1),
('POS Operations', 'pos-operations', 'How to use the POS system effectively', 'FiShoppingCart', 2),
('Inventory Management', 'inventory-management', 'Managing products, stock, and categories', 'FiPackage', 3),
('Sales & Reports', 'sales-reports', 'Understanding sales data and generating reports', 'FiBarChart2', 4),
('User Management', 'user-management', 'Managing users, roles, and permissions', 'FiUsers', 5),
('Billing & Subscriptions', 'billing-subscriptions', 'Payment, billing, and subscription management', 'FiCreditCard', 6),
('Troubleshooting', 'troubleshooting', 'Common issues and their solutions', 'FiTool', 7),
('API & Integrations', 'api-integrations', 'Developer documentation and API guides', 'FiCode', 8);

-- Insert default support portal settings
INSERT INTO support_portal_settings (setting_key, setting_value, setting_type, description) VALUES
('chat_enabled', 'true', 'boolean', 'Enable/disable the chat widget'),
('auto_response_enabled', 'true', 'boolean', 'Enable/disable automatic bot responses'),
('ticket_auto_assignment', 'false', 'boolean', 'Automatically assign tickets to available agents'),
('business_hours_start', '09:00', 'string', 'Business hours start time'),
('business_hours_end', '17:00', 'string', 'Business hours end time'),
('timezone', 'UTC', 'string', 'Support portal timezone'),
('max_attachments_per_ticket', '5', 'integer', 'Maximum number of attachments per ticket'),
('max_attachment_size_mb', '10', 'integer', 'Maximum attachment size in MB');

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_knowledgebase_category ON knowledgebase(category_id);
CREATE INDEX IF NOT EXISTS idx_knowledgebase_published ON knowledgebase(published);
CREATE INDEX IF NOT EXISTS idx_knowledgebase_slug ON knowledgebase(slug);
CREATE INDEX IF NOT EXISTS idx_support_tickets_user ON support_tickets(user_id);
CREATE INDEX IF NOT EXISTS idx_support_tickets_tenant ON support_tickets(tenant_id);
CREATE INDEX IF NOT EXISTS idx_support_tickets_status ON support_tickets(status);
CREATE INDEX IF NOT EXISTS idx_support_tickets_priority ON support_tickets(priority);
CREATE INDEX IF NOT EXISTS idx_support_tickets_assigned ON support_tickets(assigned_to);
CREATE INDEX IF NOT EXISTS idx_support_ticket_replies_ticket ON support_ticket_replies(ticket_id);
CREATE INDEX IF NOT EXISTS idx_support_chat_messages_session ON support_chat_messages(session_id);
CREATE INDEX IF NOT EXISTS idx_support_chat_messages_created ON support_chat_messages(created_at);

-- Create functions for automatic ticket numbering
CREATE OR REPLACE FUNCTION generate_ticket_number()
RETURNS TRIGGER AS $$
BEGIN
    NEW.ticket_number := 'TKT-' || EXTRACT(YEAR FROM CURRENT_DATE) || '-' || LPAD(NEW.id::text, 6, '0');
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_generate_ticket_number
    BEFORE INSERT ON support_tickets
    FOR EACH ROW
    EXECUTE FUNCTION generate_ticket_number();

-- Create function to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create triggers for updated_at
CREATE TRIGGER trigger_update_knowledgebase_updated_at
    BEFORE UPDATE ON knowledgebase
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER trigger_update_support_tickets_updated_at
    BEFORE UPDATE ON support_tickets
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER trigger_update_support_portal_settings_updated_at
    BEFORE UPDATE ON support_portal_settings
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();
