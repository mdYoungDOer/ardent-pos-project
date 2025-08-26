import React, { useState, useEffect } from 'react';
import {
  FiSettings, FiShield, FiDatabase, FiServer, FiKey, FiGlobe,
  FiMail, FiCreditCard, FiAlertTriangle, FiCheck, FiX, FiSave,
  FiRefreshCw, FiDownload, FiUpload, FiTrash2, FiEdit, FiEye,
  FiEyeOff, FiLock, FiUnlock, FiActivity, FiClock, FiCalendar,
  FiHardDrive, FiCpu, FiWifi, FiBell, FiUserCheck, FiUserX
} from 'react-icons/fi';
import { superAdminAPI } from '../../services/api';

const SuperAdminSettingsPage = () => {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [settings, setSettings] = useState({});
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [activeTab, setActiveTab] = useState('general');
  const [showApiKey, setShowApiKey] = useState(false);
  const [backupStatus, setBackupStatus] = useState('idle');
  const [maintenanceMode, setMaintenanceMode] = useState(false);

  const tabs = [
    { id: 'general', name: 'General', icon: FiSettings },
    { id: 'security', name: 'Security', icon: FiShield },
    { id: 'email', name: 'Email', icon: FiMail },
    { id: 'payments', name: 'Payments', icon: FiCreditCard },
    { id: 'api', name: 'API Keys', icon: FiKey },
    { id: 'maintenance', name: 'Maintenance', icon: FiServer },
    { id: 'backup', name: 'Backup', icon: FiDatabase }
  ];

  const fetchSettings = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await superAdminAPI.getSettings();
      if (response.data.success) {
        setSettings(response.data.data);
      } else {
        setError('Failed to load settings');
      }
    } catch (err) {
      setError('Error loading settings');
      console.error('Settings error:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchSettings();
  }, []);

  const handleSettingChange = (category, key, value) => {
    setSettings(prev => ({
      ...prev,
      [category]: {
        ...prev[category],
        [key]: value
      }
    }));
  };

  const handleSaveSettings = async (category) => {
    setSaving(true);
    setError(null);
    setSuccess(null);
    
    try {
      const response = await superAdminAPI.updateSettings(category, settings[category]);
      if (response.data.success) {
        setSuccess(`${category} settings updated successfully`);
        setTimeout(() => setSuccess(null), 3000);
      } else {
        setError(`Failed to update ${category} settings`);
      }
    } catch (err) {
      setError(`Error updating ${category} settings`);
      console.error('Save settings error:', err);
    } finally {
      setSaving(false);
    }
  };

  const handleBackup = async () => {
    setBackupStatus('running');
    try {
      const response = await superAdminAPI.createBackup();
      if (response.data.success) {
        setBackupStatus('completed');
        setSuccess('Backup created successfully');
        setTimeout(() => setSuccess(null), 3000);
      } else {
        setBackupStatus('failed');
        setError('Failed to create backup');
      }
    } catch (err) {
      setBackupStatus('failed');
      setError('Error creating backup');
      console.error('Backup error:', err);
    }
  };

  const handleMaintenanceToggle = async () => {
    try {
      const response = await superAdminAPI.toggleMaintenanceMode(!maintenanceMode);
      if (response.data.success) {
        setMaintenanceMode(!maintenanceMode);
        setSuccess(`Maintenance mode ${!maintenanceMode ? 'enabled' : 'disabled'}`);
        setTimeout(() => setSuccess(null), 3000);
      } else {
        setError('Failed to toggle maintenance mode');
      }
    } catch (err) {
      setError('Error toggling maintenance mode');
      console.error('Maintenance toggle error:', err);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#e41e5b]"></div>
        <span className="ml-3 text-[#746354]">Loading Settings...</span>
      </div>
    );
  }

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      {/* Header */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-[#2c2c2c]">System Settings</h1>
            <p className="text-[#746354] mt-1">
              Manage system-wide configurations and settings
            </p>
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

      {/* Success/Error Messages */}
      {success && (
        <div className="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
          <div className="flex items-center">
            <FiCheck className="h-5 w-5 text-green-500 mr-2" />
            <span className="text-green-800">{success}</span>
            <button
              onClick={() => setSuccess(null)}
              className="ml-auto text-green-500 hover:text-green-700"
            >
              ×
            </button>
          </div>
        </div>
      )}

      {error && (
        <div className="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
          <div className="flex items-center">
            <FiAlertTriangle className="h-5 w-5 text-red-500 mr-2" />
            <span className="text-red-800">{error}</span>
            <button
              onClick={() => setError(null)}
              className="ml-auto text-red-500 hover:text-red-700"
            >
              ×
            </button>
          </div>
        </div>
      )}

      {/* Tabs */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div className="border-b border-gray-200">
          <nav className="-mb-px flex space-x-8 px-6">
            {tabs.map((tab) => {
              const Icon = tab.icon;
              return (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={`py-4 px-1 border-b-2 font-medium text-sm flex items-center ${
                    activeTab === tab.id
                      ? 'border-[#e41e5b] text-[#e41e5b]'
                      : 'border-transparent text-[#746354] hover:text-[#2c2c2c] hover:border-gray-300'
                  }`}
                >
                  <Icon className="h-4 w-4 mr-2" />
                  {tab.name}
                </button>
              );
            })}
          </nav>
        </div>

        {/* Tab Content */}
        <div className="p-6">
          {activeTab === 'general' && (
            <div className="space-y-6">
              <h3 className="text-lg font-semibold text-[#2c2c2c]">General Settings</h3>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm font-medium text-[#746354] mb-2">
                    Application Name
                  </label>
                  <input
                    type="text"
                    value={settings.general?.app_name || 'Ardent POS'}
                    onChange={(e) => handleSettingChange('general', 'app_name', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-[#746354] mb-2">
                    Default Currency
                  </label>
                  <select
                    value={settings.general?.default_currency || 'GHS'}
                    onChange={(e) => handleSettingChange('general', 'default_currency', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                  >
                    <option value="GHS">Ghanaian Cedi (₵)</option>
                    <option value="USD">US Dollar ($)</option>
                    <option value="EUR">Euro (€)</option>
                    <option value="GBP">British Pound (£)</option>
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-[#746354] mb-2">
                    Time Zone
                  </label>
                  <select
                    value={settings.general?.timezone || 'Africa/Accra'}
                    onChange={(e) => handleSettingChange('general', 'timezone', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                  >
                    <option value="Africa/Accra">Africa/Accra (GMT+0)</option>
                    <option value="UTC">UTC (GMT+0)</option>
                    <option value="America/New_York">America/New_York (GMT-5)</option>
                    <option value="Europe/London">Europe/London (GMT+0)</option>
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-[#746354] mb-2">
                    Date Format
                  </label>
                  <select
                    value={settings.general?.date_format || 'Y-m-d'}
                    onChange={(e) => handleSettingChange('general', 'date_format', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                  >
                    <option value="Y-m-d">YYYY-MM-DD</option>
                    <option value="d/m/Y">DD/MM/YYYY</option>
                    <option value="m/d/Y">MM/DD/YYYY</option>
                    <option value="d-m-Y">DD-MM-YYYY</option>
                  </select>
                </div>
              </div>

              <div className="flex justify-end">
                <button
                  onClick={() => handleSaveSettings('general')}
                  disabled={saving}
                  className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors disabled:opacity-50"
                >
                  {saving ? (
                    <FiRefreshCw className="h-4 w-4 mr-2 animate-spin" />
                  ) : (
                    <FiSave className="h-4 w-4 mr-2" />
                  )}
                  Save Changes
                </button>
              </div>
            </div>
          )}

          {activeTab === 'security' && (
            <div className="space-y-6">
              <h3 className="text-lg font-semibold text-[#2c2c2c]">Security Settings</h3>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm font-medium text-[#746354] mb-2">
                    Session Timeout (minutes)
                  </label>
                  <input
                    type="number"
                    value={settings.security?.session_timeout || 60}
                    onChange={(e) => handleSettingChange('security', 'session_timeout', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-[#746354] mb-2">
                    Password Minimum Length
                  </label>
                  <input
                    type="number"
                    value={settings.security?.password_min_length || 8}
                    onChange={(e) => handleSettingChange('security', 'password_min_length', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                  />
                </div>

                <div className="flex items-center">
                  <input
                    type="checkbox"
                    id="require_2fa"
                    checked={settings.security?.require_2fa || false}
                    onChange={(e) => handleSettingChange('security', 'require_2fa', e.target.checked)}
                    className="h-4 w-4 text-[#e41e5b] focus:ring-[#e41e5b] border-gray-300 rounded"
                  />
                  <label htmlFor="require_2fa" className="ml-2 block text-sm text-[#746354]">
                    Require Two-Factor Authentication
                  </label>
                </div>

                <div className="flex items-center">
                  <input
                    type="checkbox"
                    id="force_ssl"
                    checked={settings.security?.force_ssl || true}
                    onChange={(e) => handleSettingChange('security', 'force_ssl', e.target.checked)}
                    className="h-4 w-4 text-[#e41e5b] focus:ring-[#e41e5b] border-gray-300 rounded"
                  />
                  <label htmlFor="force_ssl" className="ml-2 block text-sm text-[#746354]">
                    Force HTTPS/SSL
                  </label>
                </div>
              </div>

              <div className="flex justify-end">
                <button
                  onClick={() => handleSaveSettings('security')}
                  disabled={saving}
                  className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors disabled:opacity-50"
                >
                  {saving ? (
                    <FiRefreshCw className="h-4 w-4 mr-2 animate-spin" />
                  ) : (
                    <FiSave className="h-4 w-4 mr-2" />
                  )}
                  Save Changes
                </button>
              </div>
            </div>
          )}

          {activeTab === 'email' && (
            <div className="space-y-6">
              <h3 className="text-lg font-semibold text-[#2c2c2c]">Email Configuration</h3>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm font-medium text-[#746354] mb-2">
                    SMTP Host
                  </label>
                  <input
                    type="text"
                    value={settings.email?.smtp_host || ''}
                    onChange={(e) => handleSettingChange('email', 'smtp_host', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    placeholder="smtp.sendgrid.net"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-[#746354] mb-2">
                    SMTP Port
                  </label>
                  <input
                    type="number"
                    value={settings.email?.smtp_port || 587}
                    onChange={(e) => handleSettingChange('email', 'smtp_port', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-[#746354] mb-2">
                    From Email
                  </label>
                  <input
                    type="email"
                    value={settings.email?.from_email || ''}
                    onChange={(e) => handleSettingChange('email', 'from_email', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    placeholder="noreply@ardentpos.com"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-[#746354] mb-2">
                    From Name
                  </label>
                  <input
                    type="text"
                    value={settings.email?.from_name || ''}
                    onChange={(e) => handleSettingChange('email', 'from_name', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    placeholder="Ardent POS"
                  />
                </div>
              </div>

              <div className="flex justify-end">
                <button
                  onClick={() => handleSaveSettings('email')}
                  disabled={saving}
                  className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors disabled:opacity-50"
                >
                  {saving ? (
                    <FiRefreshCw className="h-4 w-4 mr-2 animate-spin" />
                  ) : (
                    <FiSave className="h-4 w-4 mr-2" />
                  )}
                  Save Changes
                </button>
              </div>
            </div>
          )}

          {activeTab === 'payments' && (
            <div className="space-y-6">
              <h3 className="text-lg font-semibold text-[#2c2c2c]">Payment Gateway Settings</h3>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm font-medium text-[#746354] mb-2">
                    Paystack Public Key
                  </label>
                  <input
                    type="text"
                    value={settings.payments?.paystack_public_key || ''}
                    onChange={(e) => handleSettingChange('payments', 'paystack_public_key', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                    placeholder="pk_test_..."
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-[#746354] mb-2">
                    Paystack Secret Key
                  </label>
                  <div className="relative">
                    <input
                      type={showApiKey ? "text" : "password"}
                      value={settings.payments?.paystack_secret_key || ''}
                      onChange={(e) => handleSettingChange('payments', 'paystack_secret_key', e.target.value)}
                      className="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                      placeholder="sk_test_..."
                    />
                    <button
                      type="button"
                      onClick={() => setShowApiKey(!showApiKey)}
                      className="absolute inset-y-0 right-0 pr-3 flex items-center"
                    >
                      {showApiKey ? (
                        <FiEyeOff className="h-4 w-4 text-[#746354]" />
                      ) : (
                        <FiEye className="h-4 w-4 text-[#746354]" />
                      )}
                    </button>
                  </div>
                </div>

                <div className="flex items-center">
                  <input
                    type="checkbox"
                    id="payments_enabled"
                    checked={settings.payments?.enabled || false}
                    onChange={(e) => handleSettingChange('payments', 'enabled', e.target.checked)}
                    className="h-4 w-4 text-[#e41e5b] focus:ring-[#e41e5b] border-gray-300 rounded"
                  />
                  <label htmlFor="payments_enabled" className="ml-2 block text-sm text-[#746354]">
                    Enable Payment Processing
                  </label>
                </div>

                <div className="flex items-center">
                  <input
                    type="checkbox"
                    id="test_mode"
                    checked={settings.payments?.test_mode || true}
                    onChange={(e) => handleSettingChange('payments', 'test_mode', e.target.checked)}
                    className="h-4 w-4 text-[#e41e5b] focus:ring-[#e41e5b] border-gray-300 rounded"
                  />
                  <label htmlFor="test_mode" className="ml-2 block text-sm text-[#746354]">
                    Test Mode
                  </label>
                </div>
              </div>

              <div className="flex justify-end">
                <button
                  onClick={() => handleSaveSettings('payments')}
                  disabled={saving}
                  className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors disabled:opacity-50"
                >
                  {saving ? (
                    <FiRefreshCw className="h-4 w-4 mr-2 animate-spin" />
                  ) : (
                    <FiSave className="h-4 w-4 mr-2" />
                  )}
                  Save Changes
                </button>
              </div>
            </div>
          )}

          {activeTab === 'api' && (
            <div className="space-y-6">
              <h3 className="text-lg font-semibold text-[#2c2c2c]">API Configuration</h3>
              
              <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div className="flex items-center">
                  <FiAlertTriangle className="h-5 w-5 text-yellow-500 mr-2" />
                  <span className="text-yellow-800">
                    API keys are sensitive information. Keep them secure and never share them publicly.
                  </span>
                </div>
              </div>

              <div className="grid grid-cols-1 gap-6">
                <div>
                  <label className="block text-sm font-medium text-[#746354] mb-2">
                    JWT Secret Key
                  </label>
                  <div className="relative">
                    <input
                      type={showApiKey ? "text" : "password"}
                      value={settings.api?.jwt_secret || ''}
                      onChange={(e) => handleSettingChange('api', 'jwt_secret', e.target.value)}
                      className="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                      placeholder="Generate a secure random key"
                    />
                    <button
                      type="button"
                      onClick={() => setShowApiKey(!showApiKey)}
                      className="absolute inset-y-0 right-0 pr-3 flex items-center"
                    >
                      {showApiKey ? (
                        <FiEyeOff className="h-4 w-4 text-[#746354]" />
                      ) : (
                        <FiEye className="h-4 w-4 text-[#746354]" />
                      )}
                    </button>
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-[#746354] mb-2">
                    API Rate Limit (requests per minute)
                  </label>
                  <input
                    type="number"
                    value={settings.api?.rate_limit || 100}
                    onChange={(e) => handleSettingChange('api', 'rate_limit', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                  />
                </div>

                <div className="flex items-center">
                  <input
                    type="checkbox"
                    id="api_enabled"
                    checked={settings.api?.enabled || true}
                    onChange={(e) => handleSettingChange('api', 'enabled', e.target.checked)}
                    className="h-4 w-4 text-[#e41e5b] focus:ring-[#e41e5b] border-gray-300 rounded"
                  />
                  <label htmlFor="api_enabled" className="ml-2 block text-sm text-[#746354]">
                    Enable API Access
                  </label>
                </div>
              </div>

              <div className="flex justify-end">
                <button
                  onClick={() => handleSaveSettings('api')}
                  disabled={saving}
                  className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors disabled:opacity-50"
                >
                  {saving ? (
                    <FiRefreshCw className="h-4 w-4 mr-2 animate-spin" />
                  ) : (
                    <FiSave className="h-4 w-4 mr-2" />
                  )}
                  Save Changes
                </button>
              </div>
            </div>
          )}

          {activeTab === 'maintenance' && (
            <div className="space-y-6">
              <h3 className="text-lg font-semibold text-[#2c2c2c]">System Maintenance</h3>
              
              <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div className="flex items-center">
                  <FiAlertTriangle className="h-5 w-5 text-red-500 mr-2" />
                  <span className="text-red-800">
                    Maintenance mode will disable access for all regular users. Only Super Admins will be able to access the system.
                  </span>
                </div>
              </div>

              <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div>
                  <h4 className="text-sm font-medium text-[#2c2c2c]">Maintenance Mode</h4>
                  <p className="text-sm text-[#746354]">
                    Enable to restrict access to Super Admins only
                  </p>
                </div>
                <button
                  onClick={handleMaintenanceToggle}
                  className={`flex items-center px-4 py-2 rounded-lg transition-colors ${
                    maintenanceMode
                      ? 'bg-red-600 text-white hover:bg-red-700'
                      : 'bg-green-600 text-white hover:bg-green-700'
                  }`}
                >
                  {maintenanceMode ? (
                    <>
                      <FiLock className="h-4 w-4 mr-2" />
                      Disable
                    </>
                  ) : (
                    <>
                      <FiUnlock className="h-4 w-4 mr-2" />
                      Enable
                    </>
                  )}
                </button>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm font-medium text-[#746354] mb-2">
                    Maintenance Message
                  </label>
                  <textarea
                    value={settings.maintenance?.message || 'System is under maintenance. Please try again later.'}
                    onChange={(e) => handleSettingChange('maintenance', 'message', e.target.value)}
                    rows={3}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-[#746354] mb-2">
                    Estimated Downtime (hours)
                  </label>
                  <input
                    type="number"
                    value={settings.maintenance?.estimated_downtime || 2}
                    onChange={(e) => handleSettingChange('maintenance', 'estimated_downtime', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
                  />
                </div>
              </div>

              <div className="flex justify-end">
                <button
                  onClick={() => handleSaveSettings('maintenance')}
                  disabled={saving}
                  className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors disabled:opacity-50"
                >
                  {saving ? (
                    <FiRefreshCw className="h-4 w-4 mr-2 animate-spin" />
                  ) : (
                    <FiSave className="h-4 w-4 mr-2" />
                  )}
                  Save Changes
                </button>
              </div>
            </div>
          )}

          {activeTab === 'backup' && (
            <div className="space-y-6">
              <h3 className="text-lg font-semibold text-[#2c2c2c]">Backup & Recovery</h3>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="bg-white border border-gray-200 rounded-lg p-6">
                  <div className="flex items-center justify-between mb-4">
                    <h4 className="text-lg font-medium text-[#2c2c2c]">Manual Backup</h4>
                    <FiDatabase className="h-6 w-6 text-[#e41e5b]" />
                  </div>
                  <p className="text-sm text-[#746354] mb-4">
                    Create a manual backup of the entire system including database and files.
                  </p>
                  <button
                    onClick={handleBackup}
                    disabled={backupStatus === 'running'}
                    className="w-full flex items-center justify-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors disabled:opacity-50"
                  >
                    {backupStatus === 'running' ? (
                      <>
                        <FiRefreshCw className="h-4 w-4 mr-2 animate-spin" />
                        Creating Backup...
                      </>
                    ) : (
                      <>
                        <FiDownload className="h-4 w-4 mr-2" />
                        Create Backup
                      </>
                    )}
                  </button>
                </div>

                <div className="bg-white border border-gray-200 rounded-lg p-6">
                  <div className="flex items-center justify-between mb-4">
                    <h4 className="text-lg font-medium text-[#2c2c2c]">Auto Backup</h4>
                    <FiCalendar className="h-6 w-6 text-[#e41e5b]" />
                  </div>
                  <p className="text-sm text-[#746354] mb-4">
                    Configure automatic daily backups at 2:00 AM.
                  </p>
                  <div className="flex items-center">
                    <input
                      type="checkbox"
                      id="auto_backup"
                      checked={settings.backup?.auto_backup || false}
                      onChange={(e) => handleSettingChange('backup', 'auto_backup', e.target.checked)}
                      className="h-4 w-4 text-[#e41e5b] focus:ring-[#e41e5b] border-gray-300 rounded"
                    />
                    <label htmlFor="auto_backup" className="ml-2 block text-sm text-[#746354]">
                      Enable Auto Backup
                    </label>
                  </div>
                </div>
              </div>

              <div className="bg-white border border-gray-200 rounded-lg p-6">
                <h4 className="text-lg font-medium text-[#2c2c2c] mb-4">Recent Backups</h4>
                <div className="space-y-3">
                  {settings.backup?.recent_backups?.map((backup, index) => (
                    <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                      <div>
                        <p className="text-sm font-medium text-[#2c2c2c]">
                          Backup {backup.filename}
                        </p>
                        <p className="text-xs text-[#746354]">
                          {new Date(backup.created_at).toLocaleString()}
                        </p>
                      </div>
                      <div className="flex items-center space-x-2">
                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                          backup.status === 'completed' ? 'bg-green-100 text-green-800' :
                          backup.status === 'failed' ? 'bg-red-100 text-red-800' :
                          'bg-yellow-100 text-yellow-800'
                        }`}>
                          {backup.status}
                        </span>
                        <button className="text-[#e41e5b] hover:text-[#9a0864]">
                          <FiDownload className="h-4 w-4" />
                        </button>
                      </div>
                    </div>
                  )) || (
                    <p className="text-sm text-[#746354] text-center py-4">
                      No recent backups found
                    </p>
                  )}
                </div>
              </div>

              <div className="flex justify-end">
                <button
                  onClick={() => handleSaveSettings('backup')}
                  disabled={saving}
                  className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors disabled:opacity-50"
                >
                  {saving ? (
                    <FiRefreshCw className="h-4 w-4 mr-2 animate-spin" />
                  ) : (
                    <FiSave className="h-4 w-4 mr-2" />
                  )}
                  Save Changes
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default SuperAdminSettingsPage;
