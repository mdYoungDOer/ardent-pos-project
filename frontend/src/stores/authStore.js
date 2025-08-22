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
    const token = localStorage.getItem('token');
    const user = localStorage.getItem('user');
    const tenant = localStorage.getItem('tenant');

    if (token && user && tenant) {
      set({
        token,
        user: JSON.parse(user),
        tenant: JSON.parse(tenant),
        isAuthenticated: true
      });
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
