import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { FiMail, FiLock, FiEye, FiEyeOff, FiUser, FiBuilding, FiArrowRight } from 'react-icons/fi'
import useAuthStore from '../../stores/authStore'
import LoadingSpinner from '../../components/ui/LoadingSpinner'
import Logo from '../../components/ui/Logo'

const RegisterPage = () => {
  const navigate = useNavigate()
  const { register: registerUser, isLoading } = useAuthStore()
  const [showPassword, setShowPassword] = useState(false)
  const [showConfirmPassword, setShowConfirmPassword] = useState(false)

  const {
    register,
    handleSubmit,
    watch,
    formState: { errors },
  } = useForm()

  const password = watch('password')

  const onSubmit = async (data) => {
    const result = await registerUser(data)
    if (result.success) {
      navigate('/app/dashboard')
    }
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex">
      {/* Left Side - Registration Form */}
      <div className="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
        <div className="max-w-md w-full space-y-8">
          {/* Header */}
          <div className="text-center">
            <div className="flex justify-center mb-6">
              <Logo size="large" />
            </div>
            <h2 className="text-3xl font-bold text-[#5D205D] mb-2">
              Create your business account
            </h2>
            <p className="text-[#746354] text-lg">
              Start your journey with Ardent POS
            </p>
          </div>

          {/* Registration Form */}
          <div className="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
            <form className="space-y-6" onSubmit={handleSubmit(onSubmit)}>
              {/* Business Name */}
              <div>
                <label htmlFor="business_name" className="block text-sm font-semibold text-[#2c2c2c] mb-2">
                  Business Name
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <FiBuilding className="h-5 w-5 text-[#746354]" />
                  </div>
                  <input
                    id="business_name"
                    type="text"
                    autoComplete="organization"
                    required
                    className="block w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl placeholder-[#746354] focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c]"
                    placeholder="Enter your business name"
                    {...register('business_name', {
                      required: 'Business name is required'
                    })}
                  />
                </div>
                {errors.business_name && (
                  <p className="mt-2 text-sm text-red-600 font-medium">{errors.business_name.message}</p>
                )}
              </div>

              {/* First Name */}
              <div>
                <label htmlFor="first_name" className="block text-sm font-semibold text-[#2c2c2c] mb-2">
                  First Name
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <FiUser className="h-5 w-5 text-[#746354]" />
                  </div>
                  <input
                    id="first_name"
                    type="text"
                    autoComplete="given-name"
                    required
                    className="block w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl placeholder-[#746354] focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c]"
                    placeholder="Enter your first name"
                    {...register('first_name', {
                      required: 'First name is required'
                    })}
                  />
                </div>
                {errors.first_name && (
                  <p className="mt-2 text-sm text-red-600 font-medium">{errors.first_name.message}</p>
                )}
              </div>

              {/* Last Name */}
              <div>
                <label htmlFor="last_name" className="block text-sm font-semibold text-[#2c2c2c] mb-2">
                  Last Name
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <FiUser className="h-5 w-5 text-[#746354]" />
                  </div>
                  <input
                    id="last_name"
                    type="text"
                    autoComplete="family-name"
                    required
                    className="block w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl placeholder-[#746354] focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c]"
                    placeholder="Enter your last name"
                    {...register('last_name', {
                      required: 'Last name is required'
                    })}
                  />
                </div>
                {errors.last_name && (
                  <p className="mt-2 text-sm text-red-600 font-medium">{errors.last_name.message}</p>
                )}
              </div>

              {/* Email */}
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
                    type="email"
                    autoComplete="email"
                    required
                    className="block w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl placeholder-[#746354] focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c]"
                    placeholder="Enter your email"
                    {...register('email', {
                      required: 'Email is required',
                      pattern: {
                        value: /^\S+@\S+$/i,
                        message: 'Invalid email address'
                      }
                    })}
                  />
                </div>
                {errors.email && (
                  <p className="mt-2 text-sm text-red-600 font-medium">{errors.email.message}</p>
                )}
              </div>

              {/* Password */}
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
                    type={showPassword ? 'text' : 'password'}
                    autoComplete="new-password"
                    required
                    className="block w-full pl-12 pr-12 py-3 border-2 border-gray-200 rounded-xl placeholder-[#746354] focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c]"
                    placeholder="Create a password"
                    {...register('password', {
                      required: 'Password is required',
                      minLength: {
                        value: 8,
                        message: 'Password must be at least 8 characters'
                      }
                    })}
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
                {errors.password && (
                  <p className="mt-2 text-sm text-red-600 font-medium">{errors.password.message}</p>
                )}
              </div>

              {/* Confirm Password */}
              <div>
                <label htmlFor="confirm_password" className="block text-sm font-semibold text-[#2c2c2c] mb-2">
                  Confirm Password
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <FiLock className="h-5 w-5 text-[#746354]" />
                  </div>
                  <input
                    id="confirm_password"
                    type={showConfirmPassword ? 'text' : 'password'}
                    autoComplete="new-password"
                    required
                    className="block w-full pl-12 pr-12 py-3 border-2 border-gray-200 rounded-xl placeholder-[#746354] focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c]"
                    placeholder="Confirm your password"
                    {...register('confirm_password', {
                      required: 'Please confirm your password',
                      validate: value => value === password || 'Passwords do not match'
                    })}
                  />
                  <button
                    type="button"
                    className="absolute inset-y-0 right-0 pr-4 flex items-center"
                    onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                  >
                    {showConfirmPassword ? (
                      <FiEyeOff className="h-5 w-5 text-[#746354] hover:text-[#2c2c2c] transition-colors" />
                    ) : (
                      <FiEye className="h-5 w-5 text-[#746354] hover:text-[#2c2c2c] transition-colors" />
                    )}
                  </button>
                </div>
                {errors.confirm_password && (
                  <p className="mt-2 text-sm text-red-600 font-medium">{errors.confirm_password.message}</p>
                )}
              </div>

              {/* Submit Button */}
              <div>
                <button
                  type="submit"
                  disabled={isLoading}
                  className="group relative w-full flex justify-center items-center py-3 px-4 border border-transparent text-base font-semibold rounded-xl text-white bg-gradient-to-r from-[#E72F7C] to-[#9a0864] hover:from-[#9a0864] hover:to-[#E72F7C] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#E72F7C] disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-[1.02] shadow-lg"
                >
                  {isLoading ? (
                    <div className="flex items-center">
                      <LoadingSpinner size="sm" />
                      <span className="ml-2">Creating account...</span>
                    </div>
                  ) : (
                    <div className="flex items-center">
                      Create Account
                      <FiArrowRight className="ml-2 h-5 w-5 group-hover:translate-x-1 transition-transform" />
                    </div>
                  )}
                </button>
              </div>

              {/* Links */}
              <div className="text-center">
                <p className="text-sm text-[#746354]">
                  Already have an account?{' '}
                  <Link
                    to="/auth/login"
                    className="font-semibold text-[#E72F7C] hover:text-[#9a0864] transition-colors"
                  >
                    Sign in
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
              Grow Your Business
            </h1>
            <p className="text-xl opacity-90 max-w-md">
              Join thousands of businesses using Ardent POS to manage sales, inventory, and customers efficiently.
            </p>
            
            {/* Features List */}
            <div className="mt-8 text-left max-w-sm mx-auto space-y-3">
              <div className="flex items-center">
                <div className="w-2 h-2 bg-white rounded-full mr-3"></div>
                <span className="opacity-90">Easy inventory management</span>
              </div>
              <div className="flex items-center">
                <div className="w-2 h-2 bg-white rounded-full mr-3"></div>
                <span className="opacity-90">Real-time sales tracking</span>
              </div>
              <div className="flex items-center">
                <div className="w-2 h-2 bg-white rounded-full mr-3"></div>
                <span className="opacity-90">Customer relationship tools</span>
              </div>
              <div className="flex items-center">
                <div className="w-2 h-2 bg-white rounded-full mr-3"></div>
                <span className="opacity-90">Comprehensive reporting</span>
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
  )
}

export default RegisterPage
