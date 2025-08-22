import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { FiShield, FiEye, FiEyeOff, FiAlertCircle, FiLoader } from 'react-icons/fi';
import useAuthStore from '../../stores/authStore';

const SuperAdminLoginPage = () => {
  const navigate = useNavigate();
  const { error, setError } = useAuthStore();
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
    if (error) setError(null);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      const response = await fetch('/auth/super-admin-login.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
      });

      const data = await response.json();

             if (data.success) {
         // Store token and user data
         localStorage.setItem('token', data.token);
         localStorage.setItem('user', JSON.stringify(data.user));
         localStorage.setItem('tenant', JSON.stringify(data.tenant));
         
         // Redirect to super admin dashboard
         navigate('/app/super-admin');
       } else {
        setError(data.error || 'Super admin login failed');
      }
         } catch (err) {
       console.error('Super admin login error:', err);
       setError('Network error. Please try again.');
     } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-[#e41e5b]/5 via-[#9a0864]/5 to-[#a67c00]/5 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-8">
        {/* Header */}
        <div className="text-center">
          <div className="mx-auto h-16 w-16 bg-gradient-to-br from-[#e41e5b] to-[#9a0864] rounded-2xl flex items-center justify-center shadow-lg">
            <FiShield className="h-8 w-8 text-white" />
          </div>
          <h2 className="mt-6 text-3xl font-bold text-[#2c2c2c]">
            Super Admin Access
          </h2>
          <p className="mt-2 text-sm text-[#746354]">
            Secure system administration portal
          </p>
        </div>

        {/* Login Form */}
        <div className="bg-white rounded-2xl shadow-xl border border-[#746354]/10 p-8">
          <form className="space-y-6" onSubmit={handleSubmit}>
            {/* Error Display */}
            {error && (
              <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                <div className="flex items-center">
                  <FiAlertCircle className="h-5 w-5 text-red-500 mr-2" />
                  <span className="text-red-800 text-sm">{error}</span>
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
                className="w-full px-4 py-3 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b] transition-colors"
                placeholder="admin@company.com"
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
                  className="w-full px-4 py-3 pr-12 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b] transition-colors"
                  placeholder="Enter your password"
                  disabled={loading}
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute inset-y-0 right-0 pr-3 flex items-center text-[#746354] hover:text-[#2c2c2c] transition-colors"
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
              className="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-gradient-to-r from-[#e41e5b] to-[#9a0864] hover:from-[#9a0864] hover:to-[#e41e5b] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#e41e5b] transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {loading ? (
                <>
                  <FiLoader className="animate-spin -ml-1 mr-3 h-5 w-5" />
                  Authenticating...
                </>
              ) : (
                'Access Super Admin Portal'
              )}
            </button>
          </form>

          {/* Security Notice */}
          <div className="mt-6 p-4 bg-[#a67c00]/5 border border-[#a67c00]/20 rounded-lg">
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
            className="text-sm text-[#e41e5b] hover:text-[#9a0864] font-medium transition-colors"
          >
            ← Back to Business Login
          </Link>
        </div>
      </div>
    </div>
  );
};

export default SuperAdminLoginPage;
