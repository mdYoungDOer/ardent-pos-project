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

  // Debug function to test credentials
  testCredentials: async (email, password) => {
    console.log('Testing credentials for:', email);
    try {
      const response = await authAxios.post('/test-user-credentials.php', { email, password });
      console.log('Credentials test response:', response.data);
      return response.data;
    } catch (error) {
      console.error('Credentials test error:', error);
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

export default api;
