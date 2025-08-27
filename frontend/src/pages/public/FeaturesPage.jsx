import { 
  FiSmartphone, 
  FiCreditCard, 
  FiBarChart2, 
  FiClipboard,
  FiUsers,
  FiShield,
  FiCloud,
  FiZap,
  FiGlobe,
  FiHeadphones
} from 'react-icons/fi'

const FeaturesPage = () => {
  const features = [
    {
      category: 'Sales & Checkout',
      items: [
        {
          icon: FiCreditCard,
          title: 'Secure Payment Processing',
          description: 'Accept payments via cash, card, and mobile money with Paystack integration.'
        },
        {
          icon: FiSmartphone,
          title: 'Mobile-First Interface',
          description: 'Touch-optimized interface designed for tablets and mobile devices.'
        },
        {
          icon: FiZap,
          title: 'Quick Checkout',
          description: 'Fast barcode scanning and streamlined checkout process.'
        }
      ]
    },
    {
      category: 'Inventory Management',
      items: [
        {
          icon: FiClipboard,
          title: 'Real-time Stock Tracking',
          description: 'Monitor inventory levels in real-time with automatic low-stock alerts.'
        },
        {
          icon: FiBarChart2,
          title: 'Inventory Analytics',
          description: 'Track product performance and optimize your inventory.'
        },
        {
          icon: FiGlobe,
          title: 'Multi-location Support',
          description: 'Manage inventory across multiple store locations.'
        }
      ]
    },
    {
      category: 'Customer Management',
      items: [
        {
          icon: FiUsers,
          title: 'Customer Database',
          description: 'Maintain detailed customer profiles and purchase history.'
        },
        {
          icon: FiBarChart2,
          title: 'Loyalty Programs',
          description: 'Build customer loyalty with points and rewards programs.'
        },
        {
          icon: FiHeadphones,
          title: 'Customer Support',
          description: 'Track customer interactions and support tickets.'
        }
      ]
    },
    {
      category: 'Security & Reliability',
      items: [
        {
          icon: FiShield,
          title: 'Enterprise Security',
          description: 'Bank-level security with encrypted data and secure access controls.'
        },
        {
          icon: FiCloud,
          title: 'Cloud-Based',
          description: 'Access your data from anywhere with automatic backups.'
        },
        {
          icon: FiZap,
          title: '99.9% Uptime',
          description: 'Reliable service with minimal downtime and fast performance.'
        }
      ]
    }
  ]

  return (
    <div className="bg-white">
      {/* Hero Section */}
      <div className="relative bg-primary-50 py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <h1 className="text-4xl font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
              Powerful Features for
              <span className="block text-primary">Modern Businesses</span>
            </h1>
            <p className="mt-3 max-w-md mx-auto text-base text-gray-500 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
              Everything you need to run your business efficiently, from sales and inventory 
              to customer management and reporting.
            </p>
          </div>
        </div>
      </div>

      {/* Features Grid */}
      <div className="py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {features.map((category, categoryIndex) => (
            <div key={category.category} className={categoryIndex > 0 ? 'mt-20' : ''}>
              <div className="text-center">
                <h2 className="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                  {category.category}
                </h2>
              </div>
              
              <div className="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                {category.items.map((feature, index) => (
                  <div key={index} className="relative group">
                    <div className="relative p-6 bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                      <div>
                        <span className="rounded-lg inline-flex p-3 bg-primary text-white">
                          <feature.icon className="h-6 w-6" />
                        </span>
                      </div>
                      <div className="mt-4">
                        <h3 className="text-lg font-medium text-gray-900">
                          {feature.title}
                        </h3>
                        <p className="mt-2 text-base text-gray-500">
                          {feature.description}
                        </p>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Integration Section */}
      <div className="bg-gray-50 py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <h2 className="text-3xl font-extrabold text-gray-900 sm:text-4xl">
              Seamless Integrations
            </h2>
            <p className="mt-4 max-w-2xl mx-auto text-xl text-gray-500">
              Connect with the tools you already use to streamline your workflow.
            </p>
          </div>

          <div className="mt-12 grid grid-cols-2 gap-8 md:grid-cols-4">
            <div className="col-span-1 flex justify-center py-8 px-8 bg-white rounded-lg shadow-sm">
              <div className="text-center">
                <div className="text-2xl font-bold text-primary">Paystack</div>
                <p className="text-sm text-gray-500 mt-1">Payment Processing</p>
              </div>
            </div>
            <div className="col-span-1 flex justify-center py-8 px-8 bg-white rounded-lg shadow-sm">
              <div className="text-center">
                <div className="text-2xl font-bold text-primary">SendGrid</div>
                <p className="text-sm text-gray-500 mt-1">Email Notifications</p>
              </div>
            </div>
            <div className="col-span-1 flex justify-center py-8 px-8 bg-white rounded-lg shadow-sm">
              <div className="text-center">
                <div className="text-2xl font-bold text-primary">PostgreSQL</div>
                <p className="text-sm text-gray-500 mt-1">Secure Database</p>
              </div>
            </div>
            <div className="col-span-1 flex justify-center py-8 px-8 bg-white rounded-lg shadow-sm">
              <div className="text-center">
                <div className="text-2xl font-bold text-primary">Digital Ocean</div>
                <p className="text-sm text-gray-500 mt-1">Cloud Hosting</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* CTA Section */}
      <div className="bg-primary">
        <div className="max-w-2xl mx-auto text-center py-16 px-4 sm:py-20 sm:px-6 lg:px-8">
          <h2 className="text-3xl font-extrabold text-white sm:text-4xl">
            <span className="block">Ready to experience these features?</span>
            <span className="block">Start your free trial today.</span>
          </h2>
          <p className="mt-4 text-lg leading-6 text-primary-100">
            No setup fees, no long-term contracts. Get started in minutes.
          </p>
          <a
            href="/auth/register"
            className="mt-8 w-full inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-primary bg-white hover:bg-primary-50 sm:w-auto"
          >
            Start Free Trial
          </a>
        </div>
      </div>
    </div>
  )
}

export default FeaturesPage
