import React, { useState, useEffect } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { FiEye, FiEyeOff, FiCheck, FiArrowLeft, FiUser, FiMail, FiLock, FiBriefcase, FiPhone, FiMapPin, FiAlertCircle, FiArrowRight } from 'react-icons/fi';
import { useAuth } from '../../contexts/AuthContext';
import Logo from '../../components/ui/Logo';

const RegisterPage = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { register, error, clearError } = useAuth();
  
  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    confirm_password: '',
    business_name: '',
    business_type: '',
    phone: '',
    address: '',
    city: '',
    country: 'Ghana'
  });
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [selectedPlan, setSelectedPlan] = useState(null);
  const [billingCycle, setBillingCycle] = useState('monthly');
  const [acceptTerms, setAcceptTerms] = useState(false);

  useEffect(() => {
    clearError();
    // Check if plan was selected from landing page
    const storedPlan = localStorage.getItem('selectedPlan');
    if (storedPlan) {
      const planData = JSON.parse(storedPlan);
      setSelectedPlan(planData.plan);
      setBillingCycle(planData.billingCycle);
    } else if (location.state?.selectedPlan) {
      setSelectedPlan(location.state.selectedPlan);
      setBillingCycle(location.state.billingCycle || 'monthly');
    }
  }, [location.state, clearError]);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    // Validation
    if (formData.password !== formData.confirm_password) {
      clearError();
      return;
    }

    if (formData.password.length < 8) {
      clearError();
      return;
    }

    if (!acceptTerms) {
      clearError();
      return;
    }

    try {
      const registrationData = {
        ...formData,
        selected_plan: selectedPlan?.plan_id || 'starter',
        billing_cycle: billingCycle
      };

      const result = await register(registrationData);
      
    if (result.success) {
        // Clear stored plan data
        localStorage.removeItem('selectedPlan');
        
        // Redirect to onboarding or dashboard
        if (result.user.role === 'super_admin') {
          navigate('/super-admin/dashboard');
        } else {
          navigate('/app/dashboard');
        }
      }
    } catch (error) {
      console.error('Registration error:', error);
    } finally {
      setLoading(false);
    }
  };

  const businessTypes = [
    'Restaurant',
    'Retail Store',
    'Supermarket',
    'Pharmacy',
    'Electronics Store',
    'Fashion Boutique',
    'Hardware Store',
    'Beauty Salon',
    'Barber Shop',
    'Coffee Shop',
    'Bakery',
    'Other'
  ];

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex">
      {/* Left Side - Registration Form */}
      <div className="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
        <div className="max-w-2xl w-full space-y-8">
          {/* Header */}
          <div className="text-center">
            <div className="flex justify-center mb-6">
              <Logo size="large" />
            </div>
            <h2 className="text-3xl font-bold text-[#5D205D] mb-2">
              Create Your Account
            </h2>
            <p className="text-[#746354] text-lg">
              Join thousands of businesses using Ardent POS
        </p>
      </div>

          {/* Selected Plan Display */}
          {selectedPlan && (
            <div className="bg-gradient-to-r from-[#E72F7C] to-[#9a0864] rounded-2xl p-6 text-white">
              <div className="flex items-center justify-between">
                <div>
                  <h3 className="text-xl font-bold mb-1">{selectedPlan.name} Plan</h3>
                  <p className="opacity-90">Billing: {billingCycle === 'monthly' ? 'Monthly' : 'Yearly'}</p>
                </div>
                <div className="text-right">
                  <div className="text-2xl font-bold">
                    ₵{billingCycle === 'monthly' ? selectedPlan.monthly_price : selectedPlan.yearly_price}
                  </div>
                  <div className="text-sm opacity-90">per {billingCycle === 'monthly' ? 'month' : 'year'}</div>
                </div>
              </div>
            </div>
          )}

          {/* Registration Form */}
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

              {/* Personal Information */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                      name="first_name"
              type="text"
                      required
                      value={formData.first_name}
                      onChange={handleInputChange}
                      className="block w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl placeholder-[#746354] focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c]"
                      placeholder="Enter your first name"
                    />
                  </div>
          </div>

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
                      name="last_name"
              type="text"
                      required
                      value={formData.last_name}
                      onChange={handleInputChange}
                      className="block w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl placeholder-[#746354] focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c]"
                      placeholder="Enter your last name"
                    />
                  </div>
          </div>
        </div>

              {/* Email */}
        <div>
                <label htmlFor="email" className="block text-sm font-semibold text-[#2c2c2c] mb-2">
                  Email Address
          </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <FiMail className="h-5 w-5 text-[#746354]" />
        </div>
          <input
            id="email"
                    name="email"
            type="email"
                    required
                    value={formData.email}
                    onChange={handleInputChange}
                    className="block w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl placeholder-[#746354] focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c]"
                    placeholder="Enter your email address"
                  />
                </div>
        </div>

              {/* Passwords */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                      required
                      value={formData.password}
                      onChange={handleInputChange}
                      className="block w-full pl-12 pr-12 py-3 border-2 border-gray-200 rounded-xl placeholder-[#746354] focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c]"
                      placeholder="Create a password"
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
                      name="confirm_password"
                      type={showConfirmPassword ? 'text' : 'password'}
                      required
                      value={formData.confirm_password}
                      onChange={handleInputChange}
                      className="block w-full pl-12 pr-12 py-3 border-2 border-gray-200 rounded-xl placeholder-[#746354] focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c]"
                      placeholder="Confirm your password"
                    />
                    <button
                      type="button"
                      className="absolute inset-y-0 right-0 pr-4 flex items-center"
                      onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                    >
                      {showConfirmPassword ? (
                        <FiEyeOff className="h-5 w-5 text-[#746354] hover:text-[#2c2c2c]" />
                      ) : (
                        <FiEye className="h-5 w-5 text-[#746354] hover:text-[#2c2c2c]" />
                      )}
                    </button>
                  </div>
                </div>
              </div>

              {/* Business Information */}
              <div className="border-t border-gray-200 pt-6">
                <h3 className="text-lg font-semibold text-[#2c2c2c] mb-4">Business Information</h3>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label htmlFor="business_name" className="block text-sm font-semibold text-[#2c2c2c] mb-2">
                      Business Name
                    </label>
                    <div className="relative">
                      <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <FiBriefcase className="h-5 w-5 text-[#746354]" />
                      </div>
                      <input
                        id="business_name"
                        name="business_name"
                        type="text"
                        required
                        value={formData.business_name}
                        onChange={handleInputChange}
                        className="block w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl placeholder-[#746354] focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c]"
                        placeholder="Enter your business name"
                      />
                    </div>
                  </div>

                  <div>
                    <label htmlFor="business_type" className="block text-sm font-semibold text-[#2c2c2c] mb-2">
                      Business Type
                    </label>
                    <select
                      id="business_type"
                      name="business_type"
                      required
                      value={formData.business_type}
                      onChange={handleInputChange}
                      className="block w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c] bg-white"
                    >
                      <option value="">Select business type</option>
                      {businessTypes.map((type) => (
                        <option key={type} value={type}>{type}</option>
                      ))}
                    </select>
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                  <div>
                    <label htmlFor="phone" className="block text-sm font-semibold text-[#2c2c2c] mb-2">
                      Phone Number
                    </label>
                    <div className="relative">
                      <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <FiPhone className="h-5 w-5 text-[#746354]" />
                      </div>
                      <input
                        id="phone"
                        name="phone"
                        type="tel"
                        required
                        value={formData.phone}
                        onChange={handleInputChange}
                        className="block w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl placeholder-[#746354] focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c]"
                        placeholder="Enter your phone number"
                      />
                    </div>
                  </div>

                  <div>
                    <label htmlFor="country" className="block text-sm font-semibold text-[#2c2c2c] mb-2">
                      Country
                    </label>
                    <select
                      id="country"
                      name="country"
                      required
                      value={formData.country}
                      onChange={handleInputChange}
                      className="block w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c] bg-white"
                    >
                      <option value="Ghana">Ghana</option>
                      <option value="Nigeria">Nigeria</option>
                      <option value="Kenya">Kenya</option>
                      <option value="South Africa">South Africa</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                  <div>
                    <label htmlFor="city" className="block text-sm font-semibold text-[#2c2c2c] mb-2">
                      City
                    </label>
                    <div className="relative">
                      <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <FiMapPin className="h-5 w-5 text-[#746354]" />
                      </div>
                      <input
                        id="city"
                        name="city"
                        type="text"
                        required
                        value={formData.city}
                        onChange={handleInputChange}
                        className="block w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl placeholder-[#746354] focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c]"
                        placeholder="Enter your city"
                      />
                    </div>
        </div>

        <div>
                    <label htmlFor="address" className="block text-sm font-semibold text-[#2c2c2c] mb-2">
                      Address
          </label>
                    <div className="relative">
                      <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <FiMapPin className="h-5 w-5 text-[#746354]" />
                      </div>
          <input
                        id="address"
                        name="address"
                        type="text"
                        required
                        value={formData.address}
                        onChange={handleInputChange}
                        className="block w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl placeholder-[#746354] focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:border-[#E72F7C] transition-all duration-200 text-[#2c2c2c]"
                        placeholder="Enter your address"
                      />
                    </div>
                  </div>
                </div>
        </div>

              {/* Terms and Conditions */}
              <div className="mt-6">
                <div className="flex items-start">
                  <div className="flex items-center h-5">
          <input
                      id="accept-terms"
                      name="accept-terms"
            type="checkbox"
                      checked={acceptTerms}
                      onChange={(e) => setAcceptTerms(e.target.checked)}
                      className="h-4 w-4 text-[#E72F7C] focus:ring-[#E72F7C] border-gray-300 rounded"
                    />
                  </div>
                  <div className="ml-3 text-sm">
                    <label htmlFor="accept-terms" className="text-[#746354]">
            I agree to the{' '}
                      <Link
                        to="/terms-of-use"
                        target="_blank"
                        className="text-[#E72F7C] hover:text-[#9a0864] underline"
                      >
                        Terms of Use
                      </Link>
                      {' '}and{' '}
                      <Link
                        to="/privacy-policy"
                        target="_blank"
                        className="text-[#E72F7C] hover:text-[#9a0864] underline"
                      >
              Privacy Policy
                      </Link>
          </label>
        </div>
                </div>
              </div>

              {/* Submit Button */}
          <button
            type="submit"
                disabled={loading || !acceptTerms}
                className="w-full bg-gradient-to-r from-[#E72F7C] to-[#9a0864] text-white py-3 px-4 rounded-xl font-semibold hover:from-[#d61f6b] hover:to-[#8a0759] focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
              >
                {loading ? (
                  <>
                    <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                    Creating Account...
                  </>
                ) : (
                  <>
                    Create Account
                    <FiArrowRight className="ml-2 h-5 w-5" />
                  </>
            )}
          </button>
            </form>

            {/* Links */}
            <div className="mt-6 text-center">
              <p className="text-[#746354] text-sm">
                Already have an account?{' '}
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
            <h1 className="text-4xl font-bold mb-4">Join Ardent POS</h1>
            <p className="text-xl opacity-90 mb-8">
              Start your business journey with our powerful POS solution
            </p>
            
            {/* Features */}
            <div className="text-left max-w-sm mx-auto space-y-4">
              <div className="flex items-center">
                <FiCheck className="h-5 w-5 text-white mr-3" />
                <span className="opacity-90">Easy setup and onboarding</span>
              </div>
              <div className="flex items-center">
                <FiCheck className="h-5 w-5 text-white mr-3" />
                <span className="opacity-90">24/7 customer support</span>
              </div>
              <div className="flex items-center">
                <FiCheck className="h-5 w-5 text-white mr-3" />
                <span className="opacity-90">Free trial available</span>
              </div>
              <div className="flex items-center">
                <FiCheck className="h-5 w-5 text-white mr-3" />
                <span className="opacity-90">No setup fees</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default RegisterPage;
