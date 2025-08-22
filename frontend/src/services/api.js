import axios from 'axios';

// Base API configuration
const API_URL = window.location.origin + '/api';

// Create axios instance
const api = axios.create({
  baseURL: API_URL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Simple authentication API object
export const authAPI = {
  // Login with email and password
  async login(email, password) {
    try {
      const response = await api.post('/auth/login.php', {
        email,
        password
      });
      
      if (response.data.success) {
        // Store token and user data in localStorage
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('user', JSON.stringify(response.data.user));
        localStorage.setItem('tenant', JSON.stringify(response.data.tenant));
        return response.data;
      } else {
        throw new Error(response.data.error || 'Login failed');
      }
    } catch (error) {
      console.error('Login error:', error);
      throw error;
    }
  },

  // Register new business account
  async register(userData) {
    try {
      const response = await api.post('/auth/register.php', userData);
      
      if (response.data.success) {
        // Store token and user data in localStorage
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('user', JSON.stringify(response.data.user));
        localStorage.setItem('tenant', JSON.stringify(response.data.tenant));
        return response.data;
      } else {
        throw new Error(response.data.error || 'Registration failed');
      }
    } catch (error) {
      console.error('Registration error:', error);
      throw error;
    }
  },

  // Verify JWT token
  async verifyToken() {
    try {
      const token = localStorage.getItem('token');
      if (!token) {
        throw new Error('No token found');
      }

      const response = await api.post('/auth/verify.php', {
        token
      });
      
      if (response.data.success) {
        // Update stored user data
        localStorage.setItem('user', JSON.stringify(response.data.user));
        localStorage.setItem('tenant', JSON.stringify(response.data.tenant));
        return response.data;
      } else {
        throw new Error(response.data.error || 'Token verification failed');
      }
    } catch (error) {
      console.error('Token verification error:', error);
      // Clear invalid token
      this.logout();
      throw error;
    }
  },

  // Logout user
  logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    localStorage.removeItem('tenant');
  },

  // Check if user is authenticated
  isAuthenticated() {
    const token = localStorage.getItem('token');
    const user = localStorage.getItem('user');
    return !!(token && user);
  },

  // Get current user data
  getCurrentUser() {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
  },

  // Get current tenant data
  getCurrentTenant() {
    const tenant = localStorage.getItem('tenant');
    return tenant ? JSON.parse(tenant) : null;
  },

  // Get auth token
  getToken() {
    return localStorage.getItem('token');
  }
};

// API with authentication interceptor
const authenticatedApi = axios.create({
  baseURL: API_URL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Add auth token to requests
authenticatedApi.interceptors.request.use(
  (config) => {
    const token = authAPI.getToken();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Handle auth errors
authenticatedApi.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      authAPI.logout();
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// Dashboard API endpoints
export const dashboardAPI = {
  async getStats() {
    const response = await authenticatedApi.get('/dashboard/stats');
    return response.data;
  },
  
  async getRecentSales() {
    const response = await authenticatedApi.get('/dashboard/recent-sales');
    return response.data;
  },
  
  async getTopProducts() {
    const response = await authenticatedApi.get('/dashboard/top-products');
    return response.data;
  }
};

// Products API endpoints
export const productsAPI = {
  async getAll() {
    const response = await authenticatedApi.get('/products');
    return response.data;
  },
  
  async getById(id) {
    const response = await authenticatedApi.get(`/products/${id}`);
    return response.data;
  },
  
  async create(productData) {
    const response = await authenticatedApi.post('/products', productData);
    return response.data;
  },
  
  async update(id, productData) {
    const response = await authenticatedApi.put(`/products/${id}`, productData);
    return response.data;
  },
  
  async delete(id) {
    const response = await authenticatedApi.delete(`/products/${id}`);
    return response.data;
  }
};

// Sales API endpoints
export const salesAPI = {
  async getAll() {
    const response = await authenticatedApi.get('/sales');
    return response.data;
  },
  
  async getById(id) {
    const response = await authenticatedApi.get(`/sales/${id}`);
    return response.data;
  },
  
  async create(saleData) {
    const response = await authenticatedApi.post('/sales', saleData);
    return response.data;
  },
  
  async update(id, saleData) {
    const response = await authenticatedApi.put(`/sales/${id}`, saleData);
    return response.data;
  },
  
  async delete(id) {
    const response = await authenticatedApi.delete(`/sales/${id}`);
    return response.data;
  }
};

// Customers API endpoints
export const customersAPI = {
  async getAll() {
    const response = await authenticatedApi.get('/customers');
    return response.data;
  },
  
  async getById(id) {
    const response = await authenticatedApi.get(`/customers/${id}`);
    return response.data;
  },
  
  async create(customerData) {
    const response = await authenticatedApi.post('/customers', customerData);
    return response.data;
  },
  
  async update(id, customerData) {
    const response = await authenticatedApi.put(`/customers/${id}`, customerData);
    return response.data;
  },
  
  async delete(id) {
    const response = await authenticatedApi.delete(`/customers/${id}`);
    return response.data;
  }
};

// Export the main API instance for backward compatibility
export default api;
