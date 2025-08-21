import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000'

const api = axios.create({
  baseURL: `${API_URL}/api`,
  headers: {
    'Content-Type': 'application/json',
  },
  timeout: 10000,
})

// Request interceptor
api.interceptors.request.use(
  (config) => {
    // Add tenant ID to headers if available
    const authData = JSON.parse(localStorage.getItem('auth-storage') || '{}')
    if (authData.state?.tenant?.id) {
      config.headers['X-Tenant-ID'] = authData.state.tenant.id
    }
    
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response) => {
    return response
  },
  (error) => {
    // Handle common errors
    if (error.response?.status === 401) {
      // Token expired or invalid
      localStorage.removeItem('auth-storage')
      window.location.href = '/auth/login'
    }
    
    if (error.response?.status === 403) {
      // Insufficient permissions
      console.error('Access denied:', error.response.data.message)
    }
    
    if (error.response?.status >= 500) {
      // Server error
      console.error('Server error:', error.response.data.message)
    }
    
    return Promise.reject(error)
  }
)

export default api
