import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { FiShield, FiEye, FiEyeOff, FiAlertCircle, FiLoader, FiArrowRight } from 'react-icons/fi';
import { useAuth } from '../../contexts/AuthContext';
import Logo from '../../components/ui/Logo';

const SuperAdminLoginPage = () => {
  const navigate = useNavigate();
  const { error, login, clearError } = useAuth();
  const [formData, setFormData] = useState({
    email: '',
    password: ''
  });
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
    if (error) clearError();
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      console.log('=== SUPER ADMIN LOGIN DEBUG START ===');
      console.log('Attempting super admin login with:', formData.email);
      
      // Use the Auth context login method
      const result = await login(formData);
      
      console.log('Super admin login result:', result);
      
      if (result.success) {
        // Verify this is actually a super admin
        if (result.user?.role === 'super_admin') {
          console.log('Super admin login successful, redirecting...');
          navigate('/super-admin/dashboard');
        } else {
          console.error('User is not a super admin');
          // This should be handled by the backend, but just in case
          throw new Error('Access denied. Super admin privileges required.');
        }
      } else {
        console.error('Super admin login failed:', result.message);
        throw new Error(result.message || 'Login failed');
      }
      
      console.log('=== SUPER ADMIN LOGIN DEBUG END ===');
    } catch (err) {
      console.error('=== SUPER ADMIN LOGIN ERROR ===');
      console.error('Error:', err.message);
      console.error('=== END ERROR ===');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex">
      {/* Left Side - Login Form */}
      <div className="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div className="max-w-md w-full space-y-8">
          {/* Header */}
          <div className="text-center">
            <div className="flex justify-center mb-6">
              <div className="relative">
                <Logo size="large" />
                <div className="absolute -top-2 -right-2 bg-[#E72F7C] rounded-full p-1">
                  <FiShield className="h-4 w-4 text-white" />
                </div>
              </div>
            </div>
            <h2 className="text-3xl font-bold text-[#5D205D] mb-2">
              Super Admin Access
            </h2>
            <p className="text-[#746354] text-lg">
              Secure system administration portal
            </p>
          </div>

          {/* Login Form */}
          <div className="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
            <form className="space-y-6" onSubmit={handleSubmit}>
              {/* Error Display */}
              {error && (
                <div className="bg-red-50 border-2 border-red-200 rounded-xl p-4">
                  <div className="flex items-center">
                    <FiAlertCircle className="h-5 w-5 text-red-500 mr-2" />
                    <span className="text-red-800 text-sm font-medium">{error}</span>
                  </div>
                </div>
              )}

              {/* Email Field */}
              <div>
                <label htmlFor="email" className="block text-sm font-semibold text-[#2c2c2c] mb-2">
                  Super Admin Email
                </label>
                <input
                  id="email"
                  name="email"
                  type="email"
                  required
                  value={formData.email}
                  onChange={handleChange}
                  className="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c]"
                  placeholder="deyoungdoer@gmail.com"
                />
              </div>

              {/* Password Field */}
              <div>
                <label htmlFor="password" className="block text-sm font-semibold text-[#2c2c2c] mb-2">
                  Super Admin Password
                </label>
                <div className="relative">
                  <input
                    id="password"
                    name="password"
                    type={showPassword ? 'text' : 'password'}
                    required
                    value={formData.password}
                    onChange={handleChange}
                    className="w-full px-4 py-3 pr-12 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c]"
                    placeholder="Enter your password"
                  />
                  <button
                    type="button"
                    className="absolute inset-y-0 right-0 pr-4 flex items-center"
                    onClick={() => setShowPassword(!showPassword)}
                  >
                    {showPassword ? (
                      <FiEyeOff className="h-5 w-5 text-[#746354] hover:text-[#2c2c2c]" />
                    ) : (
                      <FiEye className="h-5 w-5 text-[#746354] hover:text-[#2c2c2c]" />
                    )}
                  </button>
                </div>
              </div>

              {/* Submit Button */}
              <button
                type="submit"
                disabled={loading}
                className="w-full bg-gradient-to-r from-[#E72F7C] to-[#9a0864] text-white py-3 px-4 rounded-xl font-semibold hover:from-[#d61f6b] hover:to-[#8a0759] focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
              >
                {loading ? (
                  <>
                    <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                    Authenticating...
                  </>
                ) : (
                  <>
                    Access System
                    <FiArrowRight className="ml-2 h-5 w-5" />
                  </>
                )}
              </button>
            </form>

            {/* Links */}
            <div className="mt-6 text-center">
              <p className="text-[#746354] text-sm">
                Regular user?{' '}
                <Link
                  to="/auth/login"
                  className="font-semibold text-[#E72F7C] hover:text-[#9a0864] transition-colors duration-200"
                >
                  Sign in here
                </Link>
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Right Side - Background Image/Pattern */}
      <div className="hidden lg:flex lg:flex-1 bg-gradient-to-br from-[#E72F7C] to-[#9a0864] relative overflow-hidden">
        <div className="absolute inset-0 bg-black opacity-10"></div>
        <div className="relative z-10 flex items-center justify-center w-full">
          <div className="text-center text-white px-8">
            <div className="mb-6">
              <FiShield className="h-16 w-16 mx-auto mb-4 text-white opacity-90" />
            </div>
            <h1 className="text-4xl font-bold mb-4">System Administration</h1>
            <p className="text-xl opacity-90">
              Secure access to platform management and configuration
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SuperAdminLoginPage;
