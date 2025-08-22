import axios from 'axios';

// Base API configuration
const api = axios.create({
  baseURL: '/api',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Auth API - direct PHP endpoints (no /api prefix)
export const authAPI = {
  login: async (email, password) => {
    const response = await axios.post('/auth/login.php', { email, password });
    if (response.data.success) {
      localStorage.setItem('token', response.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.user));
      localStorage.setItem('tenant', JSON.stringify(response.data.tenant));
    }
    return response.data;
  },

  register: async (userData) => {
    const response = await axios.post('/auth/register.php', userData);
    if (response.data.success) {
      localStorage.setItem('token', response.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.user));
      localStorage.setItem('tenant', JSON.stringify(response.data.tenant));
    }
    return response.data;
  },

  verify: async (token) => {
    const response = await axios.post('/auth/verify.php', { token });
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

// Authenticated API with token
const authenticatedApi = axios.create({
  baseURL: '/api',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Add token to requests
authenticatedApi.interceptors.request.use((config) => {
  const token = authAPI.getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle 401 responses
authenticatedApi.interceptors.response.use(
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
  getStats: () => authenticatedApi.get('/dashboard.php'),
};

// Super Admin API
export const superAdminAPI = {
  getStats: () => authenticatedApi.get('/super-admin.php'),
  getTenants: (params = {}) => authenticatedApi.get('/super-admin.php/tenants', { params }),
  getActivity: () => authenticatedApi.get('/super-admin.php/activity'),
  createTenant: (data) => authenticatedApi.post('/super-admin.php/tenant', data),
  updateTenant: (id, data) => authenticatedApi.put(`/super-admin.php/${id}`, data),
  deleteTenant: (id) => authenticatedApi.delete(`/super-admin.php/${id}`)
};

// Products API
export const productsAPI = {
  getAll: () => authenticatedApi.get('/products.php'),
  create: (product) => authenticatedApi.post('/products.php', product),
  update: (product) => authenticatedApi.put('/products.php', product),
  delete: (id) => authenticatedApi.delete(`/products.php?id=${id}`),
};

// Sales API
export const salesAPI = {
  getAll: () => authenticatedApi.get('/sales.php'),
  create: (sale) => authenticatedApi.post('/sales.php', sale),
  getReports: () => authenticatedApi.get('/sales/reports.php'),
};

// Customers API
export const customersAPI = {
  getAll: () => authenticatedApi.get('/customers.php'),
  create: (customer) => authenticatedApi.post('/customers.php', customer),
  update: (customer) => authenticatedApi.put('/customers.php', customer),
  delete: (id) => authenticatedApi.delete(`/customers.php?id=${id}`),
};

export default api;
