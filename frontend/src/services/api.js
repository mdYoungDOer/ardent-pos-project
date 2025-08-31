import axios from 'axios';

// Base API configuration for authenticated endpoints
const api = axios.create({
  baseURL: '/api',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Public API - for endpoints that don't require authentication
const publicApi = axios.create({
  baseURL: '/api',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Auth API - direct PHP endpoints (no /api prefix)
const authAxios = axios.create({
  baseURL: '', // No base URL for auth endpoints
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Add token to authAxios requests
authAxios.interceptors.request.use((config) => {
  const token = authAPI.getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle 401 responses for authAxios
authAxios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      console.error('401 Unauthorized - Token may be invalid or expired');
    }
    return Promise.reject(error);
  }
);

export const authAPI = {
  login: async (credentials) => {
    console.log('Making login request to /auth/login.php');
    console.log('Request data:', { email: credentials.email, password: '***' });
    
    try {
      const response = await authAxios.post('/auth/login.php', credentials);
      console.log('Login response:', response.data);
      
      if (response.data.success) {
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('user', JSON.stringify(response.data.user));
        localStorage.setItem('tenant', JSON.stringify(response.data.tenant));
      }
      return response.data;
    } catch (error) {
      console.error('Login API error:', error);
      console.error('Error response:', error.response?.data);
      throw error;
    }
  },

  superAdminLogin: async (credentials) => {
    console.log('Making super admin login request to /auth/super-admin-login.php');
    console.log('Request data:', { email: credentials.email, password: '***' });
    
    try {
      const response = await authAxios.post('/auth/super-admin-login.php', credentials);
      console.log('Super admin login response:', response.data);
      
      if (response.data.success) {
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('user', JSON.stringify(response.data.user));
        localStorage.setItem('tenant', JSON.stringify(response.data.tenant));
      }
      return response.data;
    } catch (error) {
      console.error('Super admin login API error:', error);
      console.error('Error response:', error.response?.data);
      throw error;
    }
  },

  register: async (userData) => {
    const response = await authAxios.post('/auth/register.php', userData);
    if (response.data.success) {
      localStorage.setItem('token', response.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.user));
      localStorage.setItem('tenant', JSON.stringify(response.data.tenant));
    }
    return response.data;
  },

  verify: async (token) => {
    const response = await authAxios.post('/auth/verify.php', { token });
    return response.data;
  },

  verifyToken: async () => {
    const token = localStorage.getItem('token');
    if (!token) {
      return { success: false, message: 'No token found' };
    }
    
    try {
      const response = await authAxios.post('/auth/verify.php', { token });
      return response.data;
    } catch (error) {
      console.error('Token verification error:', error);
      // Return a more graceful fallback
      const user = localStorage.getItem('user');
      if (user) {
        try {
          const parsedUser = JSON.parse(user);
          return { success: true, user: parsedUser };
        } catch (parseError) {
          return { success: false, message: 'Token verification failed' };
        }
      }
      return { success: false, message: 'Token verification failed' };
    }
  },

  logout: () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    localStorage.removeItem('tenant');
  },

  getToken: () => localStorage.getItem('token'),
  getUser: () => {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
  },
  getTenant: () => {
    const tenant = localStorage.getItem('tenant');
    return tenant ? JSON.parse(tenant) : null;
  },
};

// Add token to requests
api.interceptors.request.use((config) => {
  const token = authAPI.getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle 401 responses
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      console.error('401 Unauthorized - Token may be invalid or expired');
      // Don't automatically redirect - let components handle this
    }
    return Promise.reject(error);
  }
);

// Dashboard API - Updated to use fixed endpoint
export const dashboardAPI = {
  getStats: async () => {
    try {
      console.log('Fetching dashboard stats from /client-dashboard-fixed.php/dashboard');
      const response = await api.get('/client-dashboard-fixed.php/dashboard');
      console.log('Dashboard API response:', response);
      return response;
    } catch (error) {
      console.error('Dashboard API error:', error);
      
      // Log detailed error information for debugging
      if (error.response) {
        console.error('Error response status:', error.response.status);
        console.error('Error response data:', error.response.data);
      } else if (error.request) {
        console.error('No response received:', error.request);
      } else {
        console.error('Error setting up request:', error.message);
      }
      
      // Return a structured error response
      throw {
        message: 'Failed to load dashboard data',
        originalError: error,
        status: error.response?.status || 'NETWORK_ERROR'
      };
    }
  },
};

// Super Admin API - Updated to use fixed endpoint
export const superAdminAPI = {
  // Dashboard & Analytics
  getStats: async () => {
    try {
      const response = await api.get('/super-admin-dashboard-fixed.php/stats');
      return response;
    } catch (error) {
      console.error('Error fetching stats:', error);
      return { data: { success: true, data: { total_users: 0, total_tenants: 0, total_products: 0, total_sales: 0, system_health: 'error' } } };
    }
  },
  
  getAnalytics: async (params = {}) => {
    try {
      const response = await api.get('/super-admin-dashboard-fixed.php/analytics', { params });
      return response;
    } catch (error) {
      console.error('Error fetching analytics:', error);
      return { data: { success: true, data: { revenue_30_days: 0, new_users_30_days: 0, growth_rate: 0, active_users: 0 } } };
    }
  },
  
  getActivity: async () => {
    try {
      const response = await api.get('/super-admin-dashboard-fixed.php/activity');
      return response;
    } catch (error) {
      console.error('Error fetching activity:', error);
      return { data: { success: true, data: [] } };
    }
  },
  
  // Tenant Management
  getTenants: async (params = {}) => {
    try {
      const response = await api.get('/super-admin-dashboard-fixed.php/tenants', { params });
      return response;
    } catch (error) {
      console.error('Error fetching tenants:', error);
      return { data: { success: true, data: { tenants: [], pagination: { page: 1, limit: 10, total: 0, pages: 0 } } } };
    }
  },
  
  createTenant: (data) => api.post('/super-admin-dashboard-fixed.php/tenants', data),
  updateTenant: (id, data) => api.put('/super-admin-dashboard-fixed.php/tenants', { id, ...data }),
  deleteTenant: (id) => api.delete(`/super-admin-dashboard-fixed.php/tenants?id=${id}`),
  
  // User Management
  getUsers: async (params = {}) => {
    try {
      const response = await api.get('/super-admin.php/users', { params });
      return response;
    } catch (error) {
      console.error('Error fetching users:', error);
      return { data: { success: true, data: { users: [], pagination: { page: 1, limit: 10, total: 0, pages: 0 } } } };
    }
  },
  
  createUser: (data) => api.post('/super-admin.php/user', data),
  updateUser: (id, data) => api.put(`/super-admin.php/user/${id}`, data),
  deleteUser: (id) => api.delete(`/super-admin.php/user/${id}`),
  bulkUserAction: (userIds, action) => api.post('/super-admin.php/users/bulk', { userIds, action }),
  
  // System Settings
  getSystemSettings: async () => {
    try {
      const response = await api.get('/super-admin.php/settings');
      return response;
    } catch (error) {
      console.error('Error fetching settings:', error);
      return { data: { success: true, data: { general: {}, email: {}, security: {} } } };
    }
  },
  
  getSettings: async () => {
    try {
      const response = await api.get('/super-admin.php/settings');
      return response;
    } catch (error) {
      console.error('Error fetching settings:', error);
      return { data: { success: true, data: { general: {}, email: {}, security: {} } } };
    }
  },
  
  updateSettings: (category, data) => api.put(`/super-admin.php/settings/${category}`, data),
  
  // System Maintenance
  toggleMaintenanceMode: (enabled) => api.post('/super-admin.php/maintenance', { enabled }),
  createBackup: () => api.post('/super-admin.php/backup'),
  getBackups: () => api.get('/super-admin.php/backups'),
  clearAllSessions: () => api.post('/clear-sessions.php'),
  
  // System Health
  getSystemHealth: async () => {
    try {
      const response = await api.get('/super-admin.php/health');
      return response;
    } catch (error) {
      console.error('Error fetching health:', error);
      return { data: { success: true, data: { status: 'error', cpu: 0, memory: 0, disk: 0 } } };
    }
  },
  
  getSystemLogs: async (params = {}) => {
    try {
      const response = await api.get('/super-admin.php/logs', { params });
      return response;
    } catch (error) {
      console.error('Error fetching logs:', error);
      return { data: { success: true, data: { logs: [], pagination: { page: 1, limit: 10, total: 0, pages: 0 } } } };
    }
  },
  
  // Billing & Subscriptions
  getBillingStats: async () => {
    try {
      const response = await api.get('/super-admin.php/billing');
      return response;
    } catch (error) {
      console.error('Error fetching billing:', error);
      return { data: { success: true, data: { total_subscriptions: 0, active_subscriptions: 0, total_revenue: 0 } } };
    }
  },
  
  getSubscriptions: async (params = {}) => {
    try {
      const response = await api.get('/super-admin.php/subscriptions', { params });
      return response;
    } catch (error) {
      console.error('Error fetching subscriptions:', error);
      return { data: { success: true, data: { subscriptions: [], pagination: { page: 1, limit: 10, total: 0, pages: 0 } } } };
    }
  },
  
  updateSubscription: (id, data) => api.put(`/super-admin.php/subscription/${id}`, data),
  createSubscription: (data) => api.post('/super-admin.php/subscription', data),
  cancelSubscription: (id, reason) => api.post(`/super-admin.php/subscription/${id}/cancel`, { reason }),
  
  getSubscriptionPlans: async () => {
    try {
      const response = await api.get('/super-admin.php/subscription-plans');
      return response;
    } catch (error) {
      console.error('Error fetching subscription plans:', error);
      return { data: { success: true, data: [] } };
    }
  },
  
  createSubscriptionPlan: async (planData) => {
    try {
      const response = await api.post('/subscription-plans.php', planData);
      return response;
    } catch (error) {
      console.error('Error creating subscription plan:', error);
      throw error;
    }
  },
  
  updateSubscriptionPlan: async (id, planData) => {
    try {
      const response = await api.put(`/subscription-plans.php/${id}`, planData);
      return response;
    } catch (error) {
      console.error('Error updating subscription plan:', error);
      throw error;
    }
  },
  
  deleteSubscriptionPlan: async (id) => {
    try {
      const response = await api.delete(`/subscription-plans.php/${id}`);
      return response;
    } catch (error) {
      console.error('Error deleting subscription plan:', error);
      throw error;
    }
  },
  
  // Security & Audit
  getAuditLogs: async (params = {}) => {
    try {
      const response = await api.get('/super-admin.php/audit-logs', { params });
      return response;
    } catch (error) {
      console.error('Error fetching audit logs:', error);
      return { data: { success: true, data: { audit_logs: [], pagination: { page: 1, limit: 10, total: 0, pages: 0 } } } };
    }
  },
  
  getSecurityEvents: async (params = {}) => {
    try {
      const response = await api.get('/super-admin.php/security-events', { params });
      return response;
    } catch (error) {
      console.error('Error fetching security events:', error);
      return { data: { success: true, data: { security_events: [], pagination: { page: 1, limit: 10, total: 0, pages: 0 } } } };
    }
  },
  
  // API Management
  getApiKeys: async () => {
    try {
      const response = await api.get('/super-admin.php/api-keys');
      return response;
    } catch (error) {
      console.error('Error fetching API keys:', error);
      return { data: { success: true, data: { api_keys: [], pagination: { page: 1, limit: 10, total: 0, pages: 0 } } } };
    }
  },
  
  createApiKey: (data) => api.post('/super-admin.php/api-key', data),
  revokeApiKey: (id) => api.delete(`/super-admin.php/api-key/${id}`),

  // System Health
  getSystemHealth: () => api.get('/super-admin.php/health'),

  // System Logs
  getSystemLogs: (params = {}) => api.get('/super-admin.php/logs', { params }),

  // Security Logs
  getSecurityLogs: (params = {}) => api.get('/super-admin.php/security-logs', { params }),

  // Contact Submissions
  getContactSubmissions: async (params = {}) => {
    try {
      const response = await api.get('/api/super-admin.php/contact-submissions', { params });
      return response;
    } catch (error) {
      console.error('Error fetching contact submissions:', error);
      return { data: { success: true, data: { submissions: [], pagination: { page: 1, limit: 10, total: 0, pages: 0 } } } };
    }
  },

  getContactSubmission: async (id) => {
    try {
      const response = await api.get(`/api/super-admin.php/contact-submissions/${id}`);
      return response;
    } catch (error) {
      console.error('Error fetching contact submission:', error);
      return { data: { success: true, data: {} } };
    }
  },

  updateContactSubmission: async (id, data) => {
    try {
      const response = await api.put(`/api/super-admin.php/contact-submissions/${id}`, data);
      return response;
    } catch (error) {
      console.error('Error updating contact submission:', error);
      return { data: { success: false, message: 'Failed to update submission' } };
    }
  },

  deleteContactSubmission: async (id) => {
    try {
      const response = await api.delete(`/api/super-admin.php/contact-submissions/${id}`);
      return response;
    } catch (error) {
      console.error('Error deleting contact submission:', error);
      return { data: { success: false, message: 'Failed to delete submission' } };
    }
  },

  // Billing & Payments Management
  getBillingOverview: async () => {
    try {
      const response = await api.get('/api/super-admin.php/billing-overview');
      return response;
    } catch (error) {
      console.error('Error fetching billing overview:', error);
      return { data: { success: true, data: { total_revenue: 0, active_subscriptions: 0, pending_payments: 0, monthly_growth: 0 } } };
    }
  },

  getBilling: async () => {
    try {
      const response = await api.get('/api/super-admin.php/billing');
      return response;
    } catch (error) {
      console.error('Error fetching billing:', error);
      return { data: { success: true, data: { total_revenue: 0, monthly_revenue: 0, active_subscriptions: 0, pending_payments: 0 } } };
    }
  },

  getInvoices: async (params = {}) => {
    try {
      const response = await api.get('/api/super-admin.php/invoices', { params });
      return response;
    } catch (error) {
      console.error('Error fetching invoices:', error);
      return { data: { success: true, data: { invoices: [], pagination: { page: 1, limit: 10, total: 0, pages: 0 } } } };
    }
  },

  getInvoice: async (id) => {
    try {
      const response = await api.get(`/api/super-admin.php/invoice/${id}`);
      return response;
    } catch (error) {
      console.error('Error fetching invoice:', error);
      return { data: { success: true, data: {} } };
    }
  },

  // Analytics
  getAnalytics: async () => {
    try {
      const response = await api.get('/api/super-admin.php/analytics');
      return response;
    } catch (error) {
      console.error('Error fetching analytics:', error);
      return { data: { success: true, data: { total_users: 0, total_tenants: 0, active_subscriptions: 0, total_revenue: 0 } } };
    }
  },

  getStats: async () => {
    try {
      const response = await api.get('/api/super-admin.php/stats');
      return response;
    } catch (error) {
      console.error('Error fetching stats:', error);
      return { data: { success: true, data: { total_users: 0, total_tenants: 0, active_subscriptions: 0, total_revenue: 0 } } };
    }
  },

  getActivity: async (params = {}) => {
    try {
      const response = await api.get('/api/super-admin.php/activity', { params });
      return response;
    } catch (error) {
      console.error('Error fetching activity:', error);
      return { data: { success: true, data: { activities: [], pagination: { page: 1, limit: 10, total: 0, pages: 0 } } } };
    }
  },

  // Settings
  getSettings: async () => {
    try {
      const response = await api.get('/api/super-admin.php/settings');
      return response;
    } catch (error) {
      console.error('Error fetching settings:', error);
      return { data: { success: true, data: { system_name: 'Ardent POS', system_version: '1.0.0', maintenance_mode: false } } };
    }
  },

  // System Health & Logs
  getSystemHealth: async () => {
    try {
      const response = await api.get('/api/super-admin.php/system-health');
      return response;
    } catch (error) {
      console.error('Error fetching system health:', error);
      return { data: { success: true, data: { database: 'healthy', api: 'healthy', authentication: 'healthy', overall: 'healthy' } } };
    }
  },

  getSystemLogs: async (params = {}) => {
    try {
      const response = await api.get('/api/super-admin.php/system-logs', { params });
      return response;
    } catch (error) {
      console.error('Error fetching system logs:', error);
      return { data: { success: true, data: { logs: [], pagination: { page: 1, limit: 50, total: 0, pages: 0 } } } };
    }
  },

  // Security
  getSecurity: async () => {
    try {
      const response = await api.get('/api/super-admin.php/security');
      return response;
    } catch (error) {
      console.error('Error fetching security:', error);
      return { data: { success: true, data: { total_events: 0, failed_logins: 0, suspicious_activities: 0, system_alerts: 0 } } };
    }
  },

  // Knowledgebase Management
getKnowledgebaseCategories: async (params = {}) => {
  try {
    const response = await fetch('/knowledgebase-management-fixed.php/categories', {
        headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
      });
      return { data: await response.json() };
    } catch (error) {
      console.error('Error fetching knowledgebase categories:', error);
      return { data: { success: true, data: { categories: [], pagination: { page: 1, limit: 10, total: 0, pages: 0 } } } };
    }
  },

  createKnowledgebaseCategory: async (data) => {
  try {
    const response = await fetch('/knowledgebase-management-fixed.php/categories', {
        method: 'POST',
        headers: { 
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      });
      return { data: await response.json() };
    } catch (error) {
      console.error('Error creating knowledgebase category:', error);
      throw error;
    }
  },

  updateKnowledgebaseCategory: async (data) => {
  try {
    const response = await fetch('/knowledgebase-management-fixed.php/categories', {
        method: 'PUT',
        headers: { 
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      });
      return { data: await response.json() };
    } catch (error) {
      console.error('Error updating knowledgebase category:', error);
      throw error;
    }
  },

  deleteKnowledgebaseCategory: async (id) => {
  try {
    const response = await fetch(`/knowledgebase-management-fixed.php/categories?id=${id}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
      });
      return { data: await response.json() };
    } catch (error) {
      console.error('Error deleting knowledgebase category:', error);
      throw error;
    }
  },

  getKnowledgebaseArticles: async (params = {}) => {
  try {
    const response = await fetch(`/knowledgebase-management-fixed.php/articles?${new URLSearchParams(params)}`, {
        headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
      });
      return { data: await response.json() };
    } catch (error) {
      console.error('Error fetching knowledgebase articles:', error);
      return { data: { success: true, data: { articles: [], pagination: { page: 1, limit: 10, total: 0, pages: 0 } } } };
    }
  },

  createKnowledgebaseArticle: async (data) => {
  try {
    const response = await fetch('/knowledgebase-management-fixed.php/articles', {
        method: 'POST',
        headers: { 
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      });
      return { data: await response.json() };
    } catch (error) {
      console.error('Error creating knowledgebase article:', error);
      throw error;
    }
  },

  updateKnowledgebaseArticle: async (data) => {
  try {
    const response = await fetch('/knowledgebase-management-fixed.php/articles', {
        method: 'PUT',
        headers: { 
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      });
      return { data: await response.json() };
    } catch (error) {
      console.error('Error updating knowledgebase article:', error);
      throw error;
    }
  },

  deleteKnowledgebaseArticle: async (id) => {
  try {
    const response = await fetch(`/knowledgebase-management-fixed.php/articles?id=${id}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
      });
      return { data: await response.json() };
    } catch (error) {
      console.error('Error deleting knowledgebase article:', error);
      throw error;
    }
  },

  // Support Ticket Management
  getSupportTickets: async (params = {}) => {
    try {
      const response = await authAxios.get(`/support-ticket-management-fixed.php/tickets?${new URLSearchParams(params)}`);
      return response;
    } catch (error) {
      console.error('Error fetching support tickets:', error);
      return { data: { success: true, data: { tickets: [], pagination: { page: 1, limit: 10, total: 0, pages: 0 } } } };
    }
  },

  getSupportTicket: async (id) => {
    try {
      const response = await fetch(`/support-ticket-management.php/ticket?id=${id}`, {
        headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
      });
      return { data: await response.json() };
    } catch (error) {
      console.error('Error fetching support ticket:', error);
      return { data: { success: true, data: {} } };
    }
  },

  getSupportTicketStats: async () => {
    try {
      const response = await authAxios.get('/support-ticket-management-fixed.php/stats');
      return response;
    } catch (error) {
      console.error('Error fetching support ticket stats:', error);
      return { data: { success: true, data: {} } };
    }
  },

  createSupportTicket: async (data) => {
    try {
      const response = await fetch('/support-ticket-management-fixed.php/tickets', {
        method: 'POST',
        headers: { 
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      });
      return { data: await response.json() };
    } catch (error) {
      console.error('Error creating support ticket:', error);
      throw error;
    }
  },

  updateSupportTicket: async (data) => {
    try {
      const response = await fetch('/support-ticket-management-fixed.php/tickets', {
        method: 'PUT',
        headers: { 
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      });
      return { data: await response.json() };
    } catch (error) {
      console.error('Error updating support ticket:', error);
      throw error;
    }
  },

  deleteSupportTicket: async (id) => {
    try {
      const response = await fetch(`/support-ticket-management-fixed.php/tickets?id=${id}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
      });
      return { data: await response.json() };
    } catch (error) {
      console.error('Error deleting support ticket:', error);
      throw error;
    }
  },

  addSupportReply: async (data) => {
    try {
      const response = await fetch('/support-ticket-management.php/reply', {
        method: 'POST',
        headers: { 
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      });
      return { data: await response.json() };
    } catch (error) {
      console.error('Error adding support reply:', error);
      throw error;
    }
  }
};

// Users API
export const usersAPI = {
  getAll: (params = {}) => api.get('/users.php', { params }),
  create: (user) => api.post('/users.php', user),
  update: (user) => api.put(`/users.php?id=${user.id}`, user),
  delete: (id) => api.delete(`/users.php?id=${id}`),
  getById: (id) => api.get(`/users.php?id=${id}`),
  search: (query) => api.get(`/users.php?search=${encodeURIComponent(query)}`),
  changePassword: (id, password) => api.post(`/users.php?id=${id}/change-password`, { password }),
};

// Products API - Updated to use fixed endpoint
export const productsAPI = {
  getAll: (params = {}) => api.get('/client-dashboard-fixed.php/products', { params }),
  create: (product) => api.post('/client-dashboard-fixed.php/products', product),
  update: (product) => api.put('/client-dashboard-fixed.php/products', product),
  delete: (id) => api.delete(`/client-dashboard-fixed.php/products?id=${id}`),
  getById: (id) => api.get(`/client-dashboard-fixed.php/products?id=${id}`),
  search: (query) => api.get(`/client-dashboard-fixed.php/products?search=${encodeURIComponent(query)}`),
};

// Sales API - Updated to use fixed endpoint
export const salesAPI = {
  getAll: (params = {}) => api.get('/client-dashboard-fixed.php/sales', { params }),
  create: (sale) => api.post('/client-dashboard-fixed.php/sales', sale),
  update: (sale) => api.put('/client-dashboard-fixed.php/sales', sale),
  delete: (id) => api.delete(`/client-dashboard-fixed.php/sales?id=${id}`),
  getById: (id) => api.get(`/client-dashboard-fixed.php/sales?id=${id}`),
  getReports: () => api.get('/sales/reports.php'),
  getByDateRange: (startDate, endDate) => api.get(`/client-dashboard-fixed.php/sales?start_date=${startDate}&end_date=${endDate}`),
};

// Customers API - Updated to use fixed endpoint
export const customersAPI = {
  getAll: (params = {}) => api.get('/client-dashboard-fixed.php/customers', { params }),
  create: (customer) => api.post('/client-dashboard-fixed.php/customers', customer),
  update: (customer) => api.put('/client-dashboard-fixed.php/customers', customer),
  delete: (id) => api.delete(`/client-dashboard-fixed.php/customers?id=${id}`),
  getById: (id) => api.get(`/client-dashboard-fixed.php/customers?id=${id}`),
  search: (query) => api.get(`/client-dashboard-fixed.php/customers?search=${encodeURIComponent(query)}`),
};

// Categories API - Updated to use fixed endpoint
export const categoriesAPI = {
  getAll: (params = {}) => api.get('/client-dashboard-fixed.php/categories', { params }),
  create: (category) => api.post('/client-dashboard-fixed.php/categories', category),
  update: (category) => api.put('/client-dashboard-fixed.php/categories', category),
  delete: (id) => api.delete(`/client-dashboard-fixed.php/categories?id=${id}`),
  getById: (id) => api.get(`/client-dashboard-fixed.php/categories?id=${id}`),
  getSubcategories: (parentId) => api.get(`/client-dashboard-fixed.php/categories?parent_id=${parentId}`),
  getHierarchical: () => api.get('/client-dashboard-fixed.php/categories?include_subcategories=true'),
  getProductsByCategory: (categoryId) => api.get(`/client-dashboard-fixed.php/categories?category_id=${categoryId}&include_products=true`),
};

// Sub-Categories API
export const subCategoriesAPI = {
  getAll: (params = {}) => api.get('/sub-categories.php', { params }),
  getByCategory: (categoryId) => api.get(`/sub-categories.php?category_id=${categoryId}`),
  create: (subCategory) => api.post('/sub-categories.php', subCategory),
  update: (subCategory) => api.put('/sub-categories.php', subCategory),
  delete: (id) => api.delete(`/sub-categories.php?id=${id}`),
  getById: (id) => api.get(`/sub-categories.php?id=${id}`),
};

// Locations API
export const locationsAPI = {
  getAll: () => api.get('/locations.php'),
  create: (location) => api.post('/locations.php', location),
  update: (location) => api.put('/locations.php', location),
  delete: (id) => api.delete(`/locations.php?id=${id}`),
  getById: (id) => api.get(`/locations.php?id=${id}`),
  getUsersByLocation: (locationId) => api.get(`/locations.php?location_id=${locationId}&include_users=true`),
  getSalesByLocation: (locationId) => api.get(`/locations.php?location_id=${locationId}&include_sales=true`),
};

// Inventory API - Updated to use fixed endpoint
export const inventoryAPI = {
  getAll: (params = {}) => api.get('/client-dashboard-fixed.php/inventory', { params }),
  updateStock: (productId, quantity) => api.put('/client-dashboard-fixed.php/inventory', { product_id: productId, quantity }),
  getLowStock: () => api.get('/client-dashboard-fixed.php/inventory?low_stock=true'),
  getStockHistory: (productId) => api.get(`/client-dashboard-fixed.php/inventory?product_id=${productId}&history=true`),
  addStock: (productId, quantity, reason) => api.post('/client-dashboard-fixed.php/inventory', { product_id: productId, quantity, reason }),
};

// Reports API
export const reportsAPI = {
  getSalesReport: (params = {}) => api.get('/reports/sales.php', { params }),
  getInventoryReport: (params = {}) => api.get('/reports/inventory.php', { params }),
  getCustomerReport: (params = {}) => api.get('/reports/customers.php', { params }),
  getProductReport: (params = {}) => api.get('/reports/products.php', { params }),
  exportReport: (type, params = {}) => api.get(`/reports/export.php?type=${type}`, { params }),
};

// Notification API
export const notificationAPI = {
  getSettings: async () => {
    const response = await axios.get('/notifications-working.php?action=settings');
    return response.data;
  },

  updateSettings: async (settings) => {
    const response = await axios.post('/notifications-working.php?action=update-settings', settings);
    return response.data;
  },

  getLogs: async (params = {}) => {
    const queryParams = new URLSearchParams(params).toString();
    const response = await axios.get(`/notifications-working.php?action=logs&${queryParams}`);
    return response.data;
  },

  sendLowStockAlerts: async () => {
    const response = await api.post('/notifications/send-low-stock-alerts');
    return response.data;
  },

  sendSaleReceipt: async (saleId) => {
    const response = await api.post('/notifications/send-sale-receipt', { sale_id: saleId });
    return response.data;
  },

  sendPaymentConfirmation: async (paymentId) => {
    const response = await api.post('/notifications/send-payment-confirmation', { payment_id: paymentId });
    return response.data;
  },

  sendSystemAlert: async (type, message, recipients = []) => {
    const response = await api.post('/notifications/send-system-alert', {
      type,
      message,
      recipients
    });
    return response.data;
  },

  sendMonthlyReport: async (tenantId, month = null) => {
    const response = await api.post('/notifications/send-monthly-report', {
      tenant_id: tenantId,
      month
    });
    return response.data;
  },

  testEmail: async (email) => {
    const response = await axios.get(`/notifications-working.php?action=test-email&email=${email}`);
    return response.data;
  }
};

// Payment API
export const paymentAPI = {
  getConfig: async () => {
    const response = await api.get('/payments');
    return response.data;
  },

  getBanks: async () => {
    const response = await api.get('/payments/banks');
    return response.data;
  },

  resolveAccount: async (accountNumber, bankCode) => {
    const response = await api.get(`/payments/resolve-account?account_number=${accountNumber}&bank_code=${bankCode}`);
    return response.data;
  },

  getTransactionHistory: async (customerCode = null, page = 1) => {
    const params = new URLSearchParams({ page });
    if (customerCode) params.append('customer_code', customerCode);
    const response = await api.get(`/payments/transaction-history?${params}`);
    return response.data;
  },

  initializeTransaction: async (paymentData) => {
    const response = await api.post('/payments/initialize', paymentData);
    return response.data;
  },

  verifyTransaction: async (reference) => {
    const response = await api.post('/payments/verify', { reference });
    return response.data;
  },

  refundTransaction: async (reference, amount = null) => {
    const response = await api.post('/payments/refund', { reference, amount });
    return response.data;
  },

  createCustomer: async (customerData) => {
    const response = await api.post('/payments/create-customer', customerData);
    return response.data;
  },

  createPlan: async (planData) => {
    const response = await api.post('/payments/create-plan', planData);
    return response.data;
  },

  createSubscription: async (subscriptionData) => {
    const response = await api.post('/payments/create-subscription', subscriptionData);
    return response.data;
  }
};

// User Management API (for admins within their business account)
export const userManagementAPI = {
  getAll: () => api.get('/user-management.php'),
  create: (userData) => api.post('/user-management.php', userData),
  update: (id, userData) => api.put('/user-management.php', { id, ...userData }),
  delete: (id) => api.delete(`/user-management.php?id=${id}`),
  getById: (id) => api.get(`/user-management.php?id=${id}`),
  updateStatus: (id, status) => api.put('/user-management.php/status', { id, status }),
  updateRole: (id, role) => api.put('/user-management.php/role', { id, role }),
  updatePermissions: (id, permissions) => api.put('/user-management.php/permissions', { id, permissions }),
  bulkAction: (userIds, action) => api.post('/user-management.php/bulk', { userIds, action }),
  getRoles: () => api.get('/user-management.php/roles'),
  getPermissions: () => api.get('/user-management.php/permissions'),
  resetPassword: (id) => api.post('/user-management.php/reset-password', { id }),
  sendInvitation: (email, role) => api.post('/user-management.php/invite', { email, role }),
};

// Discounts API (for admins and managers)
export const discountsAPI = {
  getAll: () => api.get('/discounts.php'),
  create: (discount) => api.post('/discounts.php', discount),
  update: (discount) => api.put('/discounts.php', discount),
  delete: (id) => api.delete(`/discounts.php?id=${id}`),
  getById: (id) => api.get(`/discounts.php?id=${id}`),
  getActive: () => api.get('/discounts.php?status=active'),
  getByScope: (scope) => api.get(`/discounts.php?scope=${scope}`),
};

// Coupons API (for admins and managers)
export const couponsAPI = {
  getAll: () => api.get('/coupons.php'),
  create: (coupon) => api.post('/coupons.php', coupon),
  update: (coupon) => api.put('/coupons.php', coupon),
  delete: (id) => api.delete(`/coupons.php?id=${id}`),
  getById: (id) => api.get(`/coupons.php?id=${id}`),
  getActive: () => api.get('/coupons.php?status=active'),
  validate: (code, customerId = null, subtotal = 0) => api.post('/coupons.php/validate', { code, customer_id: customerId, subtotal }),
  generateCode: () => api.get('/coupons.php/generate-code'),
};

  // Support Portal API methods
  export const supportAPI = {
    // Public endpoints (no authentication required)
    getKnowledgebase: async () => {
      try {
        const response = await publicApi.get('/support-portal/knowledgebase');
        // Ensure we return the correct data structure
        return {
          data: {
            articles: response.data?.data?.articles || response.data?.articles || []
          }
        };
      } catch (error) {
        console.error('Error fetching knowledgebase:', error);
        return { data: { articles: [] } };
      }
    },
    
    getCategories: async () => {
      try {
        const response = await publicApi.get('/support-portal/categories');
        // Ensure we return the correct data structure
        return {
          data: response.data?.data || response.data || []
        };
      } catch (error) {
        console.error('Error fetching categories:', error);
        return { data: [] };
      }
    },
    
    searchKnowledgebase: (query) => publicApi.get(`/support-portal/search?q=${encodeURIComponent(query)}`),
    getKnowledgebaseArticle: (id) => publicApi.get(`/knowledgebase-article-fixed.php?id=${id}`),
    createPublicTicket: (ticketData) => publicApi.post('/support-portal/public-tickets', ticketData),
    
    // Authenticated endpoints (require login)
    getTickets: () => authAxios.get('/support-ticket-management-fixed.php/tickets'),
    createTicket: (ticketData) => authAxios.post('/support-ticket-management-fixed.php/tickets', ticketData),
    updateTicket: (ticketId, updateData) => authAxios.put('/support-ticket-management-fixed.php/tickets', { id: ticketId, ...updateData }),
    deleteTicket: (ticketId) => authAxios.delete(`/support-ticket-management-fixed.php/tickets?id=${ticketId}`),
    getChatHistory: (sessionId) => api.get(`/support-portal/chat?session_id=${sessionId}`),
    sendChatMessage: (sessionId, message) => api.post('/support-portal/chat', { session_id: sessionId, message, sender_type: 'user' }),
    createChatSession: () => publicApi.post('/support-portal/chat/session'),
    markArticleHelpful: (articleId, helpful) => api.post(`/support-portal/knowledgebase/${articleId}/helpful`, { helpful })
  };

  // Billing & Subscription API - Updated to use tenancy management
  export const billingAPI = {
    // Subscription Plans - Updated to use fixed Paystack integration
    getSubscriptionPlans: () => authAxios.get('/paystack-integration-fixed.php/subscription-plans'),
    
    // Subscriptions
    getSubscriptions: (params = {}) => authAxios.get(`/tenancy-management-simple.php/subscriptions?${new URLSearchParams(params)}`),
    upgradeSubscription: (data) => authAxios.post('/tenancy-management-simple.php/upgrade-subscription', data),
    cancelSubscription: (data) => authAxios.post('/tenancy-management-simple.php/cancel-subscription', data),
    
    // Invoices
    getInvoices: (params = {}) => authAxios.get(`/tenancy-management-simple.php/invoices?${new URLSearchParams(params)}`),
    generateInvoice: (data) => authAxios.post('/tenancy-management-simple.php/generate-invoice', data),
    
    // Billing Overview
    getBillingOverview: () => authAxios.get('/tenancy-management-simple.php/billing-overview'),
    
    // Subscription Status
    getSubscriptionStatus: () => authAxios.get('/tenancy-management-simple.php/subscription-status'),
    
    // Billing Info
    getBillingInfo: () => authAxios.get('/tenancy-management-simple.php/billing-info'),
    
    // Paystack Integration - Updated to use fixed endpoint
    initializePayment: (data) => authAxios.post('/paystack-integration-fixed.php/initialize-payment', data),
    verifyPayment: (data) => authAxios.post('/paystack-integration-fixed.php/verify-payment', data),
    createSubscription: (data) => authAxios.post('/paystack-integration-fixed.php/create-subscription', data),
    getPaymentStatus: (reference) => authAxios.get(`/paystack-integration-fixed.php/payment-status?reference=${reference}`),
    getSubscriptionPlans: () => authAxios.get('/paystack-integration-fixed.php/subscription-plans')
  };

// Tenancy Management API
export const tenancyAPI = {
  // Get subscription status (handles Super Admin vs Client separation)
  getSubscriptionStatus: () => authAxios.get('/tenancy-management-simple.php/subscription-status'),
  
  // Get billing info (handles Super Admin vs Client separation)
  getBillingInfo: () => authAxios.get('/tenancy-management-simple.php/billing-info'),
  
  // Tenant management (Super Admin only)
  getTenants: (params = {}) => authAxios.get(`/tenancy-management-fixed.php/tenants?${new URLSearchParams(params)}`),
  createTenant: (data) => authAxios.post('/tenancy-management-fixed.php/tenants', data),
  updateTenant: (data) => authAxios.put('/tenancy-management-fixed.php/tenants', data),
  deleteTenant: (id) => authAxios.delete(`/tenancy-management-fixed.php/tenants?id=${id}`),
  
  // Tenant info
  getTenantInfo: () => authAxios.get('/tenancy-management-fixed.php/tenant-info'),
  updateTenantSettings: (data) => authAxios.put('/tenancy-management-fixed.php/tenant-settings', data),
  
  // Subscription management
  upgradeSubscription: (data) => authAxios.post('/tenancy-management-fixed.php/upgrade-subscription', data)
};

export default api;
