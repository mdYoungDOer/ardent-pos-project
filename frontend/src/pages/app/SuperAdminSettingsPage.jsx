import React, { useState, useEffect } from 'react';
import { FiSave, FiRotateCw, FiMail, FiCreditCard, FiShield, FiBell, FiGlobe } from 'react-icons/fi';
import { superAdminAPI } from '../../services/api';

const SuperAdminSettingsPage = () => {
  const [activeTab, setActiveTab] = useState('general');
  const [settings, setSettings] = useState({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');

  useEffect(() => {
    loadSettings();
  }, []);

  const loadSettings = async () => {
    try {
      setLoading(true);
      const response = await superAdminAPI.getSystemSettings();
      if (response.data?.success) {
        setSettings(response.data.data);
      }
    } catch (error) {
      console.error('Error loading settings:', error);
      setMessage('Error loading settings');
    } finally {
      setLoading(false);
    }
  };

  const handleSettingChange = (category, key, value) => {
    setSettings(prev => ({
      ...prev,
      [category]: {
        ...prev[category],
        [key]: value
      }
    }));
  };

  const saveSettings = async (category) => {
    try {
      setSaving(true);
      const categorySettings = settings[category];
      
      // Transform settings to the format expected by the API
      const settingsToSave = {};
      Object.keys(categorySettings).forEach(key => {
        settingsToSave[`${category}_${key}`] = categorySettings[key];
      });

      const response = await superAdminAPI.updateSettings(category, settingsToSave);
      if (response.data?.success) {
        setMessage(`${category.charAt(0).toUpperCase() + category.slice(1)} settings saved successfully!`);
        setTimeout(() => setMessage(''), 3000);
      }
    } catch (error) {
      console.error('Error saving settings:', error);
      setMessage('Error saving settings');
      setTimeout(() => setMessage(''), 3000);
    } finally {
      setSaving(false);
    }
  };

  const tabs = [
    { id: 'general', name: 'General', icon: FiGlobe },
    { id: 'email', name: 'Email (SendGrid)', icon: FiMail },
    { id: 'payment', name: 'Payment (Paystack)', icon: FiCreditCard },
    { id: 'security', name: 'Security', icon: FiShield },
    { id: 'notifications', name: 'Notifications', icon: FiBell }
  ];

  const renderGeneralSettings = () => (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Site Name
          </label>
          <input
            type="text"
            value={settings.general?.site_name || ''}
            onChange={(e) => handleSettingChange('general', 'site_name', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="Ardent POS"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Site Description
          </label>
          <input
            type="text"
            value={settings.general?.site_description || ''}
            onChange={(e) => handleSettingChange('general', 'site_description', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="Enterprise Point of Sale System"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Timezone
          </label>
          <select
            value={settings.general?.timezone || 'UTC'}
            onChange={(e) => handleSettingChange('general', 'timezone', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
          >
            <option value="UTC">UTC</option>
            <option value="Africa/Accra">Africa/Accra (GMT+0)</option>
            <option value="America/New_York">America/New_York (EST)</option>
            <option value="Europe/London">Europe/London (GMT)</option>
          </select>
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Maintenance Mode
          </label>
          <div className="flex items-center">
            <input
              type="checkbox"
              checked={settings.general?.maintenance_mode || false}
              onChange={(e) => handleSettingChange('general', 'maintenance_mode', e.target.checked)}
              className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
            />
            <span className="ml-2 text-sm text-gray-600">Enable maintenance mode</span>
          </div>
        </div>
      </div>
    </div>
  );

  const renderEmailSettings = () => (
    <div className="space-y-6">
      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h4 className="text-sm font-medium text-blue-800 mb-2">SendGrid Configuration</h4>
        <p className="text-sm text-blue-600">
          Configure SendGrid SMTP settings for email delivery. You can find your API key in your SendGrid dashboard.
        </p>
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            SMTP Host
          </label>
          <input
            type="text"
            value={settings.email?.smtp_host || 'smtp.sendgrid.net'}
            onChange={(e) => handleSettingChange('email', 'smtp_host', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="smtp.sendgrid.net"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            SMTP Port
          </label>
          <input
            type="number"
            value={settings.email?.smtp_port || '587'}
            onChange={(e) => handleSettingChange('email', 'smtp_port', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="587"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            SendGrid API Key
          </label>
          <input
            type="password"
            value={settings.email?.smtp_username || ''}
            onChange={(e) => handleSettingChange('email', 'smtp_username', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="SG.your_api_key_here"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            From Email
          </label>
          <input
            type="email"
            value={settings.email?.from_email || ''}
            onChange={(e) => handleSettingChange('email', 'from_email', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="noreply@ardentpos.com"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            From Name
          </label>
          <input
            type="text"
            value={settings.email?.from_name || ''}
            onChange={(e) => handleSettingChange('email', 'from_name', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="Ardent POS"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Email Verification
          </label>
          <div className="flex items-center">
            <input
              type="checkbox"
              checked={settings.email?.email_verification || false}
              onChange={(e) => handleSettingChange('email', 'email_verification', e.target.checked)}
              className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
            />
            <span className="ml-2 text-sm text-gray-600">Require email verification</span>
          </div>
        </div>
      </div>
    </div>
  );

  const renderPaymentSettings = () => (
    <div className="space-y-6">
      <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
        <h4 className="text-sm font-medium text-green-800 mb-2">Paystack Configuration</h4>
        <p className="text-sm text-green-600">
          Configure Paystack payment gateway settings. You can find your API keys in your Paystack dashboard.
        </p>
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Public Key
          </label>
          <input
            type="text"
            value={settings.payment?.paystack_public_key || ''}
            onChange={(e) => handleSettingChange('payment', 'paystack_public_key', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="pk_test_..."
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Secret Key
          </label>
          <input
            type="password"
            value={settings.payment?.paystack_secret_key || ''}
            onChange={(e) => handleSettingChange('payment', 'paystack_secret_key', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="sk_test_..."
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Webhook Secret
          </label>
          <input
            type="password"
            value={settings.payment?.paystack_webhook_secret || ''}
            onChange={(e) => handleSettingChange('payment', 'paystack_webhook_secret', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="whsec_..."
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Currency
          </label>
          <select
            value={settings.payment?.currency || 'GHS'}
            onChange={(e) => handleSettingChange('payment', 'currency', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
          >
            <option value="GHS">GHS (Ghanaian Cedi)</option>
            <option value="NGN">NGN (Nigerian Naira)</option>
            <option value="USD">USD (US Dollar)</option>
            <option value="EUR">EUR (Euro)</option>
          </select>
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Currency Symbol
          </label>
          <input
            type="text"
            value={settings.payment?.currency_symbol || '₵'}
            onChange={(e) => handleSettingChange('payment', 'currency_symbol', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="₵"
          />
        </div>
      </div>
    </div>
  );

  const renderSecuritySettings = () => (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Session Timeout (seconds)
          </label>
          <input
            type="number"
            value={settings.security?.session_timeout || 3600}
            onChange={(e) => handleSettingChange('security', 'session_timeout', parseInt(e.target.value))}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            min="300"
            max="86400"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Max Login Attempts
          </label>
          <input
            type="number"
            value={settings.security?.max_login_attempts || 5}
            onChange={(e) => handleSettingChange('security', 'max_login_attempts', parseInt(e.target.value))}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            min="1"
            max="10"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Minimum Password Length
          </label>
          <input
            type="number"
            value={settings.security?.password_min_length || 8}
            onChange={(e) => handleSettingChange('security', 'password_min_length', parseInt(e.target.value))}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            min="6"
            max="20"
          />
        </div>
        <div className="space-y-4">
          <div className="flex items-center">
            <input
              type="checkbox"
              checked={settings.security?.require_2fa || false}
              onChange={(e) => handleSettingChange('security', 'require_2fa', e.target.checked)}
              className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
            />
            <span className="ml-2 text-sm text-gray-600">Require Two-Factor Authentication</span>
          </div>
          <div className="flex items-center">
            <input
              type="checkbox"
              checked={settings.security?.password_require_special || false}
              onChange={(e) => handleSettingChange('security', 'password_require_special', e.target.checked)}
              className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
            />
            <span className="ml-2 text-sm text-gray-600">Require Special Characters in Passwords</span>
          </div>
        </div>
      </div>
    </div>
  );

  const renderNotificationSettings = () => (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="flex items-center">
          <input
            type="checkbox"
            checked={settings.notifications?.email_notifications || false}
            onChange={(e) => handleSettingChange('notifications', 'email_notifications', e.target.checked)}
            className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
          />
          <span className="ml-2 text-sm text-gray-600">Enable Email Notifications</span>
        </div>
        <div className="flex items-center">
          <input
            type="checkbox"
            checked={settings.notifications?.push_notifications || false}
            onChange={(e) => handleSettingChange('notifications', 'push_notifications', e.target.checked)}
            className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
          />
          <span className="ml-2 text-sm text-gray-600">Enable Push Notifications</span>
        </div>
        <div className="flex items-center">
          <input
            type="checkbox"
            checked={settings.notifications?.sms_notifications || false}
            onChange={(e) => handleSettingChange('notifications', 'sms_notifications', e.target.checked)}
            className="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
          />
          <span className="ml-2 text-sm text-gray-600">Enable SMS Notifications</span>
        </div>
      </div>
    </div>
  );

  const renderTabContent = () => {
    switch (activeTab) {
      case 'general':
        return renderGeneralSettings();
      case 'email':
        return renderEmailSettings();
      case 'payment':
        return renderPaymentSettings();
      case 'security':
        return renderSecuritySettings();
      case 'notifications':
        return renderNotificationSettings();
      default:
        return renderGeneralSettings();
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900">System Settings</h1>
        <p className="mt-2 text-gray-600">Manage global system configuration and integrations</p>
      </div>

      {message && (
        <div className={`mb-6 p-4 rounded-md ${
          message.includes('Error') ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200'
        }`}>
          {message}
        </div>
      )}

      <div className="bg-white rounded-lg shadow">
        {/* Tabs */}
        <div className="border-b border-gray-200">
          <nav className="-mb-px flex space-x-8 px-6">
            {tabs.map((tab) => {
              const Icon = tab.icon;
              return (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={`py-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2 ${
                    activeTab === tab.id
                      ? 'border-primary text-primary'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  }`}
                >
                  <Icon className="h-4 w-4" />
                  <span>{tab.name}</span>
                </button>
              );
            })}
          </nav>
        </div>

        {/* Tab Content */}
        <div className="p-6">
          {renderTabContent()}
          
          {/* Save Button */}
          <div className="mt-8 flex justify-end space-x-4">
            <button
              onClick={() => loadSettings()}
              className="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
            >
                              <FiRotateCw className="h-4 w-4 inline mr-2" />
              Refresh
            </button>
            <button
              onClick={() => saveSettings(activeTab)}
              disabled={saving}
              className="px-4 py-2 bg-primary text-white rounded-md text-sm font-medium hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50"
            >
              {saving ? (
                <>
                  <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white inline mr-2"></div>
                  Saving...
                </>
              ) : (
                <>
                  <FiSave className="h-4 w-4 inline mr-2" />
                  Save {tabs.find(t => t.id === activeTab)?.name} Settings
                </>
              )}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SuperAdminSettingsPage;
