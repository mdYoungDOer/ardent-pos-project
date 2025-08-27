import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { FiCheck, FiStar, FiArrowRight, FiUsers, FiShield, FiZap, FiHeadphones, FiGlobe } from 'react-icons/fi';
import { authAPI } from '../../services/api';
import StickyHeader from '../../components/layout/StickyHeader';
import Footer from '../../components/layout/Footer';
import Preloader from '../../components/ui/Preloader';

const LandingPage = () => {
  const [plans, setPlans] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedPlan, setSelectedPlan] = useState(null);
  const [showPricingModal, setShowPricingModal] = useState(false);
  const navigate = useNavigate();

  useEffect(() => {
    loadSubscriptionPlans();
  }, []);

  const loadSubscriptionPlans = async () => {
    try {
      setLoading(true);
      const response = await fetch('/api/subscription-plans.php');
      const data = await response.json();
      if (data.success) {
        setPlans(data.data);
      }
    } catch (error) {
      console.error('Error loading plans:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleGetStarted = (plan) => {
    setSelectedPlan(plan);
    setShowPricingModal(true);
  };

  const handlePlanSelection = (plan, billingCycle) => {
    // Store selected plan in localStorage for registration flow
    localStorage.setItem('selectedPlan', JSON.stringify({
      plan: plan,
      billingCycle: billingCycle
    }));
    
    // Navigate to registration with plan info
    navigate('/auth/register', { 
      state: { 
        selectedPlan: plan,
        billingCycle: billingCycle 
      } 
    });
  };

  const features = [
    {
      icon: FiZap,
      title: 'Lightning Fast',
      description: 'Process transactions in seconds with our optimized POS system'
    },
    {
      icon: FiShield,
      title: 'Enterprise Security',
      description: 'Bank-level security with encryption and compliance standards'
    },
    {
      icon: FiUsers,
      title: 'Multi-User Access',
      description: 'Manage multiple staff members with role-based permissions'
    },
    {
      icon: FiGlobe,
      title: 'Cloud-Based',
      description: 'Access your data anywhere, anytime with cloud synchronization'
    },
    {
      icon: FiHeadphones,
      title: '24/7 Support',
      description: 'Get help whenever you need it with our dedicated support team'
    },
    {
      icon: FiStar,
      title: 'Advanced Analytics',
      description: 'Make data-driven decisions with comprehensive business insights'
    }
  ];

  const testimonials = [
    {
      name: 'Sarah Johnson',
      role: 'Restaurant Owner',
      business: 'Taste of Ghana',
      content: 'Ardent POS transformed our restaurant operations. Sales increased by 30% in the first month!',
      rating: 5
    },
    {
      name: 'Michael Osei',
      role: 'Retail Manager',
      business: 'Fashion Forward',
      content: 'The inventory management and reporting features are incredible. Highly recommended!',
      rating: 5
    },
    {
      name: 'Grace Mensah',
      role: 'Cafe Owner',
      business: 'Coffee Haven',
      content: 'Easy to use, reliable, and the customer support is outstanding. Perfect for our business.',
      rating: 5
    }
  ];

  if (loading) {
    return <Preloader />;
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Navigation */}
      <StickyHeader />

             {/* Hero Section */}
       <section className="bg-gradient-to-br from-primary to-accent text-white py-20 mt-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h1 className="text-5xl font-bold mb-6">
            Transform Your Business with
            <span className="block">Ardent POS</span>
          </h1>
          <p className="text-xl mb-8 max-w-3xl mx-auto">
            The most comprehensive point-of-sale solution for Ghanaian businesses. 
            Streamline operations, boost sales, and grow your business with our enterprise-grade platform.
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <button 
              onClick={() => setShowPricingModal(true)}
              className="bg-white text-primary px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors"
            >
              View Pricing Plans
            </button>
            <Link 
              to="/auth/register"
              className="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-primary transition-colors"
            >
              Start Free Trial
            </Link>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 mb-4">
              Why Choose Ardent POS?
            </h2>
            <p className="text-xl text-gray-600 max-w-3xl mx-auto">
              Built specifically for the Ghanaian market with features that drive real business growth
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {features.map((feature, index) => {
              const Icon = feature.icon;
              return (
                <div key={index} className="text-center p-6 rounded-lg hover:shadow-lg transition-shadow">
                  <div className="bg-primary/10 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <Icon className="h-8 w-8 text-primary" />
                  </div>
                  <h3 className="text-xl font-semibold text-gray-900 mb-2">{feature.title}</h3>
                  <p className="text-gray-600">{feature.description}</p>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* Pricing Section */}
      <section className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 mb-4">
              Choose Your Perfect Plan
            </h2>
            <p className="text-xl text-gray-600 max-w-3xl mx-auto">
              Start with our free trial and scale as you grow. All plans include our core features.
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
            {plans.map((plan) => (
              <div 
                key={plan.id} 
                className={`bg-white rounded-lg shadow-lg overflow-hidden ${
                  plan.is_popular ? 'ring-2 ring-primary transform scale-105' : ''
                }`}
              >
                {plan.is_popular && (
                  <div className="bg-primary text-white text-center py-2 text-sm font-medium">
                    Most Popular
                  </div>
                )}
                
                <div className="p-6">
                  <h3 className="text-xl font-bold text-gray-900 mb-2">{plan.name}</h3>
                  <p className="text-gray-600 text-sm mb-4">{plan.description}</p>
                  
                  <div className="mb-6">
                    <div className="text-3xl font-bold text-gray-900">
                      ₵{plan.monthly_price}
                      <span className="text-sm font-normal text-gray-500">/month</span>
                    </div>
                    <div className="text-sm text-gray-500">
                      ₵{plan.yearly_price}/year
                    </div>
                  </div>
                  
                  <ul className="space-y-3 mb-6">
                    {plan.features?.slice(0, 5).map((feature, index) => (
                      <li key={index} className="flex items-center text-sm text-gray-600">
                        <FiCheck className="h-4 w-4 text-green-500 mr-2 flex-shrink-0" />
                        {feature}
                      </li>
                    ))}
                    {plan.features?.length > 5 && (
                      <li className="text-sm text-gray-500">
                        +{plan.features.length - 5} more features
                      </li>
                    )}
                  </ul>
                  
                  <button
                    onClick={() => handleGetStarted(plan)}
                    className={`w-full py-2 px-4 rounded-lg font-semibold transition-colors ${
                      plan.is_popular
                        ? 'bg-primary text-white hover:bg-primary-dark'
                        : 'bg-gray-100 text-gray-900 hover:bg-gray-200'
                    }`}
                  >
                    Get Started
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Testimonials Section */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 mb-4">
              Trusted by Ghanaian Businesses
            </h2>
            <p className="text-xl text-gray-600">
              See what our customers have to say about Ardent POS
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {testimonials.map((testimonial, index) => (
              <div key={index} className="bg-gray-50 p-6 rounded-lg">
                <div className="flex items-center mb-4">
                  {[...Array(testimonial.rating)].map((_, i) => (
                    <FiStar key={i} className="h-5 w-5 text-yellow-400 fill-current" />
                  ))}
                </div>
                <p className="text-gray-700 mb-4">"{testimonial.content}"</p>
                <div>
                  <p className="font-semibold text-gray-900">{testimonial.name}</p>
                  <p className="text-sm text-gray-600">{testimonial.role}</p>
                  <p className="text-sm text-primary">{testimonial.business}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-20 bg-primary text-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-4xl font-bold mb-4">
            Ready to Transform Your Business?
          </h2>
          <p className="text-xl mb-8 max-w-2xl mx-auto">
            Join thousands of Ghanaian businesses already using Ardent POS to grow their revenue and streamline operations.
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link 
              to="/auth/register"
              className="bg-white text-primary px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors"
            >
              Start Free Trial
            </Link>
            <button 
              onClick={() => setShowPricingModal(true)}
              className="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-primary transition-colors"
            >
              View All Plans
            </button>
          </div>
        </div>
      </section>

      {/* Footer */}
      <Footer />

      {/* Pricing Modal */}
      {showPricingModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6">
              <div className="flex justify-between items-center mb-6">
                <h2 className="text-2xl font-bold">Choose Your Billing Cycle</h2>
                <button
                  onClick={() => setShowPricingModal(false)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  ✕
                </button>
              </div>
              
              {selectedPlan && (
                <div className="text-center mb-8">
                  <h3 className="text-xl font-semibold mb-2">{selectedPlan.name} Plan</h3>
                  <p className="text-gray-600">{selectedPlan.description}</p>
                </div>
              )}
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="border-2 border-gray-200 rounded-lg p-6 hover:border-primary transition-colors">
                  <h4 className="text-lg font-semibold mb-2">Monthly Billing</h4>
                  <div className="text-3xl font-bold text-gray-900 mb-2">
                    ₵{selectedPlan?.monthly_price}
                    <span className="text-sm font-normal text-gray-500">/month</span>
                  </div>
                  <p className="text-gray-600 mb-4">Perfect for businesses getting started</p>
                  <button
                    onClick={() => handlePlanSelection(selectedPlan, 'monthly')}
                    className="w-full bg-primary text-white py-2 px-4 rounded-lg hover:bg-primary-dark transition-colors"
                  >
                    Choose Monthly
                  </button>
                </div>
                
                <div className="border-2 border-primary rounded-lg p-6 bg-primary/5">
                  <div className="bg-primary text-white text-xs px-2 py-1 rounded-full inline-block mb-2">
                    Save 17%
                  </div>
                  <h4 className="text-lg font-semibold mb-2">Yearly Billing</h4>
                  <div className="text-3xl font-bold text-gray-900 mb-2">
                    ₵{selectedPlan?.yearly_price}
                    <span className="text-sm font-normal text-gray-500">/year</span>
                  </div>
                  <p className="text-gray-600 mb-4">Best value for growing businesses</p>
                  <button
                    onClick={() => handlePlanSelection(selectedPlan, 'yearly')}
                    className="w-full bg-primary text-white py-2 px-4 rounded-lg hover:bg-primary-dark transition-colors"
                  >
                    Choose Yearly
                  </button>
                </div>
              </div>
              
              <div className="mt-6 text-center">
                <p className="text-sm text-gray-600">
                  All plans include a 14-day free trial. No credit card required.
                </p>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default LandingPage;
