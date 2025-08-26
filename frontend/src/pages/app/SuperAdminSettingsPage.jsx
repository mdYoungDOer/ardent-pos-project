import React, { useState, useEffect } from 'react';
import {
  FiSettings, FiSave, FiRefreshCw, FiAlertCircle, FiCheckCircle,
  FiShield, FiMail, FiGlobe, FiCreditCard, FiDatabase, FiServer,
  FiUsers, FiLock, FiEye, FiEyeOff, FiPlus, FiTrash, FiEdit,
  FiDownload, FiUpload, FiActivity, FiTrendingUp, FiDollarSign
} from 'react-icons/fi';
import useSuperAdminAuthStore from '../../stores/superAdminAuthStore';
import { superAdminAPI } from '../../services/api';

const SuperAdminSettingsPage = () => {
  const { user } = useSuperAdminAuthStore();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [activeTab, setActiveTab] = useState('general');
  const [settings, setSettings] = useState({
    general: {
      site_name: 'Ardent POS',
      site_description: 'Enterprise Point of Sale System',
      timezone: 'UTC',
      date_format: 'Y-m-d',
      time_format: 'H:i:s',
      maintenance_mode: false
    },
    email: {
      smtp_host: '',
      smtp_port: '587',
      smtp_username: '',
      smtp_password: '',
      from_email: 'noreply@ardentpos.com',
      from_name: 'Ardent POS',
      email_verification: true
    },
    security: {
      session_timeout: 3600,
      max_login_attempts: 5,
      password_min_length: 8,
      require_2fa: false,
      force_ssl: true,
      rate_limiting: true
    },
    billing: {
      currency: 'GHS',
      tax_rate: 0.125,
      auto_renewal: true,
      grace_period_days: 7,
      late_fee_percentage: 5
    },
    subscription_plans: [
      {
        id: 'starter',
        name: 'Starter',
        description: 'Perfect for small businesses just getting started',
        monthly_price: 120,
        yearly_price: 1200,
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
        monthly_price: 240,
        yearly_price: 2400,
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
        monthly_price: 480,
        yearly_price: 4800,
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
    ]
  });

  useEffect(() => {
    fetchSettings();
  }, []);

  const fetchSettings = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await superAdminAPI.getSystemSettings();
      if (response.data.success) {
        setSettings(response.data.data);
      }
    } catch (error) {
      console.error('Error fetching settings:', error);
      setError('Failed to load settings');
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async (category) => {
    setSaving(true);
    setError(null);
    setSuccess(null);
    
    try {
      const response = await superAdminAPI.updateSystemSettings(category, settings[category]);
      if (response.data.success) {
        setSuccess(`${category.charAt(0).toUpperCase() + category.slice(1)} settings updated successfully`);
        setTimeout(() => setSuccess(null), 3000);
      } else {
        setError('Failed to update settings');
      }
    } catch (error) {
      console.error('Error updating settings:', error);
      setError('Failed to update settings');
    } finally {
      setSaving(false);
    }
  };

  const handleInputChange = (category, field, value) => {
    setSettings(prev => ({
      ...prev,
      [category]: {
        ...prev[category],
        [field]: value
      }
    }));
  };

  const handlePlanChange = (planId, field, value) => {
    setSettings(prev => ({
      ...prev,
      subscription_plans: prev.subscription_plans.map(plan => 
        plan.id === planId ? { ...plan, [field]: value } : plan
      )
    }));
  };

  const addFeature = (planId) => {
    const newFeature = prompt('Enter new feature:');
    if (newFeature) {
      setSettings(prev => ({
        ...prev,
        subscription_plans: prev.subscription_plans.map(plan => 
          plan.id === planId 
            ? { ...plan, features: [...plan.features, newFeature] }
            : plan
        )
      }));
    }
  };

  const removeFeature = (planId, featureIndex) => {
    setSettings(prev => ({
      ...prev,
      subscription_plans: prev.subscription_plans.map(plan => 
        plan.id === planId 
          ? { ...plan, features: plan.features.filter((_, index) => index !== featureIndex) }
          : plan
      )
    }));
  };

  if (!user) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#e41e5b] mx-auto mb-4"></div>
          <p className="text-gray-600">Loading...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-900 flex items-center">
                <FiSettings className="h-8 w-8 mr-3 text-[#e41e5b]" />
                System Settings
              </h1>
              <p className="mt-2 text-gray-600">Manage system configuration and preferences</p>
            </div>
            <div className="flex items-center space-x-4">
              <button 
                onClick={fetchSettings}
                className="flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
              >
                <FiRefreshCw className="h-4 w-4 mr-2" />
                Refresh
              </button>
            </div>
          </div>
        </div>

        {/* Error/Success Messages */}
        {error && (
          <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div className="flex items-center">
              <FiAlertCircle className="h-5 w-5 text-red-500 mr-2" />
              <span className="text-red-800">{error}</span>
            </div>
          </div>
        )}

        {success && (
          <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <div className="flex items-center">
              <FiCheckCircle className="h-5 w-5 text-green-500 mr-2" />
              <span className="text-green-800">{success}</span>
            </div>
          </div>
        )}

        {/* Tab Navigation */}
        <div className="mb-8">
          <nav className="flex space-x-8">
            {[
              { id: 'general', name: 'General', icon: FiGlobe },
              { id: 'email', name: 'Email', icon: FiMail },
              { id: 'security', name: 'Security', icon: FiShield },
              { id: 'billing', name: 'Billing', icon: FiCreditCard },
              { id: 'subscription_plans', name: 'Subscription Plans', icon: FiDollarSign }
            ].map((tab) => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                  activeTab === tab.id
                    ? 'bg-[#e41e5b] text-white'
                    : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'
                }`}
              >
                <tab.icon className="h-4 w-4 mr-2" />
                {tab.name}
              </button>
            ))}
          </nav>
        </div>

        {/* Loading State */}
        {loading ? (
          <div className="text-center py-12">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#e41e5b] mx-auto"></div>
            <p className="mt-4 text-gray-600">Loading settings...</p>
          </div>
        ) : (
          <div className="bg-white rounded-xl shadow-sm border border-gray-200">
            {/* General Settings */}
            {activeTab === 'general' && (
              <div className="p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-6">General Settings</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Site Name
                    </label>
                    <input
                      type="text"
                      value={settings.general.site_name}
                      onChange={(e) => handleInputChange('general', 'site_name', e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Site Description
                    </label>
                    <input
                      type="text"
                      value={settings.general.site_description}
                      onChange={(e) => handleInputChange('general', 'site_description', e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Timezone
                    </label>
                    <select
                      value={settings.general.timezone}
                      onChange={(e) => handleInputChange('general', 'timezone', e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    >
                      <option value="UTC">UTC</option>
                      <option value="Africa/Accra">Africa/Accra</option>
                      <option value="America/New_York">America/New_York</option>
                      <option value="Europe/London">Europe/London</option>
                    </select>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Date Format
                    </label>
                    <select
                      value={settings.general.date_format}
                      onChange={(e) => handleInputChange('general', 'date_format', e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    >
                      <option value="Y-m-d">YYYY-MM-DD</option>
                      <option value="d/m/Y">DD/MM/YYYY</option>
                      <option value="m/d/Y">MM/DD/YYYY</option>
                    </select>
                  </div>
                  <div className="md:col-span-2">
                    <label className="flex items-center">
                      <input
                        type="checkbox"
                        checked={settings.general.maintenance_mode}
                        onChange={(e) => handleInputChange('general', 'maintenance_mode', e.target.checked)}
                        className="h-4 w-4 text-[#e41e5b] focus:ring-[#e41e5b] border-gray-300 rounded"
                      />
                      <span className="ml-2 text-sm text-gray-700">Maintenance Mode</span>
                    </label>
                  </div>
                </div>
                <div className="mt-6">
                  <button
                    onClick={() => handleSave('general')}
                    disabled={saving}
                    className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors disabled:opacity-50"
                  >
                    <FiSave className="h-4 w-4 mr-2" />
                    {saving ? 'Saving...' : 'Save General Settings'}
                  </button>
                </div>
              </div>
            )}

            {/* Email Settings */}
            {activeTab === 'email' && (
              <div className="p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-6">Email Configuration</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      SMTP Host
                    </label>
                    <input
                      type="text"
                      value={settings.email.smtp_host}
                      onChange={(e) => handleInputChange('email', 'smtp_host', e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                      placeholder="smtp.gmail.com"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      SMTP Port
                    </label>
                    <input
                      type="number"
                      value={settings.email.smtp_port}
                      onChange={(e) => handleInputChange('email', 'smtp_port', e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      SMTP Username
                    </label>
                    <input
                      type="text"
                      value={settings.email.smtp_username}
                      onChange={(e) => handleInputChange('email', 'smtp_username', e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      SMTP Password
                    </label>
                    <input
                      type="password"
                      value={settings.email.smtp_password}
                      onChange={(e) => handleInputChange('email', 'smtp_password', e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      From Email
                    </label>
                    <input
                      type="email"
                      value={settings.email.from_email}
                      onChange={(e) => handleInputChange('email', 'from_email', e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      From Name
                    </label>
                    <input
                      type="text"
                      value={settings.email.from_name}
                      onChange={(e) => handleInputChange('email', 'from_name', e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>
                  <div className="md:col-span-2">
                    <label className="flex items-center">
                      <input
                        type="checkbox"
                        checked={settings.email.email_verification}
                        onChange={(e) => handleInputChange('email', 'email_verification', e.target.checked)}
                        className="h-4 w-4 text-[#e41e5b] focus:ring-[#e41e5b] border-gray-300 rounded"
                      />
                      <span className="ml-2 text-sm text-gray-700">Require Email Verification</span>
                    </label>
                  </div>
                </div>
                <div className="mt-6">
                  <button
                    onClick={() => handleSave('email')}
                    disabled={saving}
                    className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors disabled:opacity-50"
                  >
                    <FiSave className="h-4 w-4 mr-2" />
                    {saving ? 'Saving...' : 'Save Email Settings'}
                  </button>
                </div>
              </div>
            )}

            {/* Security Settings */}
            {activeTab === 'security' && (
              <div className="p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-6">Security Settings</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Session Timeout (seconds)
                    </label>
                    <input
                      type="number"
                      value={settings.security.session_timeout}
                      onChange={(e) => handleInputChange('security', 'session_timeout', parseInt(e.target.value))}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Max Login Attempts
                    </label>
                    <input
                      type="number"
                      value={settings.security.max_login_attempts}
                      onChange={(e) => handleInputChange('security', 'max_login_attempts', parseInt(e.target.value))}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Minimum Password Length
                    </label>
                    <input
                      type="number"
                      value={settings.security.password_min_length}
                      onChange={(e) => handleInputChange('security', 'password_min_length', parseInt(e.target.value))}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>
                  <div className="space-y-4">
                    <label className="flex items-center">
                      <input
                        type="checkbox"
                        checked={settings.security.require_2fa}
                        onChange={(e) => handleInputChange('security', 'require_2fa', e.target.checked)}
                        className="h-4 w-4 text-[#e41e5b] focus:ring-[#e41e5b] border-gray-300 rounded"
                      />
                      <span className="ml-2 text-sm text-gray-700">Require Two-Factor Authentication</span>
                    </label>
                    <label className="flex items-center">
                      <input
                        type="checkbox"
                        checked={settings.security.force_ssl}
                        onChange={(e) => handleInputChange('security', 'force_ssl', e.target.checked)}
                        className="h-4 w-4 text-[#e41e5b] focus:ring-[#e41e5b] border-gray-300 rounded"
                      />
                      <span className="ml-2 text-sm text-gray-700">Force SSL/HTTPS</span>
                    </label>
                    <label className="flex items-center">
                      <input
                        type="checkbox"
                        checked={settings.security.rate_limiting}
                        onChange={(e) => handleInputChange('security', 'rate_limiting', e.target.checked)}
                        className="h-4 w-4 text-[#e41e5b] focus:ring-[#e41e5b] border-gray-300 rounded"
                      />
                      <span className="ml-2 text-sm text-gray-700">Enable Rate Limiting</span>
                    </label>
                  </div>
                </div>
                <div className="mt-6">
                  <button
                    onClick={() => handleSave('security')}
                    disabled={saving}
                    className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors disabled:opacity-50"
                  >
                    <FiSave className="h-4 w-4 mr-2" />
                    {saving ? 'Saving...' : 'Save Security Settings'}
                  </button>
                </div>
              </div>
            )}

            {/* Billing Settings */}
            {activeTab === 'billing' && (
              <div className="p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-6">Billing Configuration</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Currency
                    </label>
                    <select
                      value={settings.billing.currency}
                      onChange={(e) => handleInputChange('billing', 'currency', e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    >
                      <option value="GHS">GHS (Ghanaian Cedi)</option>
                      <option value="USD">USD (US Dollar)</option>
                      <option value="EUR">EUR (Euro)</option>
                      <option value="GBP">GBP (British Pound)</option>
                    </select>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Tax Rate (%)
                    </label>
                    <input
                      type="number"
                      step="0.01"
                      value={settings.billing.tax_rate * 100}
                      onChange={(e) => handleInputChange('billing', 'tax_rate', parseFloat(e.target.value) / 100)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Grace Period (days)
                    </label>
                    <input
                      type="number"
                      value={settings.billing.grace_period_days}
                      onChange={(e) => handleInputChange('billing', 'grace_period_days', parseInt(e.target.value))}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Late Fee Percentage (%)
                    </label>
                    <input
                      type="number"
                      step="0.01"
                      value={settings.billing.late_fee_percentage}
                      onChange={(e) => handleInputChange('billing', 'late_fee_percentage', parseFloat(e.target.value))}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    />
                  </div>
                  <div className="md:col-span-2">
                    <label className="flex items-center">
                      <input
                        type="checkbox"
                        checked={settings.billing.auto_renewal}
                        onChange={(e) => handleInputChange('billing', 'auto_renewal', e.target.checked)}
                        className="h-4 w-4 text-[#e41e5b] focus:ring-[#e41e5b] border-gray-300 rounded"
                      />
                      <span className="ml-2 text-sm text-gray-700">Enable Auto-Renewal</span>
                    </label>
                  </div>
                </div>
                <div className="mt-6">
                  <button
                    onClick={() => handleSave('billing')}
                    disabled={saving}
                    className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors disabled:opacity-50"
                  >
                    <FiSave className="h-4 w-4 mr-2" />
                    {saving ? 'Saving...' : 'Save Billing Settings'}
                  </button>
                </div>
              </div>
            )}

            {/* Subscription Plans */}
            {activeTab === 'subscription_plans' && (
              <div className="p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-6">Subscription Plans</h3>
                <div className="space-y-8">
                  {settings.subscription_plans.map((plan) => (
                    <div key={plan.id} className="border border-gray-200 rounded-lg p-6">
                      <div className="flex items-center justify-between mb-4">
                        <h4 className="text-lg font-semibold text-gray-900">{plan.name}</h4>
                        <div className="flex items-center space-x-2">
                          <label className="flex items-center">
                            <input
                              type="checkbox"
                              checked={plan.popular}
                              onChange={(e) => handlePlanChange(plan.id, 'popular', e.target.checked)}
                              className="h-4 w-4 text-[#e41e5b] focus:ring-[#e41e5b] border-gray-300 rounded"
                            />
                            <span className="ml-2 text-sm text-gray-700">Popular Plan</span>
                          </label>
                        </div>
                      </div>
                      
                      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-2">
                            Monthly Price
                          </label>
                          <input
                            type="number"
                            value={plan.monthly_price}
                            onChange={(e) => handlePlanChange(plan.id, 'monthly_price', parseFloat(e.target.value))}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                          />
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-2">
                            Yearly Price
                          </label>
                          <input
                            type="number"
                            value={plan.yearly_price}
                            onChange={(e) => handlePlanChange(plan.id, 'yearly_price', parseFloat(e.target.value))}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                          />
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-2">
                            Description
                          </label>
                          <input
                            type="text"
                            value={plan.description}
                            onChange={(e) => handlePlanChange(plan.id, 'description', e.target.value)}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                          />
                        </div>
                      </div>

                      <div className="mt-6">
                        <h5 className="text-md font-medium text-gray-900 mb-3">Features</h5>
                        <div className="space-y-2">
                          {plan.features.map((feature, index) => (
                            <div key={index} className="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                              <span className="text-sm text-gray-700">{feature}</span>
                              <button
                                onClick={() => removeFeature(plan.id, index)}
                                className="text-red-600 hover:text-red-800"
                              >
                                <FiTrash className="h-4 w-4" />
                              </button>
                            </div>
                          ))}
                          <button
                            onClick={() => addFeature(plan.id)}
                            className="flex items-center text-[#e41e5b] hover:text-[#9a0864] text-sm"
                          >
                            <FiPlus className="h-4 w-4 mr-1" />
                            Add Feature
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
                <div className="mt-6">
                  <button
                    onClick={() => handleSave('subscription_plans')}
                    disabled={saving}
                    className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors disabled:opacity-50"
                  >
                    <FiSave className="h-4 w-4 mr-2" />
                    {saving ? 'Saving...' : 'Save Subscription Plans'}
                  </button>
                </div>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default SuperAdminSettingsPage;
