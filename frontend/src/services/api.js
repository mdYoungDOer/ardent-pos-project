import axios from 'axios';

// Base API configuration for authenticated endpoints
const api = axios.create({
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

export const authAPI = {
  login: async (email, password) => {
    console.log('Making login request to /auth/login.php');
    console.log('Request data:', { email, password: '***' });
    
    try {
      const response = await authAxios.post('/auth/login.php', { email, password });
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
      authAPI.logout();
      window.location.href = '/auth/login';
    }
    return Promise.reject(error);
  }
);

// Dashboard API
export const dashboardAPI = {
  getStats: async () => {
    try {
      console.log('Fetching dashboard stats from /api/dashboard.php');
      const response = await api.get('/dashboard.php');
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

// Super Admin API
export const superAdminAPI = {
  // Dashboard & Analytics
  getStats: () => api.get('/super-admin-enhanced.php'),
  getAnalytics: (params = {}) => api.get('/super-admin-enhanced.php/analytics', { params }),
  getActivity: () => api.get('/super-admin.php/activity'),
  
  // Tenant Management
  getTenants: (params = {}) => api.get('/super-admin.php/tenants', { params }),
  createTenant: (data) => api.post('/super-admin.php/tenant', data),
  updateTenant: (id, data) => api.put(`/super-admin.php/tenant/${id}`, data),
  deleteTenant: (id) => api.delete(`/super-admin.php/tenant/${id}`),
  
  // User Management
  getUsers: (params = {}) => api.get('/super-admin-enhanced.php/users', { params }),
  createUser: (data) => api.post('/super-admin.php/user', data),
  updateUser: (id, data) => api.put(`/super-admin-enhanced.php/user/${id}`, data),
  deleteUser: (id) => api.delete(`/super-admin-enhanced.php/user/${id}`),
  bulkUserAction: (userIds, action) => api.post('/super-admin-enhanced.php/users/bulk', { userIds, action }),
  
  // System Settings
  getSettings: () => api.get('/super-admin-enhanced.php/settings'),
  updateSettings: (category, data) => api.put(`/super-admin-enhanced.php/settings/${category}`, data),
  
  // System Maintenance
  toggleMaintenanceMode: (enabled) => api.post('/super-admin.php/maintenance', { enabled }),
  createBackup: () => api.post('/super-admin.php/backup'),
  getBackups: () => api.get('/super-admin.php/backups'),
  clearAllSessions: () => api.post('/clear-sessions.php'),
  
  // System Health
  getSystemHealth: () => api.get('/super-admin.php/health'),
  getSystemLogs: (params = {}) => api.get('/super-admin.php/logs', { params }),
  
  // Billing & Subscriptions
  getBillingStats: () => api.get('/super-admin-enhanced.php/billing/stats'),
  getSubscriptions: (params = {}) => api.get('/super-admin-enhanced.php/subscriptions', { params }),
  updateSubscription: (id, data) => api.put(`/super-admin-enhanced.php/subscription/${id}`, data),
  createSubscription: (data) => api.post('/super-admin-enhanced.php/subscription', data),
  cancelSubscription: (id, reason) => api.post(`/super-admin-enhanced.php/subscription/${id}/cancel`, { reason }),
  getSubscriptionPlans: () => api.get('/super-admin-enhanced.php/subscription-plans'),
  updateSubscriptionPlan: (id, planData) => api.put(`/super-admin-enhanced.php/subscription/${id}/plan`, planData),
  
  // Security & Audit
  getAuditLogs: (params = {}) => api.get('/super-admin.php/audit-logs', { params }),
  getSecurityEvents: (params = {}) => api.get('/super-admin.php/security-events', { params }),
  
  // API Management
  getApiKeys: () => api.get('/super-admin.php/api-keys'),
  createApiKey: (data) => api.post('/super-admin.php/api-key', data),
  revokeApiKey: (id) => api.delete(`/super-admin.php/api-key/${id}`)
};

// Products API
export const productsAPI = {
  getAll: () => api.get('/products.php'),
  create: (product) => api.post('/products.php', product),
  update: (product) => api.put('/products.php', product),
  delete: (id) => api.delete(`/products.php?id=${id}`),
  getById: (id) => api.get(`/products.php?id=${id}`),
  search: (query) => api.get(`/products.php?search=${encodeURIComponent(query)}`),
};

// Sales API
export const salesAPI = {
  getAll: () => api.get('/sales.php'),
  create: (sale) => api.post('/sales.php', sale),
  update: (sale) => api.put('/sales.php', sale),
  delete: (id) => api.delete(`/sales.php?id=${id}`),
  getById: (id) => api.get(`/sales.php?id=${id}`),
  getReports: () => api.get('/sales/reports.php'),
  getByDateRange: (startDate, endDate) => api.get(`/sales.php?start_date=${startDate}&end_date=${endDate}`),
};

// Customers API
export const customersAPI = {
  getAll: () => api.get('/customers.php'),
  create: (customer) => api.post('/customers.php', customer),
  update: (customer) => api.put('/customers.php', customer),
  delete: (id) => api.delete(`/customers.php?id=${id}`),
  getById: (id) => api.get(`/customers.php?id=${id}`),
  search: (query) => api.get(`/customers.php?search=${encodeURIComponent(query)}`),
};

// Categories API
export const categoriesAPI = {
  getAll: (params = {}) => api.get('/categories.php', { params }),
  create: (category) => api.post('/categories.php', category),
  update: (category) => api.put('/categories.php', category),
  delete: (id) => api.delete(`/categories.php?id=${id}`),
  getById: (id) => api.get(`/categories.php?id=${id}`),
  getSubcategories: (parentId) => api.get(`/categories.php?parent_id=${parentId}`),
  getHierarchical: () => api.get('/categories.php?include_subcategories=true'),
  getProductsByCategory: (categoryId) => api.get(`/categories.php?category_id=${categoryId}&include_products=true`),
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

// Inventory API
export const inventoryAPI = {
  getAll: () => api.get('/inventory.php'),
  updateStock: (productId, quantity) => api.put('/inventory.php', { product_id: productId, quantity }),
  getLowStock: () => api.get('/inventory.php?low_stock=true'),
  getStockHistory: (productId) => api.get(`/inventory.php?product_id=${productId}&history=true`),
  addStock: (productId, quantity, reason) => api.post('/inventory.php', { product_id: productId, quantity, reason }),
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

export default api;
