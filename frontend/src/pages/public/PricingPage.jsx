import { Link } from 'react-router-dom'
import { HiCheck, HiX } from 'react-icons/hi'

const PricingPage = () => {
  const plans = [
    {
      name: 'Free',
      price: '₵0',
      period: 'forever',
      description: 'Perfect for getting started',
      features: [
        'Up to 100 products',
        'Basic sales tracking',
        '1 user account',
        'Email support',
        'Mobile app access',
        'Basic reporting'
      ],
      limitations: [
        'Limited to 50 transactions/month',
        'No inventory alerts',
        'No customer management',
        'No advanced reporting'
      ],
      cta: 'Get Started Free',
      popular: false
    },
    {
      name: 'Basic',
      price: '₵50',
      period: 'per month',
      description: 'Great for small businesses',
      features: [
        'Up to 1,000 products',
        'Unlimited transactions',
        'Up to 3 user accounts',
        'Inventory management',
        'Customer database',
        'Low stock alerts',
        'Email notifications',
        'Priority support',
        'Advanced reporting'
      ],
      limitations: [
        'No multi-location support',
        'Limited integrations'
      ],
      cta: 'Start Free Trial',
      popular: true
    },
    {
      name: 'Pro',
      price: '₵150',
      period: 'per month',
      description: 'Perfect for growing businesses',
      features: [
        'Unlimited products',
        'Unlimited transactions',
        'Up to 10 user accounts',
        'Multi-location support',
        'Advanced inventory',
        'Loyalty programs',
        'Custom reports',
        'API access',
        'Phone support',
        'All integrations'
      ],
      limitations: [],
      cta: 'Start Free Trial',
      popular: false
    }
  ]

  const faqs = [
    {
      question: 'Can I change my plan at any time?',
      answer: 'Yes, you can upgrade or downgrade your plan at any time. Changes take effect immediately and billing is prorated.'
    },
    {
      question: 'Is there a setup fee?',
      answer: 'No, there are no setup fees or hidden charges. You only pay the monthly subscription fee.'
    },
    {
      question: 'What payment methods do you accept?',
      answer: 'We accept all major credit cards, debit cards, and bank transfers through Paystack.'
    },
    {
      question: 'Can I cancel my subscription?',
      answer: 'Yes, you can cancel your subscription at any time. Your account will remain active until the end of your billing period.'
    },
    {
      question: 'Do you offer discounts for annual payments?',
      answer: 'Yes, we offer a 20% discount when you pay annually. Contact our sales team for more details.'
    }
  ]

  return (
    <div className="bg-white">
      {/* Hero Section */}
      <div className="relative bg-primary-50 py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <h1 className="text-4xl font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
              Simple, Transparent
              <span className="block text-primary">Pricing</span>
            </h1>
            <p className="mt-3 max-w-md mx-auto text-base text-gray-500 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
              Choose the plan that's right for your business. Start free, upgrade when you're ready.
            </p>
          </div>
        </div>
      </div>

      {/* Pricing Cards */}
      <div className="py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
            {plans.map((plan, index) => (
              <div
                key={plan.name}
                className={`relative rounded-lg shadow-sm border ${
                  plan.popular
                    ? 'border-primary ring-2 ring-primary'
                    : 'border-gray-200'
                } bg-white`}
              >
                {plan.popular && (
                  <div className="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                    <span className="inline-flex px-4 py-1 rounded-full text-sm font-semibold bg-primary text-white">
                      Most Popular
                    </span>
                  </div>
                )}
                
                <div className="p-6">
                  <div className="text-center">
                    <h3 className="text-2xl font-semibold text-gray-900">{plan.name}</h3>
                    <p className="mt-2 text-gray-500">{plan.description}</p>
                    <div className="mt-4">
                      <span className="text-4xl font-extrabold text-gray-900">{plan.price}</span>
                      <span className="text-base font-medium text-gray-500">/{plan.period}</span>
                    </div>
                  </div>

                  <div className="mt-6">
                    <Link
                      to="/auth/register"
                      className={`w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium ${
                        plan.popular
                          ? 'text-white bg-primary hover:bg-primary-600'
                          : 'text-primary bg-primary-50 hover:bg-primary-100'
                      } transition-colors`}
                    >
                      {plan.cta}
                    </Link>
                  </div>

                  <div className="mt-6">
                    <h4 className="text-sm font-medium text-gray-900 uppercase tracking-wide">
                      What's included
                    </h4>
                    <ul className="mt-4 space-y-3">
                      {plan.features.map((feature, featureIndex) => (
                        <li key={featureIndex} className="flex items-start">
                          <HiCheck className="flex-shrink-0 h-5 w-5 text-green-500" />
                          <span className="ml-3 text-sm text-gray-700">{feature}</span>
                        </li>
                      ))}
                      {plan.limitations.map((limitation, limitationIndex) => (
                        <li key={limitationIndex} className="flex items-start">
                          <HiX className="flex-shrink-0 h-5 w-5 text-gray-400" />
                          <span className="ml-3 text-sm text-gray-500">{limitation}</span>
                        </li>
                      ))}
                    </ul>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Enterprise Section */}
      <div className="bg-gray-50 py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <h2 className="text-3xl font-extrabold text-gray-900 sm:text-4xl">
              Need something more?
            </h2>
            <p className="mt-4 text-xl text-gray-500">
              We offer custom enterprise solutions for larger businesses.
            </p>
            <div className="mt-8">
              <Link
                to="/contact"
                className="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary hover:bg-primary-600"
              >
                Contact Sales
              </Link>
            </div>
          </div>
        </div>
      </div>

      {/* FAQ Section */}
      <div className="py-16">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <h2 className="text-3xl font-extrabold text-gray-900 sm:text-4xl">
              Frequently Asked Questions
            </h2>
          </div>
          
          <div className="mt-12 space-y-8">
            {faqs.map((faq, index) => (
              <div key={index} className="border-b border-gray-200 pb-8">
                <h3 className="text-lg font-medium text-gray-900 mb-4">
                  {faq.question}
                </h3>
                <p className="text-gray-600">
                  {faq.answer}
                </p>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* CTA Section */}
      <div className="bg-primary">
        <div className="max-w-2xl mx-auto text-center py-16 px-4 sm:py-20 sm:px-6 lg:px-8">
          <h2 className="text-3xl font-extrabold text-white sm:text-4xl">
            <span className="block">Ready to get started?</span>
            <span className="block">Try Ardent POS free for 14 days.</span>
          </h2>
          <p className="mt-4 text-lg leading-6 text-primary-100">
            No credit card required. Cancel anytime.
          </p>
          <Link
            to="/auth/register"
            className="mt-8 w-full inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-primary bg-white hover:bg-primary-50 sm:w-auto"
          >
            Start Free Trial
          </Link>
        </div>
      </div>
    </div>
  )
}

export default PricingPage
