import axios from 'axios'
import toast from 'react-hot-toast'

// Create axios instance
const api = axios.create({
  baseURL: window.location.origin,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
  }
})

// Request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth-token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor to handle auth errors
api.interceptors.response.use(
  (response) => {
    return response
  },
  (error) => {
    if (error.response?.status === 401) {
      // Clear invalid token
      localStorage.removeItem('auth-token')
      localStorage.removeItem('auth-user')
      localStorage.removeItem('auth-tenant')
      window.location.href = '/auth/login'
    }
    return Promise.reject(error)
  }
)

// Authentication API methods
export const authAPI = {
  // Login
  login: async (credentials) => {
    try {
      const response = await api.post('/auth/login-simple.php', credentials)
      const { token, user, tenant } = response.data
      
      // Store auth data
      localStorage.setItem('auth-token', token)
      localStorage.setItem('auth-user', JSON.stringify(user))
      localStorage.setItem('auth-tenant', JSON.stringify(tenant))
      
      return { success: true, data: response.data }
    } catch (error) {
      const message = error.response?.data?.error || error.response?.data?.message || 'Login failed'
      toast.error(message)
      return { success: false, error: message }
    }
  },

  // Register
  register: async (userData) => {
    try {
      const response = await api.post('/auth/register.php', userData)
      const { token, user, tenant } = response.data
      
      // Store auth data
      localStorage.setItem('auth-token', token)
      localStorage.setItem('auth-user', JSON.stringify(user))
      localStorage.setItem('auth-tenant', JSON.stringify(tenant))
      
      return { success: true, data: response.data }
    } catch (error) {
      const message = error.response?.data?.error || 'Registration failed'
      toast.error(message)
      return { success: false, error: message }
    }
  },

  // Verify token
  verifyToken: async () => {
    try {
      const token = localStorage.getItem('auth-token')
      if (!token) {
        return { success: false, error: 'No token found' }
      }

      const response = await api.post('/auth/verify.php')
      const { user, tenant } = response.data
      
      // Update stored data
      localStorage.setItem('auth-user', JSON.stringify(user))
      localStorage.setItem('auth-tenant', JSON.stringify(tenant))
      
      return { success: true, data: response.data }
    } catch (error) {
      // Clear invalid data
      localStorage.removeItem('auth-token')
      localStorage.removeItem('auth-user')
      localStorage.removeItem('auth-tenant')
      
      return { success: false, error: 'Invalid token' }
    }
  },

  // Logout
  logout: () => {
    localStorage.removeItem('auth-token')
    localStorage.removeItem('auth-user')
    localStorage.removeItem('auth-tenant')
    window.location.href = '/auth/login'
  },

  // Get current user
  getCurrentUser: () => {
    const user = localStorage.getItem('auth-user')
    return user ? JSON.parse(user) : null
  },

  // Get current tenant
  getCurrentTenant: () => {
    const tenant = localStorage.getItem('auth-tenant')
    return tenant ? JSON.parse(tenant) : null
  },

  // Check if authenticated
  isAuthenticated: () => {
    return !!localStorage.getItem('auth-token')
  }
}

// Dashboard API methods
export const dashboardAPI = {
  getStats: async () => {
    try {
      const response = await api.get('/api/dashboard/stats')
      return { success: true, data: response.data }
    } catch (error) {
      return { success: false, error: error.message }
    }
  }
}

// Products API methods
export const productsAPI = {
  getAll: async () => {
    try {
      const response = await api.get('/api/products')
      return { success: true, data: response.data }
    } catch (error) {
      return { success: false, error: error.message }
    }
  },

  create: async (productData) => {
    try {
      const response = await api.post('/api/products', productData)
      return { success: true, data: response.data }
    } catch (error) {
      return { success: false, error: error.message }
    }
  },

  update: async (id, productData) => {
    try {
      const response = await api.put(`/api/products/${id}`, productData)
      return { success: true, data: response.data }
    } catch (error) {
      return { success: false, error: error.message }
    }
  },

  delete: async (id) => {
    try {
      const response = await api.delete(`/api/products/${id}`)
      return { success: true, data: response.data }
    } catch (error) {
      return { success: false, error: error.message }
    }
  }
}

// Sales API methods
export const salesAPI = {
  getAll: async () => {
    try {
      const response = await api.get('/api/sales')
      return { success: true, data: response.data }
    } catch (error) {
      return { success: false, error: error.message }
    }
  },

  create: async (saleData) => {
    try {
      const response = await api.post('/api/sales', saleData)
      return { success: true, data: response.data }
    } catch (error) {
      return { success: false, error: error.message }
    }
  }
}

// Customers API methods
export const customersAPI = {
  getAll: async () => {
    try {
      const response = await api.get('/api/customers')
      return { success: true, data: response.data }
    } catch (error) {
      return { success: false, error: error.message }
    }
  },

  create: async (customerData) => {
    try {
      const response = await api.post('/api/customers', customerData)
      return { success: true, data: response.data }
    } catch (error) {
      return { success: false, error: error.message }
    }
  }
}

export default api
