import axios from 'axios'

// Get API URL from environment variables with fallback
const API_URL = import.meta.env.VITE_API_URL || 
                (window.location.origin + '/api') || 
                'http://localhost:8000'

console.log('API URL:', API_URL) // Debug log

const api = axios.create({
  baseURL: API_URL.startsWith('http') ? `${API_URL}/api` : `${API_URL}`,
  headers: {
    'Content-Type': 'application/json',
  },
  timeout: 30000, // Increased timeout for production
  withCredentials: true, // Enable credentials for CORS
})

// Request interceptor
api.interceptors.request.use(
  (config) => {
    // Add tenant ID to headers if available
    try {
      const authData = JSON.parse(localStorage.getItem('auth-storage') || '{}')
      if (authData.state?.tenant?.id) {
        config.headers['X-Tenant-ID'] = authData.state.tenant.id
      }
      
      // Add authorization header if token exists
      if (authData.state?.token) {
        config.headers['Authorization'] = `Bearer ${authData.state.token}`
      }
    } catch (error) {
      console.warn('Error parsing auth data:', error)
    }
    
    // Log request for debugging
    console.log('API Request:', config.method?.toUpperCase(), config.url)
    
    return config
  },
  (error) => {
    console.error('Request error:', error)
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response) => {
    // Log successful response for debugging
    console.log('API Response:', response.status, response.config.url)
    return response
  },
  (error) => {
    console.error('API Error:', error.response?.status, error.response?.data, error.config?.url)
    
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
    
    // Network errors
    if (!error.response) {
      console.error('Network error:', error.message)
    }
    
    return Promise.reject(error)
  }
)

export default api
