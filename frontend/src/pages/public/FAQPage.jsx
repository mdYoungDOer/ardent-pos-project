import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { FiSearch, FiChevronDown, FiChevronUp, FiMail, FiPhone, FiMessageCircle } from 'react-icons/fi';

const FAQPage = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [openItems, setOpenItems] = useState(new Set());

  const toggleItem = (id) => {
    const newOpenItems = new Set(openItems);
    if (newOpenItems.has(id)) {
      newOpenItems.delete(id);
    } else {
      newOpenItems.add(id);
    }
    setOpenItems(newOpenItems);
  };

  const faqCategories = [
    {
      title: 'Getting Started',
      icon: '🚀',
      questions: [
        {
          id: 'gs-1',
          question: 'How do I get started with Ardent POS?',
          answer: 'Getting started is easy! Simply sign up for a free account, complete your business profile, add your products, and you\'re ready to start processing sales. Our setup wizard will guide you through each step.'
        },
        {
          id: 'gs-2',
          question: 'What information do I need to set up my account?',
          answer: 'You\'ll need your business name, contact information, business type, and basic product information. We also recommend having your business registration details ready for verification.'
        },
        {
          id: 'gs-3',
          question: 'Can I import my existing product data?',
          answer: 'Yes! We support CSV imports for products, customers, and inventory. Our import wizard will help you map your existing data to our system format.'
        },
        {
          id: 'gs-4',
          question: 'How long does the setup process take?',
          answer: 'Most businesses can be up and running in under 30 minutes. The basic setup takes about 10-15 minutes, with additional time for importing data if needed.'
        }
      ]
    },
    {
      title: 'Pricing & Billing',
      icon: '💰',
      questions: [
        {
          id: 'pb-1',
          question: 'What payment methods do you accept?',
          answer: 'We accept all major credit cards, debit cards, and bank transfers through Paystack. We also support mobile money payments for convenience.'
        },
        {
          id: 'pb-2',
          question: 'Can I change my plan at any time?',
          answer: 'Yes, you can upgrade or downgrade your plan at any time. Changes take effect immediately and billing is prorated accordingly.'
        },
        {
          id: 'pb-3',
          question: 'Is there a setup fee or hidden charges?',
          answer: 'No, there are no setup fees or hidden charges. You only pay the monthly subscription fee. Transaction fees for payments are clearly displayed.'
        },
        {
          id: 'pb-4',
          question: 'Do you offer annual billing discounts?',
          answer: 'Yes, we offer a 20% discount when you pay annually. This applies to all paid plans and can save you significantly over monthly billing.'
        },
        {
          id: 'pb-5',
          question: 'What happens if I exceed my plan limits?',
          answer: 'You\'ll receive notifications when approaching limits. You can upgrade your plan or purchase additional capacity as needed.'
        }
      ]
    },
    {
      title: 'Features & Functionality',
      icon: '⚡',
      questions: [
        {
          id: 'ff-1',
          question: 'Can I use Ardent POS offline?',
          answer: 'Yes! Our mobile app works offline and syncs data when you\'re back online. All transactions are stored locally and uploaded automatically.'
        },
        {
          id: 'ff-2',
          question: 'How does inventory management work?',
          answer: 'Our inventory system tracks stock levels in real-time, sends low stock alerts, and provides detailed reports. You can set minimum stock levels and automatic reorder points.'
        },
        {
          id: 'ff-3',
          question: 'Can I manage multiple locations?',
          answer: 'Yes, our Pro plan supports multi-location management. You can view inventory across all locations and transfer stock between them.'
        },
        {
          id: 'ff-4',
          question: 'What types of reports are available?',
          answer: 'We offer comprehensive reporting including sales reports, inventory reports, customer analytics, employee performance, and custom date range reports.'
        },
        {
          id: 'ff-5',
          question: 'Can I customize receipts and invoices?',
          answer: 'Yes, you can customize receipts with your logo, business information, and custom fields. Professional invoices are also available.'
        }
      ]
    },
    {
      title: 'Security & Data',
      icon: '🔒',
      questions: [
        {
          id: 'sd-1',
          question: 'How secure is my business data?',
          answer: 'We use enterprise-grade security including SSL encryption, secure data centers, and regular security audits. Your data is backed up daily and protected by multiple layers of security.'
        },
        {
          id: 'sd-2',
          question: 'Can I export my data?',
          answer: 'Yes, you can export your data in various formats including CSV, Excel, and PDF. There are no restrictions on data export.'
        },
        {
          id: 'sd-3',
          question: 'What happens to my data if I cancel?',
          answer: 'Your data is retained for 30 days after cancellation. You can download all your data during this period. After 30 days, data is permanently deleted.'
        },
        {
          id: 'sd-4',
          question: 'Do you comply with data protection regulations?',
          answer: 'Yes, we comply with all relevant data protection regulations and implement best practices for data security and privacy.'
        }
      ]
    },
    {
      title: 'Support & Training',
      icon: '🎓',
      questions: [
        {
          id: 'st-1',
          question: 'What support options are available?',
          answer: 'We offer email support for all plans, priority support for paid plans, and phone support for Pro users. Our knowledge base and video tutorials are also available.'
        },
        {
          id: 'st-2',
          question: 'Do you provide training?',
          answer: 'Yes, we offer free onboarding sessions, video tutorials, and webinars. Pro users get personalized training sessions.'
        },
        {
          id: 'st-3',
          question: 'How quickly do you respond to support requests?',
          answer: 'We typically respond within 2-4 hours during business hours. Pro users get priority support with faster response times.'
        },
        {
          id: 'st-4',
          question: 'Is there a community or forum?',
          answer: 'Yes, we have an active user community where you can connect with other business owners, share tips, and get advice.'
        }
      ]
    },
    {
      title: 'Technical Requirements',
      icon: '💻',
      questions: [
        {
          id: 'tr-1',
          question: 'What devices are supported?',
          answer: 'Ardent POS works on iOS and Android devices, as well as web browsers on desktop and tablet computers. We recommend using the latest versions for best performance.'
        },
        {
          id: 'tr-2',
          question: 'Do I need an internet connection?',
          answer: 'While the app works offline, an internet connection is recommended for real-time sync, updates, and full functionality.'
        },
        {
          id: 'tr-3',
          question: 'Can I integrate with other software?',
          answer: 'Yes, we offer API access and integrations with popular accounting software, e-commerce platforms, and other business tools.'
        },
        {
          id: 'tr-4',
          question: 'What browsers are supported?',
          answer: 'We support Chrome, Firefox, Safari, and Edge. We recommend using the latest versions for optimal performance.'
        }
      ]
    }
  ];

  // Filter FAQs based on search term
  const filteredCategories = faqCategories.map(category => ({
    ...category,
    questions: category.questions.filter(q =>
      q.question.toLowerCase().includes(searchTerm.toLowerCase()) ||
      q.answer.toLowerCase().includes(searchTerm.toLowerCase())
    )
  })).filter(category => category.questions.length > 0);

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section */}
      <section className="pt-24 pb-16 bg-gradient-to-br from-primary-50 to-accent-50">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h1 className="text-4xl font-bold text-gray-900 mb-4">
            Frequently Asked Questions
          </h1>
          <p className="text-xl text-gray-600 mb-8">
            Find answers to common questions about Ardent POS
          </p>
          
          {/* Search Bar */}
          <div className="max-w-md mx-auto relative">
            <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" />
            <input
              type="text"
              placeholder="Search questions..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
            />
          </div>
        </div>
      </section>

      {/* FAQ Content */}
      <section className="py-16">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          {filteredCategories.length === 0 ? (
            <div className="text-center py-12">
              <p className="text-gray-500 text-lg">No questions found matching your search.</p>
              <button
                onClick={() => setSearchTerm('')}
                className="mt-4 text-primary hover:text-primary-600 font-medium"
              >
                Clear search
              </button>
            </div>
          ) : (
            <div className="space-y-8">
              {filteredCategories.map((category) => (
                <div key={category.title} className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                  <div className="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h2 className="text-xl font-semibold text-gray-900 flex items-center">
                      <span className="mr-3">{category.icon}</span>
                      {category.title}
                    </h2>
                  </div>
                  
                  <div className="divide-y divide-gray-200">
                    {category.questions.map((item) => (
                      <div key={item.id} className="px-6 py-4">
                        <button
                          onClick={() => toggleItem(item.id)}
                          className="w-full flex justify-between items-start text-left"
                        >
                          <h3 className="text-lg font-medium text-gray-900 pr-4">
                            {item.question}
                          </h3>
                          {openItems.has(item.id) ? (
                            <FiChevronUp className="h-5 w-5 text-gray-500 flex-shrink-0 mt-1" />
                          ) : (
                            <FiChevronDown className="h-5 w-5 text-gray-500 flex-shrink-0 mt-1" />
                          )}
                        </button>
                        
                        {openItems.has(item.id) && (
                          <div className="mt-4 text-gray-600 leading-relaxed">
                            {item.answer}
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </section>

      {/* Contact Section */}
      <section className="py-16 bg-white border-t border-gray-200">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-3xl font-bold text-gray-900 mb-4">
            Still have questions?
          </h2>
          <p className="text-xl text-gray-600 mb-8">
            Our support team is here to help you get the most out of Ardent POS
          </p>
          
          <div className="grid md:grid-cols-3 gap-8">
            <div className="flex flex-col items-center">
              <div className="bg-primary-100 p-3 rounded-full mb-4">
                <FiMail className="h-6 w-6 text-primary" />
              </div>
              <h3 className="font-semibold text-gray-900 mb-2">Email Support</h3>
              <p className="text-gray-600 mb-4">Get help via email</p>
              <a href="mailto:support@ardentpos.com" className="text-primary hover:text-primary-600 font-medium">
                support@ardentpos.com
              </a>
            </div>
            
            <div className="flex flex-col items-center">
              <div className="bg-primary-100 p-3 rounded-full mb-4">
                <FiPhone className="h-6 w-6 text-primary" />
              </div>
              <h3 className="font-semibold text-gray-900 mb-2">Phone Support</h3>
              <p className="text-gray-600 mb-4">Speak with our team</p>
              <a href="tel:+233000000000" className="text-primary hover:text-primary-600 font-medium">
                +233 00 000 0000
              </a>
            </div>
            
            <div className="flex flex-col items-center">
              <div className="bg-primary-100 p-3 rounded-full mb-4">
                <FiMessageCircle className="h-6 w-6 text-primary" />
              </div>
              <h3 className="font-semibold text-gray-900 mb-2">Live Chat</h3>
              <p className="text-gray-600 mb-4">Chat with us online</p>
              <Link to="/contact" className="text-primary hover:text-primary-600 font-medium">
                Start Chat
              </Link>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
};

export default FAQPage;
