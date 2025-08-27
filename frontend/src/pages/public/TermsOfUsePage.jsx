import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import StickyHeader from '../../components/layout/StickyHeader';
import Footer from '../../components/layout/Footer';
import Preloader from '../../components/ui/Preloader';

const TermsOfUsePage = () => {
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
              <h1 className="text-4xl font-bold text-gray-900 mb-4">Terms of Use</h1>
              <p className="text-lg text-gray-600 max-w-2xl mx-auto">
                Please read these terms carefully before using our services. By using Ardent POS, you agree to these terms.
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
                
                <h2 className="text-2xl font-bold text-gray-900 mb-6">1. Acceptance of Terms</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    By accessing and using Ardent POS ("the Service"), you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.
                  </p>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">2. Description of Service</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    Ardent POS is a cloud-based point of sale system designed for businesses to manage sales, inventory, customers, and business operations. The Service includes:
                  </p>
                  <ul className="list-disc list-inside text-gray-700 ml-4 space-y-2">
                    <li>Point of sale functionality</li>
                    <li>Inventory management</li>
                    <li>Customer relationship management</li>
                    <li>Sales reporting and analytics</li>
                    <li>Payment processing integration</li>
                    <li>Multi-location support</li>
                    <li>Mobile and web applications</li>
                  </ul>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">3. User Accounts and Registration</h2>
                <div className="space-y-4 mb-8">
                  <h3 className="text-xl font-semibold text-gray-800">3.1 Account Creation</h3>
                  <p className="text-gray-700">
                    To use certain features of the Service, you must create an account. You agree to provide accurate, current, and complete information during registration and to update such information to keep it accurate, current, and complete.
                  </p>
                  
                  <h3 className="text-xl font-semibold text-gray-800">3.2 Account Security</h3>
                  <p className="text-gray-700">
                    You are responsible for safeguarding your account credentials and for all activities that occur under your account. You agree to notify us immediately of any unauthorized use of your account.
                  </p>
                  
                  <h3 className="text-xl font-semibold text-gray-800">3.3 Account Termination</h3>
                  <p className="text-gray-700">
                    We reserve the right to terminate or suspend your account at any time for violation of these terms or for any other reason at our sole discretion.
                  </p>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">4. Acceptable Use Policy</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    You agree not to use the Service to:
                  </p>
                  <ul className="list-disc list-inside text-gray-700 ml-4 space-y-2">
                    <li>Violate any applicable laws or regulations</li>
                    <li>Infringe upon the rights of others</li>
                    <li>Transmit harmful, offensive, or inappropriate content</li>
                    <li>Attempt to gain unauthorized access to our systems</li>
                    <li>Interfere with or disrupt the Service</li>
                    <li>Use the Service for any illegal or unauthorized purpose</li>
                    <li>Attempt to reverse engineer or copy our software</li>
                  </ul>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">5. Subscription and Payment Terms</h2>
                <div className="space-y-4 mb-8">
                  <h3 className="text-xl font-semibold text-gray-800">5.1 Subscription Plans</h3>
                  <p className="text-gray-700">
                    We offer various subscription plans with different features and pricing. All subscriptions are billed in advance on a monthly or annual basis.
                  </p>
                  
                  <h3 className="text-xl font-semibold text-gray-800">5.2 Payment Processing</h3>
                  <p className="text-gray-700">
                    Payments are processed through secure third-party payment processors. You authorize us to charge your payment method for all fees associated with your subscription.
                  </p>
                  
                  <h3 className="text-xl font-semibold text-gray-800">5.3 Cancellation and Refunds</h3>
                  <p className="text-gray-700">
                    You may cancel your subscription at any time through your account settings. Refunds are provided in accordance with our refund policy, which may vary by plan and circumstances.
                  </p>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">6. Data and Privacy</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    Your privacy is important to us. Our collection and use of personal information is governed by our Privacy Policy, which is incorporated into these terms by reference.
                  </p>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">7. Intellectual Property Rights</h2>
                <div className="space-y-4 mb-8">
                  <h3 className="text-xl font-semibold text-gray-800">7.1 Our Rights</h3>
                  <p className="text-gray-700">
                    The Service and its original content, features, and functionality are owned by Ardent POS and are protected by international copyright, trademark, patent, trade secret, and other intellectual property laws.
                  </p>
                  
                  <h3 className="text-xl font-semibold text-gray-800">7.2 Your Content</h3>
                  <p className="text-gray-700">
                    You retain ownership of any content you upload to the Service. By uploading content, you grant us a limited license to use, store, and display such content as necessary to provide the Service.
                  </p>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">8. Service Availability and Support</h2>
                <div className="space-y-4 mb-8">
                  <h3 className="text-xl font-semibold text-gray-800">8.1 Service Availability</h3>
                  <p className="text-gray-700">
                    We strive to maintain high service availability but do not guarantee uninterrupted access. The Service may be temporarily unavailable due to maintenance, updates, or other factors.
                  </p>
                  
                  <h3 className="text-xl font-semibold text-gray-800">8.2 Support</h3>
                  <p className="text-gray-700">
                    We provide support through various channels including email, chat, and documentation. Support availability may vary by subscription plan.
                  </p>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">9. Disclaimers and Limitations</h2>
                <div className="space-y-4 mb-8">
                  <h3 className="text-xl font-semibold text-gray-800">9.1 Service Disclaimer</h3>
                  <p className="text-gray-700">
                    The Service is provided "as is" without warranties of any kind. We disclaim all warranties, express or implied, including but not limited to warranties of merchantability, fitness for a particular purpose, and non-infringement.
                  </p>
                  
                  <h3 className="text-xl font-semibold text-gray-800">9.2 Limitation of Liability</h3>
                  <p className="text-gray-700">
                    In no event shall Ardent POS be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses.
                  </p>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">10. Indemnification</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    You agree to indemnify and hold harmless Ardent POS and its officers, directors, employees, and agents from and against any claims, damages, obligations, losses, liabilities, costs, or debt arising from your use of the Service.
                  </p>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">11. Governing Law and Dispute Resolution</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    These terms shall be governed by and construed in accordance with the laws of Ghana. Any disputes arising from these terms or your use of the Service shall be resolved through binding arbitration in Accra, Ghana.
                  </p>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">12. Changes to Terms</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    We reserve the right to modify these terms at any time. We will notify users of material changes by posting the new terms on this page and updating the "Last updated" date. Your continued use of the Service after such changes constitutes acceptance of the updated terms.
                  </p>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">13. Contact Information</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    If you have any questions about these terms, please contact us:
                  </p>
                  <div className="bg-gray-50 rounded-lg p-4">
                    <p className="text-gray-700"><strong>Email:</strong> legal@ardentpos.com</p>
                    <p className="text-gray-700"><strong>Address:</strong> Ardent POS, Accra, Ghana</p>
                    <p className="text-gray-700"><strong>Phone:</strong> +233 XX XXX XXXX</p>
                  </div>
                </div>

                <div className="border-t border-gray-200 pt-8 mt-8">
                  <p className="text-sm text-gray-500 text-center">
                    These terms of use are effective as of {new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })} and will remain in effect except with respect to any changes in its provisions in the future.
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

export default TermsOfUsePage;
