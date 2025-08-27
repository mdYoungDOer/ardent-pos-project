import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import StickyHeader from '../../components/layout/StickyHeader';
import Footer from '../../components/layout/Footer';
import Preloader from '../../components/ui/Preloader';

const CookiePolicyPage = () => {
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const timer = setTimeout(() => {
      setLoading(false);
    }, 1000);
    return () => clearTimeout(timer);
  }, []);

  if (loading) {
    return <Preloader />;
  }

  return (
    <div>
      <StickyHeader />
      <div className="mt-16">
        {/* Hero Section */}
        <section className="bg-gradient-to-br from-primary-50 to-accent-50 py-16">
          <div className="container mx-auto px-4">
            <div className="text-center">
              <h1 className="text-4xl font-bold text-gray-900 mb-4">Cookie Policy</h1>
              <p className="text-lg text-gray-600 max-w-2xl mx-auto">
                Learn how we use cookies and similar technologies to enhance your experience on Ardent POS.
              </p>
              <p className="text-sm text-gray-500 mt-4">
                Last updated: {new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}
              </p>
            </div>
          </div>
        </section>

        {/* Content Section */}
        <section className="py-16">
          <div className="container mx-auto px-4 max-w-4xl">
            <div className="prose prose-lg max-w-none">
              <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                
                <h2 className="text-2xl font-bold text-gray-900 mb-6">1. What Are Cookies?</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    Cookies are small text files that are placed on your device when you visit our website. They help us provide you with a better experience by remembering your preferences, analyzing how you use our site, and personalizing content.
                  </p>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">2. How We Use Cookies</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    We use cookies for the following purposes:
                  </p>
                  <ul className="list-disc list-inside text-gray-700 ml-4 space-y-2">
                    <li><strong>Essential Cookies:</strong> Required for basic website functionality</li>
                    <li><strong>Performance Cookies:</strong> Help us understand how visitors interact with our website</li>
                    <li><strong>Functional Cookies:</strong> Remember your preferences and settings</li>
                    <li><strong>Marketing Cookies:</strong> Used to deliver relevant advertisements</li>
                  </ul>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">3. Types of Cookies We Use</h2>
                <div className="space-y-6 mb-8">
                  
                  <div className="bg-blue-50 rounded-lg p-6">
                    <h3 className="text-xl font-semibold text-blue-900 mb-3">3.1 Essential Cookies</h3>
                    <p className="text-blue-800 mb-3">
                      These cookies are necessary for the website to function properly. They enable basic functions like page navigation, access to secure areas, and form submissions.
                    </p>
                    <ul className="list-disc list-inside text-blue-800 space-y-1">
                      <li>Authentication and session management</li>
                      <li>Security features and fraud prevention</li>
                      <li>Load balancing and performance optimization</li>
                      <li>Language and region preferences</li>
                    </ul>
                  </div>

                  <div className="bg-green-50 rounded-lg p-6">
                    <h3 className="text-xl font-semibold text-green-900 mb-3">3.2 Performance Cookies</h3>
                    <p className="text-green-800 mb-3">
                      These cookies help us understand how visitors interact with our website by collecting and reporting information anonymously.
                    </p>
                    <ul className="list-disc list-inside text-green-800 space-y-1">
                      <li>Website usage analytics and statistics</li>
                      <li>Error tracking and debugging</li>
                      <li>Performance monitoring and optimization</li>
                      <li>User behavior analysis</li>
                    </ul>
                  </div>

                  <div className="bg-purple-50 rounded-lg p-6">
                    <h3 className="text-xl font-semibold text-purple-900 mb-3">3.3 Functional Cookies</h3>
                    <p className="text-purple-800 mb-3">
                      These cookies enable enhanced functionality and personalization, such as remembering your preferences and settings.
                    </p>
                    <ul className="list-disc list-inside text-purple-800 space-y-1">
                      <li>User preferences and settings</li>
                      <li>Language and localization preferences</li>
                      <li>Theme and display preferences</li>
                      <li>Form data and input assistance</li>
                    </ul>
                  </div>

                  <div className="bg-orange-50 rounded-lg p-6">
                    <h3 className="text-xl font-semibold text-orange-900 mb-3">3.4 Marketing Cookies</h3>
                    <p className="text-orange-800 mb-3">
                      These cookies are used to track visitors across websites to display relevant and engaging advertisements.
                    </p>
                    <ul className="list-disc list-inside text-orange-800 space-y-1">
                      <li>Advertising and marketing campaigns</li>
                      <li>Social media integration</li>
                      <li>Retargeting and remarketing</li>
                      <li>Conversion tracking and analytics</li>
                    </ul>
                  </div>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">4. Third-Party Cookies</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    We may use third-party services that place cookies on your device. These services include:
                  </p>
                  <ul className="list-disc list-inside text-gray-700 ml-4 space-y-2">
                    <li><strong>Google Analytics:</strong> Website analytics and performance tracking</li>
                    <li><strong>Google Ads:</strong> Advertising and conversion tracking</li>
                    <li><strong>Facebook Pixel:</strong> Social media advertising and analytics</li>
                    <li><strong>Paystack:</strong> Payment processing and security</li>
                    <li><strong>SendGrid:</strong> Email delivery and analytics</li>
                  </ul>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">5. Cookie Duration</h2>
                <div className="space-y-4 mb-8">
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="bg-gray-50 rounded-lg p-4">
                      <h4 className="font-semibold text-gray-900 mb-2">Session Cookies</h4>
                      <p className="text-sm text-gray-700">Deleted when you close your browser</p>
                    </div>
                    <div className="bg-gray-50 rounded-lg p-4">
                      <h4 className="font-semibold text-gray-900 mb-2">Persistent Cookies</h4>
                      <p className="text-sm text-gray-700">Remain on your device for a set period</p>
                    </div>
                    <div className="bg-gray-50 rounded-lg p-4">
                      <h4 className="font-semibold text-gray-900 mb-2">Third-Party Cookies</h4>
                      <p className="text-sm text-gray-700">Duration set by third-party providers</p>
                    </div>
                  </div>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">6. Managing Your Cookie Preferences</h2>
                <div className="space-y-4 mb-8">
                  <h3 className="text-xl font-semibold text-gray-800">6.1 Browser Settings</h3>
                  <p className="text-gray-700">
                    You can control and manage cookies through your browser settings. Most browsers allow you to:
                  </p>
                  <ul className="list-disc list-inside text-gray-700 ml-4 space-y-2">
                    <li>View and delete existing cookies</li>
                    <li>Block cookies from specific websites</li>
                    <li>Block all cookies</li>
                    <li>Set preferences for different types of cookies</li>
                  </ul>
                  
                  <h3 className="text-xl font-semibold text-gray-800">6.2 Our Cookie Consent Widget</h3>
                  <p className="text-gray-700">
                    We provide a cookie consent widget that allows you to:
                  </p>
                  <ul className="list-disc list-inside text-gray-700 ml-4 space-y-2">
                    <li>Accept or decline non-essential cookies</li>
                    <li>Customize your cookie preferences</li>
                    <li>Update your choices at any time</li>
                    <li>Learn more about our cookie practices</li>
                  </ul>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">7. Impact of Disabling Cookies</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    While you can disable cookies, doing so may affect your experience on our website:
                  </p>
                  <ul className="list-disc list-inside text-gray-700 ml-4 space-y-2">
                    <li>Some features may not function properly</li>
                    <li>You may need to re-enter information repeatedly</li>
                    <li>Personalization features may be limited</li>
                    <li>Security features may be affected</li>
                  </ul>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">8. Updates to This Policy</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    We may update this cookie policy from time to time to reflect changes in our practices or for other operational, legal, or regulatory reasons. We will notify you of any material changes by posting the updated policy on this page.
                  </p>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">9. Contact Us</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    If you have any questions about our use of cookies or this cookie policy, please contact us:
                  </p>
                  <div className="bg-gray-50 rounded-lg p-4">
                    <p className="text-gray-700"><strong>Email:</strong> privacy@ardentpos.com</p>
                    <p className="text-gray-700"><strong>Address:</strong> Ardent POS, Accra, Ghana</p>
                    <p className="text-gray-700"><strong>Phone:</strong> +233 XX XXX XXXX</p>
                  </div>
                </div>

                <div className="border-t border-gray-200 pt-8 mt-8">
                  <p className="text-sm text-gray-500 text-center">
                    This cookie policy is effective as of {new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })} and will remain in effect except with respect to any changes in its provisions in the future.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
      <Footer />
    </div>
  );
};

export default CookiePolicyPage;
