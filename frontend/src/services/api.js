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
  getStats: () => api.get('/dashboard.php'),
};

// Super Admin API
export const superAdminAPI = {
  getStats: () => api.get('/super-admin.php'),
  getTenants: (params = {}) => api.get('/super-admin.php/tenants', { params }),
  getActivity: () => api.get('/super-admin.php/activity'),
  createTenant: (data) => api.post('/super-admin.php/tenant', data),
  updateTenant: (id, data) => api.put(`/super-admin.php/${id}`, data),
  deleteTenant: (id) => api.delete(`/super-admin.php/${id}`)
};

// Products API
export const productsAPI = {
  getAll: () => api.get('/products.php'),
  create: (product) => api.post('/products.php', product),
  update: (product) => api.put('/products.php', product),
  delete: (id) => api.delete(`/products.php?id=${id}`),
};

// Sales API
export const salesAPI = {
  getAll: () => api.get('/sales.php'),
  create: (sale) => api.post('/sales.php', sale),
  getReports: () => api.get('/sales/reports.php'),
};

// Customers API
export const customersAPI = {
  getAll: () => api.get('/customers.php'),
  create: (customer) => api.post('/customers.php', customer),
  update: (customer) => api.put('/customers.php', customer),
  delete: (id) => api.delete(`/customers.php?id=${id}`),
};

// Notification API
export const notificationAPI = {
  getSettings: async () => {
    const response = await api.get('/notifications-working.php?action=settings');
    return response.data;
  },

  updateSettings: async (settings) => {
    const response = await api.post('/notifications-working.php?action=update-settings', settings);
    return response.data;
  },

  getLogs: async (params = {}) => {
    const queryParams = new URLSearchParams(params).toString();
    const response = await api.get(`/notifications-working.php?action=logs&${queryParams}`);
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
    const response = await api.get(`/notifications-working.php?action=test-email&email=${email}`);
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

export default api;
