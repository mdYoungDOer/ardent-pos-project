import { create } from 'zustand';
import { authAPI } from '../services/api';

const useAuthStore = create((set, get) => ({
  // State
  user: null,
  tenant: null,
  token: null,
  isLoading: false,
  isAuthenticated: false,

  // Initialize auth state from localStorage
  initialize: () => {
    try {
      const token = localStorage.getItem('token');
      const user = localStorage.getItem('user');
      const tenant = localStorage.getItem('tenant');

      if (token && user && tenant) {
        // Parse JSON with error handling
        let parsedUser, parsedTenant;
        try {
          parsedUser = JSON.parse(user);
          parsedTenant = JSON.parse(tenant);
        } catch (parseError) {
          console.error('Error parsing stored user/tenant data:', parseError);
          // Clear corrupted data
          localStorage.removeItem('token');
          localStorage.removeItem('user');
          localStorage.removeItem('tenant');
          return;
        }

        set({
          token,
          user: parsedUser,
          tenant: parsedTenant,
          isAuthenticated: true
        });
      }
    } catch (error) {
      console.error('Error initializing auth store:', error);
      // Clear any corrupted data
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      localStorage.removeItem('tenant');
    }
  },

  // Login
  login: async (email, password) => {
    set({ isLoading: true });
    try {
      const response = await authAPI.login(email, password);
      set({
        user: response.user,
        tenant: response.tenant,
        token: response.token,
        isAuthenticated: true,
        isLoading: false
      });
      return { success: true, data: response };
    } catch (error) {
      set({ isLoading: false });
      return { 
        success: false, 
        error: error.response?.data?.error || error.message || 'Login failed' 
      };
    }
  },

  // Register
  register: async (userData) => {
    set({ isLoading: true });
    try {
      const response = await authAPI.register(userData);
      set({
        user: response.user,
        tenant: response.tenant,
        token: response.token,
        isAuthenticated: true,
        isLoading: false
      });
      return { success: true, data: response };
    } catch (error) {
      set({ isLoading: false });
      return { 
        success: false, 
        error: error.response?.data?.error || error.message || 'Registration failed' 
      };
    }
  },

  // Verify token
  verifyToken: async () => {
    try {
      const response = await authAPI.verifyToken();
      set({
        user: response.user,
        tenant: response.tenant,
        isAuthenticated: true
      });
      return { success: true, data: response };
    } catch (error) {
      set({
        user: null,
        tenant: null,
        token: null,
        isAuthenticated: false
      });
      return { 
        success: false, 
        error: error.response?.data?.error || error.message || 'Token verification failed' 
      };
    }
  },

  // Logout
  logout: () => {
    authAPI.logout();
    set({
      user: null,
      tenant: null,
      token: null,
      isAuthenticated: false,
      isLoading: false
    });
  },

  // Get current user
  getCurrentUser: () => {
    return get().user;
  },

  // Get current tenant
  getCurrentTenant: () => {
    return get().tenant;
  },

  // Check if authenticated
  checkAuth: () => {
    return get().isAuthenticated;
  }
}));

export default useAuthStore;
