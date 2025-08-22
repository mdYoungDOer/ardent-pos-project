import { create } from 'zustand';
import { authAPI } from '../services/api';

const useAuthStore = create((set, get) => ({
  // State
  user: null,
  tenant: null,
  token: null,
  isAuthenticated: false,
  isLoading: false,
  error: null,

  // Actions
  login: async (email, password) => {
    set({ isLoading: true, error: null });
    try {
      const response = await authAPI.login(email, password);
      if (response.success) {
        set({
          user: response.user,
          tenant: response.tenant,
          token: response.token,
          isAuthenticated: true,
          isLoading: false,
          error: null
        });
        return response;
      } else {
        throw new Error(response.error || 'Login failed');
      }
    } catch (error) {
      set({
        isLoading: false,
        error: error.message || 'Login failed'
      });
      throw error;
    }
  },

  register: async (userData) => {
    set({ isLoading: true, error: null });
    try {
      const response = await authAPI.register(userData);
      if (response.success) {
        set({
          user: response.user,
          tenant: response.tenant,
          token: response.token,
          isAuthenticated: true,
          isLoading: false,
          error: null
        });
        return response;
      } else {
        throw new Error(response.error || 'Registration failed');
      }
    } catch (error) {
      set({
        isLoading: false,
        error: error.message || 'Registration failed'
      });
      throw error;
    }
  },

  logout: () => {
    authAPI.logout();
    set({
      user: null,
      tenant: null,
      token: null,
      isAuthenticated: false,
      isLoading: false,
      error: null
    });
  },

  initialize: () => {
    try {
      const token = authAPI.getToken();
      const user = authAPI.getUser();
      const tenant = authAPI.getTenant();

      if (token && user && tenant) {
        set({
          user,
          tenant,
          token,
          isAuthenticated: true,
          isLoading: false,
          error: null
        });
      }
    } catch (error) {
      console.error('Error initializing auth store:', error);
      authAPI.logout();
    }
  },

  clearError: () => set({ error: null })
}));

export default useAuthStore;
