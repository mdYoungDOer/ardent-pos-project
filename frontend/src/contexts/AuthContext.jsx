import React, { createContext, useContext, useState, useEffect } from 'react';
import { authAPI } from '../services/api';

const AuthContext = createContext();

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Check for existing token on app load
  useEffect(() => {
    const initializeAuth = async () => {
      try {
        const token = localStorage.getItem('token');
        const user = localStorage.getItem('user');
        
        if (token && user) {
          try {
            // Try to verify token
            const response = await authAPI.verifyToken();
            if (response.success) {
              setUser(response.user);
            } else {
              // Token is invalid, clear it
              localStorage.removeItem('token');
              localStorage.removeItem('user');
              localStorage.removeItem('tenant');
            }
          } catch (error) {
            console.error('Token verification failed:', error);
            // If verification fails, try to use stored user data
            try {
              const parsedUser = JSON.parse(user);
              setUser(parsedUser);
            } catch (parseError) {
              console.error('Failed to parse stored user:', parseError);
              localStorage.removeItem('token');
              localStorage.removeItem('user');
              localStorage.removeItem('tenant');
            }
          }
        }
      } catch (error) {
        console.error('Auth initialization error:', error);
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('tenant');
      } finally {
        setLoading(false);
      }
    };

    initializeAuth();
  }, []);

  const login = async (credentials) => {
    try {
      setError(null);
      const response = await authAPI.login(credentials);
      
      if (response.success) {
        const { token, user } = response;
        localStorage.setItem('token', token);
        setUser(user);
        return { success: true, user };
      } else {
        setError(response.message || 'Login failed');
        return { success: false, message: response.message };
      }
    } catch (error) {
      console.error('Login error:', error);
      const message = error.response?.data?.message || 'Login failed. Please try again.';
      setError(message);
      return { success: false, message };
    }
  };

  const superAdminLogin = async (credentials) => {
    try {
      setError(null);
      const response = await authAPI.superAdminLogin(credentials);
      
      if (response.success) {
        const { token, user } = response;
        localStorage.setItem('token', token);
        setUser(user);
        return { success: true, user };
      } else {
        setError(response.message || 'Super admin login failed');
        return { success: false, message: response.message };
      }
    } catch (error) {
      console.error('Super admin login error:', error);
      const message = error.response?.data?.message || 'Super admin login failed. Please try again.';
      setError(message);
      return { success: false, message };
    }
  };

  const register = async (userData) => {
    try {
      setError(null);
      const response = await authAPI.register(userData);
      
      if (response.success) {
        const { token, user } = response;
        localStorage.setItem('token', token);
        setUser(user);
        return { success: true, user };
      } else {
        setError(response.message || 'Registration failed');
        return { success: false, message: response.message };
      }
    } catch (error) {
      console.error('Registration error:', error);
      const message = error.response?.data?.message || 'Registration failed. Please try again.';
      setError(message);
      return { success: false, message };
    }
  };

  const logout = () => {
    localStorage.removeItem('token');
    setUser(null);
    setError(null);
  };

  const updateUser = (updatedUser) => {
    setUser(updatedUser);
  };

  const clearError = () => {
    setError(null);
  };

  const value = {
    user,
    loading,
    error,
    login,
    superAdminLogin,
    register,
    logout,
    updateUser,
    clearError,
    isAuthenticated: !!user,
    isSuperAdmin: user?.role === 'super_admin',
    isAdmin: user?.role === 'admin',
    isManager: user?.role === 'manager',
    isCashier: user?.role === 'cashier'
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
};
