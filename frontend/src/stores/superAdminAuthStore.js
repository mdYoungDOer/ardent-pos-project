import { create } from 'zustand';

const useSuperAdminAuthStore = create((set, get) => ({
  // State
  user: null,
  token: null,
  isAuthenticated: false,
  isLoading: false,
  error: null,

  // Actions
  login: async (email, password) => {
    set({ isLoading: true, error: null });
    try {
      const response = await fetch('/super-admin-login-final.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password })
      });

      const data = await response.json();

      if (data.success) {
        // Store Super Admin specific data
        localStorage.setItem('super_admin_token', data.token);
        localStorage.setItem('super_admin_user', JSON.stringify(data.user));
        
        set({
          user: data.user,
          token: data.token,
          isAuthenticated: true,
          isLoading: false,
          error: null
        });
        return data;
      } else {
        throw new Error(data.error || 'Super admin login failed');
      }
    } catch (error) {
      set({
        isLoading: false,
        error: error.message || 'Super admin login failed'
      });
      throw error;
    }
  },

  logout: () => {
    // Clear Super Admin specific data
    localStorage.removeItem('super_admin_token');
    localStorage.removeItem('super_admin_user');
    
    set({
      user: null,
      token: null,
      isAuthenticated: false,
      isLoading: false,
      error: null
    });
  },

  initialize: () => {
    try {
      const token = localStorage.getItem('super_admin_token');
      const user = JSON.parse(localStorage.getItem('super_admin_user') || 'null');

      if (token && user && user.role === 'super_admin') {
        set({
          user,
          token,
          isAuthenticated: true,
          isLoading: false,
          error: null
        });
      } else {
        // Clear invalid data
        localStorage.removeItem('super_admin_token');
        localStorage.removeItem('super_admin_user');
      }
    } catch (error) {
      console.error('Error initializing super admin auth store:', error);
      get().logout();
    }
  },

  clearError: () => set({ error: null })
}));

export default useSuperAdminAuthStore;
