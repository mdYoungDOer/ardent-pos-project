import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { FiShield, FiEye, FiEyeOff, FiAlertCircle, FiLoader, FiArrowRight } from 'react-icons/fi';
import useSuperAdminAuthStore from '../../stores/superAdminAuthStore';
import Logo from '../../components/ui/Logo';

const SuperAdminLoginPage = () => {
  const navigate = useNavigate();
  const { error, login, clearError } = useSuperAdminAuthStore();
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
      
      // Use the Super Admin auth store login method
      await login(formData.email, formData.password);
      
      console.log('Super admin login successful, redirecting...');
      
      // Redirect to super admin dashboard
      navigate('/super-admin/dashboard');
      
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
                  disabled={loading}
                />
              </div>

              {/* Password Field */}
              <div>
                <label htmlFor="password" className="block text-sm font-semibold text-[#2c2c2c] mb-2">
                  Password
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
                    disabled={loading}
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute inset-y-0 right-0 pr-4 flex items-center text-[#746354] hover:text-[#2c2c2c] transition-colors"
                    disabled={loading}
                  >
                    {showPassword ? <FiEyeOff className="h-5 w-5" /> : <FiEye className="h-5 w-5" />}
                  </button>
                </div>
              </div>

              {/* Submit Button */}
              <button
                type="submit"
                disabled={loading}
                className="group w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl shadow-lg text-base font-semibold text-white bg-gradient-to-r from-[#E72F7C] to-[#9a0864] hover:from-[#9a0864] hover:to-[#E72F7C] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#E72F7C] transition-all duration-200 transform hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {loading ? (
                  <>
                    <FiLoader className="animate-spin -ml-1 mr-3 h-5 w-5" />
                    Authenticating...
                  </>
                ) : (
                  <div className="flex items-center">
                    Access Super Admin Portal
                    <FiArrowRight className="ml-2 h-5 w-5 group-hover:translate-x-1 transition-transform" />
                  </div>
                )}
              </button>
            </form>

            {/* Security Notice */}
            <div className="mt-6 p-4 bg-[#a67c00]/5 border-2 border-[#a67c00]/20 rounded-xl">
              <div className="flex items-start">
                <FiShield className="h-5 w-5 text-[#a67c00] mt-0.5 mr-3 flex-shrink-0" />
                <div className="text-sm">
                  <p className="font-semibold text-[#2c2c2c] mb-1">Security Notice</p>
                  <p className="text-[#746354]">
                    This portal provides access to system-wide administration. 
                    All activities are logged and monitored for security purposes.
                  </p>
                </div>
              </div>
            </div>


          </div>

          {/* Footer Links */}
          <div className="text-center">
            <Link 
              to="/auth/login" 
              className="inline-flex items-center text-sm text-[#E72F7C] hover:text-[#9a0864] font-semibold transition-colors"
            >
              ← Back to Business Login
            </Link>
          </div>
        </div>
      </div>

      {/* Right Side - Decorative */}
      <div className="hidden lg:flex lg:flex-1 bg-gradient-to-br from-[#E72F7C] to-[#9a0864] relative overflow-hidden">
        <div className="absolute inset-0 bg-black opacity-10"></div>
        <div className="relative z-10 flex items-center justify-center w-full">
          <div className="text-center text-white px-8">
            <div className="mb-8">
              <div className="relative inline-block">
                <Logo size="xl" className="text-white" />
                <div className="absolute -top-2 -right-2 bg-white rounded-full p-2">
                  <FiShield className="h-6 w-6 text-[#E72F7C]" />
                </div>
              </div>
            </div>
            <h1 className="text-4xl font-bold mb-4">
              System Administration
            </h1>
            <p className="text-xl opacity-90 max-w-md">
              Manage the entire Ardent POS ecosystem with powerful administrative tools and comprehensive oversight.
            </p>
            
            {/* Admin Features */}
            <div className="mt-8 text-left max-w-sm mx-auto space-y-3">
              <div className="flex items-center">
                <div className="w-2 h-2 bg-white rounded-full mr-3"></div>
                <span className="opacity-90">Tenant management</span>
              </div>
              <div className="flex items-center">
                <div className="w-2 h-2 bg-white rounded-full mr-3"></div>
                <span className="opacity-90">System monitoring</span>
              </div>
              <div className="flex items-center">
                <div className="w-2 h-2 bg-white rounded-full mr-3"></div>
                <span className="opacity-90">User administration</span>
              </div>
              <div className="flex items-center">
                <div className="w-2 h-2 bg-white rounded-full mr-3"></div>
                <span className="opacity-90">Security controls</span>
              </div>
            </div>
          </div>
        </div>
        
        {/* Decorative Elements */}
        <div className="absolute top-10 right-10 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div className="absolute bottom-20 left-20 w-24 h-24 bg-white opacity-10 rounded-full"></div>
        <div className="absolute top-1/2 right-1/4 w-16 h-16 bg-white opacity-10 rounded-full"></div>
      </div>
    </div>
  );
};

export default SuperAdminLoginPage;
