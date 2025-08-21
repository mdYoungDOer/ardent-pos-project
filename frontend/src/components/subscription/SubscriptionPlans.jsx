import React, { useState } from 'react';
import { toast } from 'react-hot-toast';
import { CheckIcon, XMarkIcon } from '@heroicons/react/24/outline';
import LoadingSpinner from '../ui/LoadingSpinner';
import paystackService from '../../services/paystack';

const plans = [
  {
    id: 'starter',
    name: 'Starter',
    description: 'Perfect for small businesses just getting started',
    monthly: 120,
    yearly: 1200,
    features: [
      'Up to 100 products',
      'Up to 2 users',
      'Basic reporting',
      'Email support',
      'Mobile app access'
    ],
    limitations: [
      'Limited integrations',
      'Basic customization'
    ]
  },
  {
    id: 'professional',
    name: 'Professional',
    description: 'Ideal for growing businesses with advanced needs',
    monthly: 240,
    yearly: 2400,
    popular: true,
    features: [
      'Up to 1,000 products',
      'Up to 10 users',
      'Advanced reporting & analytics',
      'Priority email support',
      'Mobile app access',
      'Inventory management',
      'Customer management',
      'Multi-location support'
    ],
    limitations: [
      'Limited API calls'
    ]
  },
  {
    id: 'enterprise',
    name: 'Enterprise',
    description: 'For large businesses requiring maximum flexibility',
    monthly: 480,
    yearly: 4800,
    features: [
      'Unlimited products',
      'Unlimited users',
      'Advanced reporting & analytics',
      'Phone & email support',
      'Mobile app access',
      'Full inventory management',
      'Advanced customer management',
      'Multi-location support',
      'API access',
      'Custom integrations',
      'White-label options'
    ],
    limitations: []
  }
];

const SubscriptionPlans = ({ currentPlan, onPlanSelect }) => {
  const [billingCycle, setBillingCycle] = useState('monthly');
  const [loading, setLoading] = useState(false);
  const [selectedPlan, setSelectedPlan] = useState(null);

  const handlePlanSelect = async (plan) => {
    if (loading) return;
    
    setLoading(true);
    setSelectedPlan(plan.id);

    try {
      const subscriptionData = {
        plan: plan.id,
        billing_cycle: billingCycle
      };

      const result = await paystackService.initializeSubscription(subscriptionData);
      
      if (result.status === 'success') {
        toast.success('Subscription upgraded successfully!');
        if (onPlanSelect) {
          onPlanSelect(plan, billingCycle);
        }
      }
    } catch (error) {
      console.error('Subscription error:', error);
      toast.error(error.message || 'Failed to upgrade subscription');
    } finally {
      setLoading(false);
      setSelectedPlan(null);
    }
  };

  const formatPrice = (price) => {
    return new Intl.NumberFormat('en-GH', {
      style: 'currency',
      currency: 'GHS'
    }).format(price);
  };

  const getMonthlyPrice = (plan) => {
    return billingCycle === 'yearly' ? plan.yearly / 12 : plan.monthly;
  };

  const getSavings = (plan) => {
    if (billingCycle === 'yearly') {
      const yearlyMonthly = plan.yearly / 12;
      const savings = ((plan.monthly - yearlyMonthly) / plan.monthly) * 100;
      return Math.round(savings);
    }
    return 0;
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      {/* Billing Toggle */}
      <div className="flex justify-center mb-8">
        <div className="bg-gray-100 p-1 rounded-lg">
          <button
            onClick={() => setBillingCycle('monthly')}
            className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${
              billingCycle === 'monthly'
                ? 'bg-white text-primary shadow-sm'
                : 'text-gray-600 hover:text-gray-900'
            }`}
          >
            Monthly
          </button>
          <button
            onClick={() => setBillingCycle('yearly')}
            className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${
              billingCycle === 'yearly'
                ? 'bg-white text-primary shadow-sm'
                : 'text-gray-600 hover:text-gray-900'
            }`}
          >
            Yearly
            <span className="ml-1 text-xs text-green-600 font-semibold">Save up to 17%</span>
          </button>
        </div>
      </div>

      {/* Plans Grid */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
        {plans.map((plan) => {
          const isCurrentPlan = currentPlan === plan.id;
          const monthlyPrice = getMonthlyPrice(plan);
          const savings = getSavings(plan);
          const isLoading = loading && selectedPlan === plan.id;

          return (
            <div
              key={plan.id}
              className={`relative bg-white rounded-2xl shadow-lg border-2 transition-all duration-200 ${
                plan.popular
                  ? 'border-primary ring-2 ring-primary ring-opacity-20'
                  : 'border-gray-200 hover:border-gray-300'
              } ${isCurrentPlan ? 'ring-2 ring-green-500 ring-opacity-50' : ''}`}
            >
              {plan.popular && (
                <div className="absolute -top-4 left-1/2 transform -translate-x-1/2">
                  <span className="bg-primary text-white px-4 py-1 rounded-full text-sm font-medium">
                    Most Popular
                  </span>
                </div>
              )}

              {isCurrentPlan && (
                <div className="absolute -top-4 right-4">
                  <span className="bg-green-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                    Current Plan
                  </span>
                </div>
              )}

              <div className="p-8">
                <h3 className="text-2xl font-bold text-gray-900 mb-2">{plan.name}</h3>
                <p className="text-gray-600 mb-6">{plan.description}</p>

                <div className="mb-6">
                  <div className="flex items-baseline">
                    <span className="text-4xl font-bold text-gray-900">
                      {formatPrice(monthlyPrice)}
                    </span>
                    <span className="text-gray-600 ml-2">/month</span>
                  </div>
                  {billingCycle === 'yearly' && (
                    <div className="mt-2">
                      <span className="text-sm text-gray-600">
                        Billed annually ({formatPrice(plan.yearly)})
                      </span>
                      {savings > 0 && (
                        <span className="ml-2 text-sm text-green-600 font-medium">
                          Save {savings}%
                        </span>
                      )}
                    </div>
                  )}
                </div>

                <button
                  onClick={() => handlePlanSelect(plan)}
                  disabled={isCurrentPlan || loading}
                  className={`w-full py-3 px-4 rounded-lg font-medium transition-colors mb-6 ${
                    isCurrentPlan
                      ? 'bg-gray-100 text-gray-500 cursor-not-allowed'
                      : plan.popular
                      ? 'bg-primary text-white hover:bg-primary-dark'
                      : 'bg-gray-900 text-white hover:bg-gray-800'
                  } ${loading ? 'opacity-50 cursor-not-allowed' : ''}`}
                >
                  {isLoading ? (
                    <LoadingSpinner size="sm" />
                  ) : isCurrentPlan ? (
                    'Current Plan'
                  ) : (
                    'Choose Plan'
                  )}
                </button>

                <div className="space-y-4">
                  <h4 className="font-semibold text-gray-900">Features included:</h4>
                  <ul className="space-y-3">
                    {plan.features.map((feature, index) => (
                      <li key={index} className="flex items-start">
                        <CheckIcon className="h-5 w-5 text-green-500 mt-0.5 mr-3 flex-shrink-0" />
                        <span className="text-gray-600">{feature}</span>
                      </li>
                    ))}
                  </ul>

                  {plan.limitations.length > 0 && (
                    <div className="mt-6">
                      <h4 className="font-semibold text-gray-900 mb-3">Limitations:</h4>
                      <ul className="space-y-2">
                        {plan.limitations.map((limitation, index) => (
                          <li key={index} className="flex items-start">
                            <XMarkIcon className="h-5 w-5 text-red-400 mt-0.5 mr-3 flex-shrink-0" />
                            <span className="text-gray-500 text-sm">{limitation}</span>
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* FAQ Section */}
      <div className="mt-16 text-center">
        <h3 className="text-2xl font-bold text-gray-900 mb-4">Frequently Asked Questions</h3>
        <div className="max-w-3xl mx-auto space-y-6">
          <div className="text-left">
            <h4 className="font-semibold text-gray-900 mb-2">Can I change my plan anytime?</h4>
            <p className="text-gray-600">
              Yes, you can upgrade or downgrade your plan at any time. Changes will be prorated and reflected in your next billing cycle.
            </p>
          </div>
          <div className="text-left">
            <h4 className="font-semibold text-gray-900 mb-2">What payment methods do you accept?</h4>
            <p className="text-gray-600">
              We accept all major credit cards, debit cards, and bank transfers through our secure payment processor Paystack.
            </p>
          </div>
          <div className="text-left">
            <h4 className="font-semibold text-gray-900 mb-2">Is there a free trial?</h4>
            <p className="text-gray-600">
              Yes, all new accounts come with a 14-day free trial. No credit card required to get started.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SubscriptionPlans;
