import React, { useState, useEffect } from 'react';
import { FiSave, FiUser, FiSettings, FiBell, FiShield, FiCreditCard, FiGlobe } from 'react-icons/fi';
import { useAuth } from '../../contexts/AuthContext';

const SettingsPage = () => {
  const { user, logout } = useAuth();
  const [activeTab, setActiveTab] = useState('profile');
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState({ type: '', text: '' });

  // Profile settings
  const [profileData, setProfileData] = useState({
    first_name: user?.first_name || '',
    last_name: user?.last_name || '',
    email: user?.email || '',
    phone: ''
  });

  // Business settings
  const [businessData, setBusinessData] = useState({
    business_name: 'Ardent POS',
    address: '',
    phone: '',
    email: '',
    currency: 'GHS',
    timezone: 'Africa/Accra',
    tax_rate: '0',
    receipt_footer: ''
  });

  // Notification settings
  const [notificationSettings, setNotificationSettings] = useState({
    email_notifications: true,
    low_stock_alerts: true,
    sales_reports: true,
    customer_notifications: false
  });

  const handleProfileSave = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMessage({ type: '', text: '' });

    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1000));
      setMessage({ type: 'success', text: 'Profile updated successfully!' });
    } catch (error) {
      setMessage({ type: 'error', text: 'Failed to update profile' });
    } finally {
      setLoading(false);
    }
  };

  const handleBusinessSave = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMessage({ type: '', text: '' });

    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1000));
      setMessage({ type: 'success', text: 'Business settings updated successfully!' });
    } catch (error) {
      setMessage({ type: 'error', text: 'Failed to update business settings' });
    } finally {
      setLoading(false);
    }
  };

  const handleNotificationSave = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMessage({ type: '', text: '' });

    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1000));
      setMessage({ type: 'success', text: 'Notification settings updated successfully!' });
    } catch (error) {
      setMessage({ type: 'error', text: 'Failed to update notification settings' });
    } finally {
      setLoading(false);
    }
  };

  const tabs = [
    { id: 'profile', label: 'Profile', icon: FiUser },
    { id: 'business', label: 'Business', icon: FiSettings },
    { id: 'notifications', label: 'Notifications', icon: FiBell },
    { id: 'security', label: 'Security', icon: FiShield },
    { id: 'payments', label: 'Payments', icon: FiCreditCard },
    { id: 'integrations', label: 'Integrations', icon: FiGlobe }
  ];

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      {/* Header */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-[#2c2c2c]">Settings</h1>
            <p className="text-[#746354] mt-1">
              Manage your account and business preferences
            </p>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {/* Sidebar */}
        <div className="lg:col-span-1">
          <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-4">
            <nav className="space-y-2">
              {tabs.map((tab) => {
                const Icon = tab.icon;
                return (
                  <button
                    key={tab.id}
                    onClick={() => setActiveTab(tab.id)}
                    className={`w-full flex items-center px-4 py-3 rounded-lg text-left transition-colors ${
                      activeTab === tab.id
                        ? 'bg-[#e41e5b] text-white'
                        : 'text-[#746354] hover:bg-gray-50'
                    }`}
                  >
                    <Icon className="h-5 w-5 mr-3" />
                    {tab.label}
                  </button>
                );
              })}
            </nav>
          </div>
        </div>

        {/* Main Content */}
        <div className="lg:col-span-3">
          <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
            {/* Success/Error Message */}
            {message.text && (
              <div className={`mb-6 p-4 rounded-lg ${
                message.type === 'success' 
                  ? 'bg-green-100 text-green-800 border border-green-200' 
                  : 'bg-red-100 text-red-800 border border-red-200'
              }`}>
                {message.text}
              </div>
            )}

            {/* Profile Settings */}
            {activeTab === 'profile' && (
              <div>
                <h2 className="text-xl font-semibold text-[#2c2c2c] mb-6">Profile Settings</h2>
                <form onSubmit={handleProfileSave} className="space-y-6">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                        First Name
                      </label>
                      <input
                        type="text"
                        className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                        value={profileData.first_name}
                        onChange={(e) => setProfileData({ ...profileData, first_name: e.target.value })}
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                        Last Name
                      </label>
                      <input
                        type="text"
                        className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                        value={profileData.last_name}
                        onChange={(e) => setProfileData({ ...profileData, last_name: e.target.value })}
                      />
                    </div>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                      Email Address
                    </label>
                    <input
                      type="email"
                      className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                      value={profileData.email}
                      onChange={(e) => setProfileData({ ...profileData, email: e.target.value })}
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                      Phone Number
                    </label>
                    <input
                      type="tel"
                      className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                      value={profileData.phone}
                      onChange={(e) => setProfileData({ ...profileData, phone: e.target.value })}
                    />
                  </div>
                  <div className="flex justify-end">
                    <button
                      type="submit"
                      disabled={loading}
                      className="flex items-center px-6 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors disabled:opacity-50"
                    >
                      <FiSave className="h-4 w-4 mr-2" />
                      {loading ? 'Saving...' : 'Save Changes'}
                    </button>
                  </div>
                </form>
              </div>
            )}

            {/* Business Settings */}
            {activeTab === 'business' && (
              <div>
                <h2 className="text-xl font-semibold text-[#2c2c2c] mb-6">Business Settings</h2>
                <form onSubmit={handleBusinessSave} className="space-y-6">
                  <div>
                    <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                      Business Name
                    </label>
                    <input
                      type="text"
                      className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                      value={businessData.business_name}
                      onChange={(e) => setBusinessData({ ...businessData, business_name: e.target.value })}
                    />
                  </div>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                        Business Phone
                      </label>
                      <input
                        type="tel"
                        className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                        value={businessData.phone}
                        onChange={(e) => setBusinessData({ ...businessData, phone: e.target.value })}
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                        Business Email
                      </label>
                      <input
                        type="email"
                        className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                        value={businessData.email}
                        onChange={(e) => setBusinessData({ ...businessData, email: e.target.value })}
                      />
                    </div>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                      Business Address
                    </label>
                    <textarea
                      rows="3"
                      className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                      value={businessData.address}
                      onChange={(e) => setBusinessData({ ...businessData, address: e.target.value })}
                    />
                  </div>
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                      <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                        Currency
                      </label>
                      <select
                        className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                        value={businessData.currency}
                        onChange={(e) => setBusinessData({ ...businessData, currency: e.target.value })}
                      >
                        <option value="GHS">GHS - Ghanaian Cedi</option>
                        <option value="USD">USD - US Dollar</option>
                        <option value="EUR">EUR - Euro</option>
                        <option value="GBP">GBP - British Pound</option>
                      </select>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                        Timezone
                      </label>
                      <select
                        className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                        value={businessData.timezone}
                        onChange={(e) => setBusinessData({ ...businessData, timezone: e.target.value })}
                      >
                        <option value="Africa/Accra">Africa/Accra</option>
                        <option value="UTC">UTC</option>
                        <option value="America/New_York">America/New_York</option>
                        <option value="Europe/London">Europe/London</option>
                      </select>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                        Tax Rate (%)
                      </label>
                      <input
                        type="number"
                        step="0.01"
                        className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                        value={businessData.tax_rate}
                        onChange={(e) => setBusinessData({ ...businessData, tax_rate: e.target.value })}
                      />
                    </div>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-[#2c2c2c] mb-2">
                      Receipt Footer
                    </label>
                    <textarea
                      rows="3"
                      className="w-full px-3 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
                      value={businessData.receipt_footer}
                      onChange={(e) => setBusinessData({ ...businessData, receipt_footer: e.target.value })}
                      placeholder="Thank you for your business!"
                    />
                  </div>
                  <div className="flex justify-end">
                    <button
                      type="submit"
                      disabled={loading}
                      className="flex items-center px-6 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors disabled:opacity-50"
                    >
                      <FiSave className="h-4 w-4 mr-2" />
                      {loading ? 'Saving...' : 'Save Changes'}
                    </button>
                  </div>
                </form>
              </div>
            )}

            {/* Notification Settings */}
            {activeTab === 'notifications' && (
              <div>
                <h2 className="text-xl font-semibold text-[#2c2c2c] mb-6">Notification Settings</h2>
                <form onSubmit={handleNotificationSave} className="space-y-6">
                  <div className="space-y-4">
                    <div className="flex items-center justify-between p-4 border border-[#746354]/20 rounded-lg">
                      <div>
                        <h3 className="text-sm font-medium text-[#2c2c2c]">Email Notifications</h3>
                        <p className="text-sm text-[#746354]">Receive notifications via email</p>
                      </div>
                      <label className="relative inline-flex items-center cursor-pointer">
                        <input
                          type="checkbox"
                          className="sr-only peer"
                          checked={notificationSettings.email_notifications}
                          onChange={(e) => setNotificationSettings({
                            ...notificationSettings,
                            email_notifications: e.target.checked
                          })}
                        />
                        <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#e41e5b]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#e41e5b]"></div>
                      </label>
                    </div>

                    <div className="flex items-center justify-between p-4 border border-[#746354]/20 rounded-lg">
                      <div>
                        <h3 className="text-sm font-medium text-[#2c2c2c]">Low Stock Alerts</h3>
                        <p className="text-sm text-[#746354]">Get notified when products are running low</p>
                      </div>
                      <label className="relative inline-flex items-center cursor-pointer">
                        <input
                          type="checkbox"
                          className="sr-only peer"
                          checked={notificationSettings.low_stock_alerts}
                          onChange={(e) => setNotificationSettings({
                            ...notificationSettings,
                            low_stock_alerts: e.target.checked
                          })}
                        />
                        <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#e41e5b]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#e41e5b]"></div>
                      </label>
                    </div>

                    <div className="flex items-center justify-between p-4 border border-[#746354]/20 rounded-lg">
                      <div>
                        <h3 className="text-sm font-medium text-[#2c2c2c]">Sales Reports</h3>
                        <p className="text-sm text-[#746354]">Receive daily/weekly sales summaries</p>
                      </div>
                      <label className="relative inline-flex items-center cursor-pointer">
                        <input
                          type="checkbox"
                          className="sr-only peer"
                          checked={notificationSettings.sales_reports}
                          onChange={(e) => setNotificationSettings({
                            ...notificationSettings,
                            sales_reports: e.target.checked
                          })}
                        />
                        <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#e41e5b]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#e41e5b]"></div>
                      </label>
                    </div>

                    <div className="flex items-center justify-between p-4 border border-[#746354]/20 rounded-lg">
                      <div>
                        <h3 className="text-sm font-medium text-[#2c2c2c]">Customer Notifications</h3>
                        <p className="text-sm text-[#746354]">Send notifications to customers</p>
                      </div>
                      <label className="relative inline-flex items-center cursor-pointer">
                        <input
                          type="checkbox"
                          className="sr-only peer"
                          checked={notificationSettings.customer_notifications}
                          onChange={(e) => setNotificationSettings({
                            ...notificationSettings,
                            customer_notifications: e.target.checked
                          })}
                        />
                        <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#e41e5b]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#e41e5b]"></div>
                      </label>
                    </div>
                  </div>
                  <div className="flex justify-end">
                    <button
                      type="submit"
                      disabled={loading}
                      className="flex items-center px-6 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors disabled:opacity-50"
                    >
                      <FiSave className="h-4 w-4 mr-2" />
                      {loading ? 'Saving...' : 'Save Changes'}
                    </button>
                  </div>
                </form>
              </div>
            )}

            {/* Security Settings */}
            {activeTab === 'security' && (
              <div>
                <h2 className="text-xl font-semibold text-[#2c2c2c] mb-6">Security Settings</h2>
                <div className="space-y-6">
                  <div className="p-4 border border-[#746354]/20 rounded-lg">
                    <h3 className="text-sm font-medium text-[#2c2c2c] mb-2">Change Password</h3>
                    <p className="text-sm text-[#746354] mb-4">Update your account password for enhanced security</p>
                    <button className="px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors">
                      Change Password
                    </button>
                  </div>
                  <div className="p-4 border border-[#746354]/20 rounded-lg">
                    <h3 className="text-sm font-medium text-[#2c2c2c] mb-2">Two-Factor Authentication</h3>
                    <p className="text-sm text-[#746354] mb-4">Add an extra layer of security to your account</p>
                    <button className="px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors">
                      Enable 2FA
                    </button>
                  </div>
                  <div className="p-4 border border-red-200 rounded-lg bg-red-50">
                    <h3 className="text-sm font-medium text-red-800 mb-2">Danger Zone</h3>
                    <p className="text-sm text-red-600 mb-4">Permanently delete your account and all associated data</p>
                    <button className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                      Delete Account
                    </button>
                  </div>
                </div>
              </div>
            )}

            {/* Payment Settings */}
            {activeTab === 'payments' && (
              <div>
                <h2 className="text-xl font-semibold text-[#2c2c2c] mb-6">Payment Settings</h2>
                <div className="space-y-6">
                  <div className="p-4 border border-[#746354]/20 rounded-lg">
                    <h3 className="text-sm font-medium text-[#2c2c2c] mb-2">Payment Methods</h3>
                    <p className="text-sm text-[#746354] mb-4">Configure your preferred payment methods</p>
                    <button className="px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors">
                      Add Payment Method
                    </button>
                  </div>
                  <div className="p-4 border border-[#746354]/20 rounded-lg">
                    <h3 className="text-sm font-medium text-[#2c2c2c] mb-2">Billing Information</h3>
                    <p className="text-sm text-[#746354] mb-4">Update your billing address and payment details</p>
                    <button className="px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors">
                      Update Billing
                    </button>
                  </div>
                </div>
              </div>
            )}

            {/* Integrations */}
            {activeTab === 'integrations' && (
              <div>
                <h2 className="text-xl font-semibold text-[#2c2c2c] mb-6">Integrations</h2>
                <div className="space-y-6">
                  <div className="p-4 border border-[#746354]/20 rounded-lg">
                    <h3 className="text-sm font-medium text-[#2c2c2c] mb-2">Paystack Integration</h3>
                    <p className="text-sm text-[#746354] mb-4">Connect your Paystack account for online payments</p>
                    <button className="px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors">
                      Connect Paystack
                    </button>
                  </div>
                  <div className="p-4 border border-[#746354]/20 rounded-lg">
                    <h3 className="text-sm font-medium text-[#2c2c2c] mb-2">Email Integration</h3>
                    <p className="text-sm text-[#746354] mb-4">Connect your email service for notifications</p>
                    <button className="px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors">
                      Connect Email
                    </button>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default SettingsPage;
