import { create } from 'zustand'
import { authAPI } from '../services/api'
import toast from 'react-hot-toast'

const useAuthStore = create((set, get) => ({
  user: null,
  tenant: null,
  isLoading: false,
  isAuthenticated: false,

  // Initialize auth state from localStorage
  init: () => {
    const user = authAPI.getCurrentUser()
    const tenant = authAPI.getCurrentTenant()
    const isAuthenticated = authAPI.isAuthenticated()

    set({
      user,
      tenant,
      isAuthenticated
    })

    // If authenticated, verify token
    if (isAuthenticated) {
      get().verifyToken()
    }
  },

  // Login
  login: async (credentials) => {
    set({ isLoading: true })
    
    try {
      const result = await authAPI.login(credentials)
      
      if (result.success) {
        const { user, tenant } = result.data
        
        set({
          user,
          tenant,
          isAuthenticated: true,
          isLoading: false
        })
        
        toast.success('Login successful!')
        return { success: true }
      } else {
        set({ isLoading: false })
        return { success: false, error: result.error }
      }
    } catch (error) {
      set({ isLoading: false })
      return { success: false, error: error.message }
    }
  },

  // Register
  register: async (userData) => {
    set({ isLoading: true })
    
    try {
      const result = await authAPI.register(userData)
      
      if (result.success) {
        const { user, tenant } = result.data
        
        set({
          user,
          tenant,
          isAuthenticated: true,
          isLoading: false
        })
        
        toast.success('Registration successful!')
        return { success: true }
      } else {
        set({ isLoading: false })
        return { success: false, error: result.error }
      }
    } catch (error) {
      set({ isLoading: false })
      return { success: false, error: error.message }
    }
  },

  // Verify token
  verifyToken: async () => {
    try {
      const result = await authAPI.verifyToken()
      
      if (result.success) {
        const { user, tenant } = result.data
        
        set({
          user,
          tenant,
          isAuthenticated: true
        })
        
        return { success: true }
      } else {
        set({
          user: null,
          tenant: null,
          isAuthenticated: false
        })
        
        return { success: false, error: result.error }
      }
    } catch (error) {
      set({
        user: null,
        tenant: null,
        isAuthenticated: false
      })
      
      return { success: false, error: error.message }
    }
  },

  // Logout
  logout: () => {
    authAPI.logout()
    set({
      user: null,
      tenant: null,
      isAuthenticated: false,
      isLoading: false
    })
  },

  // Helper methods
  hasRole: (role) => {
    const { user } = get()
    return user?.role === role
  },

  hasAnyRole: (roles) => {
    const { user } = get()
    return roles.includes(user?.role)
  },

  canAccess: (feature) => {
    const { user } = get()
    if (!user) return false

    const permissions = {
      dashboard: ['admin', 'manager', 'cashier', 'inventory_staff', 'viewer', 'super_admin'],
      products: ['admin', 'manager', 'super_admin'],
      inventory: ['admin', 'manager', 'inventory_staff', 'super_admin'],
      sales: ['admin', 'manager', 'cashier', 'super_admin'],
      customers: ['admin', 'manager', 'super_admin'],
      reports: ['admin', 'manager', 'viewer', 'super_admin'],
      settings: ['admin', 'super_admin'],
      users: ['admin', 'super_admin']
    }

    return permissions[feature]?.includes(user.role) || false
  }
}))

export { useAuthStore }
