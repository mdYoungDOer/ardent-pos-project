import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import api from '../services/api'
import toast from 'react-hot-toast'

const useAuthStore = create(
  persist(
    (set, get) => ({
      user: null,
      tenant: null,
      token: null,
      isLoading: false,
      isAuthenticated: false,

      login: async (credentials) => {
        set({ isLoading: true })
        try {
          console.log('Attempting login with:', credentials.email)
          
          const response = await api.post('/auth/login', credentials)
          console.log('Login response:', response.data)
          
          const { token, user, tenant } = response.data

          set({
            user,
            tenant,
            token,
            isAuthenticated: true,
            isLoading: false
          })

          // Set token in API headers
          api.defaults.headers.common['Authorization'] = `Bearer ${token}`
          
          toast.success('Login successful!')
          return { success: true }
        } catch (error) {
          console.error('Login error:', error)
          set({ isLoading: false })
          
          let message = 'Login failed'
          if (error.response?.data?.error) {
            message = error.response.data.error
          } else if (error.response?.data?.message) {
            message = error.response.data.message
          } else if (error.message) {
            message = error.message
          }
          
          toast.error(message)
          return { success: false, error: message }
        }
      },

      register: async (userData) => {
        set({ isLoading: true })
        try {
          console.log('Attempting registration with:', userData.email)
          
          const response = await api.post('/auth/register', userData)
          console.log('Registration response:', response.data)
          
          const { token, user, tenant } = response.data

          set({
            user,
            tenant,
            token,
            isAuthenticated: true,
            isLoading: false
          })

          // Set token in API headers
          api.defaults.headers.common['Authorization'] = `Bearer ${token}`
          
          toast.success('Registration successful!')
          return { success: true }
        } catch (error) {
          console.error('Registration error:', error)
          set({ isLoading: false })
          
          let message = 'Registration failed'
          if (error.response?.data?.error) {
            message = error.response.data.error
          } else if (error.response?.data?.message) {
            message = error.response.data.message
          } else if (error.message) {
            message = error.message
          }
          
          toast.error(message)
          return { success: false, error: message }
        }
      },

      logout: async () => {
        try {
          await api.post('/auth/logout')
        } catch (error) {
          // Ignore logout errors
          console.log('Logout error (ignored):', error)
        }

        set({
          user: null,
          tenant: null,
          token: null,
          isAuthenticated: false,
          isLoading: false
        })

        // Remove token from API headers
        delete api.defaults.headers.common['Authorization']
        
        toast.success('Logged out successfully')
      },

      checkAuth: async () => {
        const { token } = get()
        
        if (!token) {
          set({ isLoading: false, isAuthenticated: false })
          return
        }

        set({ isLoading: true })
        
        try {
          // Set token in API headers
          api.defaults.headers.common['Authorization'] = `Bearer ${token}`
          
          const response = await api.get('/auth/me')
          const { user, tenant } = response.data

          set({
            user,
            tenant,
            isAuthenticated: true,
            isLoading: false
          })
        } catch (error) {
          console.log('Auth check failed:', error)
          // Token is invalid, clear auth state
          set({
            user: null,
            tenant: null,
            token: null,
            isAuthenticated: false,
            isLoading: false
          })
          
          delete api.defaults.headers.common['Authorization']
        }
      },

      updateProfile: async (profileData) => {
        set({ isLoading: true })
        try {
          const response = await api.put('/auth/profile', profileData)
          const { user } = response.data

          set({
            user,
            isLoading: false
          })

          toast.success('Profile updated successfully!')
          return { success: true }
        } catch (error) {
          set({ isLoading: false })
          const message = error.response?.data?.error || 'Profile update failed'
          toast.error(message)
          return { success: false, error: message }
        }
      },

      changePassword: async (passwordData) => {
        set({ isLoading: true })
        try {
          await api.put('/auth/password', passwordData)
          
          set({ isLoading: false })
          toast.success('Password changed successfully!')
          return { success: true }
        } catch (error) {
          set({ isLoading: false })
          const message = error.response?.data?.error || 'Password change failed'
          toast.error(message)
          return { success: false, error: message }
        }
      },

      forgotPassword: async (email) => {
        set({ isLoading: true })
        try {
          await api.post('/auth/forgot-password', { email })
          
          set({ isLoading: false })
          toast.success('Password reset link sent to your email!')
          return { success: true }
        } catch (error) {
          set({ isLoading: false })
          const message = error.response?.data?.error || 'Failed to send reset link'
          toast.error(message)
          return { success: false, error: message }
        }
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
    }),
    {
      name: 'auth-storage',
      partialize: (state) => ({
        token: state.token,
        user: state.user,
        tenant: state.tenant,
        isAuthenticated: state.isAuthenticated
      })
    }
  )
)

export { useAuthStore }
