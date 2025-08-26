import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { FiMail, FiLock, FiEye, FiEyeOff, FiArrowRight } from 'react-icons/fi';
import useAuthStore from '../../stores/authStore';
import Logo from '../../components/ui/Logo';

const LoginPage = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  
  const { login, error, clearError } = useAuthStore();
  const navigate = useNavigate();

  useEffect(() => {
    clearError();
  }, [clearError]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsLoading(true);
    
    try {
      console.log('Attempting login with:', { email, password: '***' });
      const result = await login(email, password);
      console.log('Login result:', result);
      
      // Check if user is super admin and redirect accordingly
      if (result.user?.role === 'super_admin') {
        console.log('Super admin detected, redirecting to super admin dashboard');
        navigate('/super-admin/dashboard');
      } else {
        console.log('Regular user, redirecting to app dashboard');
        navigate('/app/dashboard');
      }
    } catch (error) {
      console.error('Login failed:', error);
      console.error('Error details:', {
        message: error.message,
        response: error.response?.data,
        status: error.response?.status
      });
    } finally {
      setIsLoading(false);
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
              <Logo size="large" />
            </div>
            <h2 className="text-3xl font-bold text-[#5D205D] mb-2">
              Welcome back
            </h2>
            <p className="text-[#746354] text-lg">
              Sign in to your Ardent POS account
            </p>
            <div className="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
              <p className="text-sm text-blue-700">
                <strong>Super Admin?</strong> Please use the{' '}
                <Link to="/auth/super-admin" className="text-blue-800 underline hover:text-blue-900">
                  Super Admin Login
                </Link>{' '}
                instead.
              </p>
            </div>
          </div>

          {/* Login Form */}
          <div className="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
            <form className="space-y-6" onSubmit={handleSubmit}>
              {/* Email Field */}
              <div>
                <label htmlFor="email" className="block text-sm font-semibold text-[#2c2c2c] mb-2">
                  Email address
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <FiMail className="h-5 w-5 text-[#746354]" />
                  </div>
                  <input
                    id="email"
                    name="email"
                    type="email"
                    autoComplete="email"
                    required
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    className="block w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl placeholder-[#746354] focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c]"
                    placeholder="Enter your email"
                  />
                </div>
              </div>

              {/* Password Field */}
              <div>
                <label htmlFor="password" className="block text-sm font-semibold text-[#2c2c2c] mb-2">
                  Password
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <FiLock className="h-5 w-5 text-[#746354]" />
                  </div>
                  <input
                    id="password"
                    name="password"
                    type={showPassword ? 'text' : 'password'}
                    autoComplete="current-password"
                    required
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    className="block w-full pl-12 pr-12 py-3 border-2 border-gray-200 rounded-xl placeholder-[#746354] focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c]"
                    placeholder="Enter your password"
                  />
                  <button
                    type="button"
                    className="absolute inset-y-0 right-0 pr-4 flex items-center"
                    onClick={() => setShowPassword(!showPassword)}
                  >
                    {showPassword ? (
                      <FiEyeOff className="h-5 w-5 text-[#746354] hover:text-[#2c2c2c] transition-colors" />
                    ) : (
                      <FiEye className="h-5 w-5 text-[#746354] hover:text-[#2c2c2c] transition-colors" />
                    )}
                  </button>
                </div>
              </div>

              {/* Error Message */}
              {error && (
                <div className="bg-red-50 border-2 border-red-200 rounded-xl p-4">
                  <div className="flex">
                    <div className="flex-shrink-0">
                      <svg className="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                      </svg>
                    </div>
                    <div className="ml-3">
                      <p className="text-sm text-red-800 font-medium">{error}</p>
                    </div>
                  </div>
                </div>
              )}

              {/* Submit Button */}
              <div>
                <button
                  type="submit"
                  disabled={isLoading}
                  className="group relative w-full flex justify-center items-center py-3 px-4 border border-transparent text-base font-semibold rounded-xl text-white bg-gradient-to-r from-[#E72F7C] to-[#9a0864] hover:from-[#9a0864] hover:to-[#E72F7C] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#E72F7C] disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-[1.02] shadow-lg"
                >
                  {isLoading ? (
                    <div className="flex items-center">
                      <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Signing in...
                    </div>
                  ) : (
                    <div className="flex items-center">
                      Sign in
                      <FiArrowRight className="ml-2 h-5 w-5 group-hover:translate-x-1 transition-transform" />
                    </div>
                  )}
                </button>
              </div>

              {/* Links */}
              <div className="text-center space-y-4">
                <p className="text-sm text-[#746354]">
                  Don't have an account?{' '}
                  <Link to="/auth/register" className="font-semibold text-[#E72F7C] hover:text-[#9a0864] transition-colors">
                    Sign up
                  </Link>
                </p>
                <p className="text-sm text-[#746354]">
                  <Link to="/auth/forgot-password" className="font-semibold text-[#E72F7C] hover:text-[#9a0864] transition-colors">
                    Forgot your password?
                  </Link>
                </p>
              </div>
            </form>
          </div>
        </div>
      </div>

      {/* Right Side - Decorative */}
      <div className="hidden lg:flex lg:flex-1 bg-gradient-to-br from-[#E72F7C] to-[#9a0864] relative overflow-hidden">
        <div className="absolute inset-0 bg-black opacity-10"></div>
        <div className="relative z-10 flex items-center justify-center w-full">
          <div className="text-center text-white px-8">
            <div className="mb-8">
              <Logo size="xl" className="text-white" />
            </div>
            <h1 className="text-4xl font-bold mb-4">
              Streamline Your Business
            </h1>
            <p className="text-xl opacity-90 max-w-md">
              Powerful point of sale system designed to help your business grow and succeed.
            </p>
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

export default LoginPage;
