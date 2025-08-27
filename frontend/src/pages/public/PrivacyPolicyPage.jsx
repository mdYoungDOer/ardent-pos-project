import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import StickyHeader from '../../components/layout/StickyHeader';
import Footer from '../../components/layout/Footer';
import Preloader from '../../components/ui/Preloader';

const PrivacyPolicyPage = () => {
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
              <h1 className="text-4xl font-bold text-gray-900 mb-4">Privacy Policy</h1>
              <p className="text-lg text-gray-600 max-w-2xl mx-auto">
                Your privacy is important to us. This policy explains how we collect, use, and protect your information.
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
                
                <h2 className="text-2xl font-bold text-gray-900 mb-6">1. Information We Collect</h2>
                <div className="space-y-4 mb-8">
                  <h3 className="text-xl font-semibold text-gray-800">1.1 Personal Information</h3>
                  <p className="text-gray-700">
                    We collect information you provide directly to us, such as when you create an account, make a purchase, or contact us for support. This may include:
                  </p>
                  <ul className="list-disc list-inside text-gray-700 ml-4 space-y-2">
                    <li>Name, email address, and phone number</li>
                    <li>Business information and address</li>
                    <li>Payment and billing information</li>
                    <li>Account credentials and preferences</li>
                    <li>Communication history and support requests</li>
                  </ul>
                </div>

                <div className="space-y-4 mb-8">
                  <h3 className="text-xl font-semibold text-gray-800">1.2 Automatically Collected Information</h3>
                  <p className="text-gray-700">
                    We automatically collect certain information when you use our services:
                  </p>
                  <ul className="list-disc list-inside text-gray-700 ml-4 space-y-2">
                    <li>Device information (IP address, browser type, operating system)</li>
                    <li>Usage data (pages visited, features used, time spent)</li>
                    <li>Cookies and similar tracking technologies</li>
                    <li>Log files and analytics data</li>
                  </ul>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">2. How We Use Your Information</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    We use the information we collect to:
                  </p>
                  <ul className="list-disc list-inside text-gray-700 ml-4 space-y-2">
                    <li>Provide, maintain, and improve our services</li>
                    <li>Process transactions and send related information</li>
                    <li>Send technical notices, updates, and support messages</li>
                    <li>Respond to your comments, questions, and requests</li>
                    <li>Monitor and analyze trends, usage, and activities</li>
                    <li>Detect, investigate, and prevent fraudulent transactions</li>
                    <li>Personalize and improve your experience</li>
                  </ul>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">3. Information Sharing and Disclosure</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    We do not sell, trade, or otherwise transfer your personal information to third parties except in the following circumstances:
                  </p>
                  <ul className="list-disc list-inside text-gray-700 ml-4 space-y-2">
                    <li><strong>Service Providers:</strong> We may share information with trusted third-party service providers who assist us in operating our platform</li>
                    <li><strong>Legal Requirements:</strong> We may disclose information if required by law or to protect our rights and safety</li>
                    <li><strong>Business Transfers:</strong> In the event of a merger, acquisition, or sale of assets, your information may be transferred</li>
                    <li><strong>Consent:</strong> We may share information with your explicit consent</li>
                  </ul>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">4. Data Security</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. These measures include:
                  </p>
                  <ul className="list-disc list-inside text-gray-700 ml-4 space-y-2">
                    <li>Encryption of data in transit and at rest</li>
                    <li>Regular security assessments and updates</li>
                    <li>Access controls and authentication measures</li>
                    <li>Employee training on data protection</li>
                    <li>Incident response procedures</li>
                  </ul>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">5. Your Rights and Choices</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    Depending on your location, you may have certain rights regarding your personal information:
                  </p>
                  <ul className="list-disc list-inside text-gray-700 ml-4 space-y-2">
                    <li><strong>Access:</strong> Request access to your personal information</li>
                    <li><strong>Correction:</strong> Request correction of inaccurate information</li>
                    <li><strong>Deletion:</strong> Request deletion of your personal information</li>
                    <li><strong>Portability:</strong> Request a copy of your data in a portable format</li>
                    <li><strong>Objection:</strong> Object to certain processing activities</li>
                    <li><strong>Withdrawal:</strong> Withdraw consent where processing is based on consent</li>
                  </ul>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">6. Cookies and Tracking Technologies</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    We use cookies and similar technologies to enhance your experience, analyze usage, and provide personalized content. You can control cookie settings through your browser preferences or our cookie consent widget.
                  </p>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">7. International Data Transfers</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    Your information may be transferred to and processed in countries other than your own. We ensure appropriate safeguards are in place to protect your information in accordance with this privacy policy and applicable laws.
                  </p>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">8. Children's Privacy</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    Our services are not intended for children under 13 years of age. We do not knowingly collect personal information from children under 13. If you believe we have collected information from a child under 13, please contact us immediately.
                  </p>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">9. Changes to This Policy</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    We may update this privacy policy from time to time. We will notify you of any material changes by posting the new policy on this page and updating the "Last updated" date. Your continued use of our services after such changes constitutes acceptance of the updated policy.
                  </p>
                </div>

                <h2 className="text-2xl font-bold text-gray-900 mb-6">10. Contact Us</h2>
                <div className="space-y-4 mb-8">
                  <p className="text-gray-700">
                    If you have any questions about this privacy policy or our data practices, please contact us:
                  </p>
                  <div className="bg-gray-50 rounded-lg p-4">
                    <p className="text-gray-700"><strong>Email:</strong> privacy@ardentpos.com</p>
                    <p className="text-gray-700"><strong>Address:</strong> Ardent POS, Accra, Ghana</p>
                    <p className="text-gray-700"><strong>Phone:</strong> +233 XX XXX XXXX</p>
                  </div>
                </div>

                <div className="border-t border-gray-200 pt-8 mt-8">
                  <p className="text-sm text-gray-500 text-center">
                    This privacy policy is effective as of {new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })} and will remain in effect except with respect to any changes in its provisions in the future.
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

export default PrivacyPolicyPage;
