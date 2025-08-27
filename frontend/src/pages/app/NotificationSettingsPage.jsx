import React, { useState, useEffect } from 'react';
import { FiBell, FiMail, FiAlertTriangle, FiCheckCircle, FiSettings, FiSend, FiEye, FiEyeOff } from 'react-icons/fi';
import { notificationAPI } from '../../services/api';
import { useAuth } from '../../contexts/AuthContext';
import useNotificationStore from '../../stores/notificationStore';
import toast from 'react-hot-toast';

const NotificationSettingsPage = () => {
  const { user, tenant } = useAuth();
  const { addSystemNotification } = useNotificationStore();
  const [loading, setLoading] = useState(false);
  const [testEmail, setTestEmail] = useState('');
  const [showTestEmail, setShowTestEmail] = useState(false);

  // Notification settings
  const [settings, setSettings] = useState({
    email_notifications: true,
    low_stock_alerts: true,
    sales_reports: true,
    payment_notifications: true,
    system_alerts: true,
    low_stock_threshold: 10,
    report_frequency: 'monthly', // daily, weekly, monthly
    email_time: '09:00' // Time to send daily reports
  });

  // Notification logs
  const [logs, setLogs] = useState([]);
  const [logsLoading, setLogsLoading] = useState(false);

  useEffect(() => {
    loadSettings();
    loadLogs();
  }, []);

  const loadSettings = async () => {
    try {
      setLoading(true);
      const response = await notificationAPI.getSettings(tenant?.id);
      if (response.success) {
        setSettings(prev => ({ ...prev, ...response.data }));
      }
    } catch (error) {
      console.error('Failed to load settings:', error);
      // Don't show error toast for settings loading failure
      // Just use default settings
    } finally {
      setLoading(false);
    }
  };

  const loadLogs = async () => {
    try {
      setLogsLoading(true);
      const response = await notificationAPI.getLogs({ limit: 50 });
      if (response.success) {
        setLogs(response.data.notifications || []);
      }
    } catch (error) {
      console.error('Failed to load logs:', error);
    } finally {
      setLogsLoading(false);
    }
  };

  const handleSettingChange = (key, value) => {
    setSettings(prev => ({ ...prev, [key]: value }));
  };

  const saveSettings = async () => {
    try {
      setLoading(true);
      const response = await notificationAPI.updateSettings(settings);
      if (response.success) {
        toast.success('Notification settings updated successfully!');
        addSystemNotification('Notification settings updated successfully!', 'success');
      } else {
        toast.error('Failed to update settings');
        addSystemNotification('Failed to update notification settings', 'error');
      }
    } catch (error) {
      console.error('Failed to save settings:', error);
      toast.error('Failed to save notification settings');
      addSystemNotification('Failed to save notification settings', 'error');
    } finally {
      setLoading(false);
    }
  };

  const sendTestEmail = async () => {
    if (!testEmail) {
      toast.error('Please enter an email address');
      return;
    }

    try {
      setLoading(true);
      const response = await notificationAPI.testEmail(testEmail);
      if (response.success) {
        toast.success('Test email sent successfully!');
        addSystemNotification(`Test email sent to ${testEmail}`, 'success');
        setTestEmail('');
        setShowTestEmail(false);
      } else {
        toast.error(response.error || 'Failed to send test email');
        addSystemNotification('Failed to send test email', 'error');
      }
    } catch (error) {
      console.error('Failed to send test email:', error);
      toast.error('Failed to send test email');
      addSystemNotification('Failed to send test email', 'error');
    } finally {
      setLoading(false);
    }
  };

  const sendLowStockAlert = async () => {
    try {
      setLoading(true);
      const response = await notificationAPI.sendLowStockAlerts();
      if (response.success) {
        toast.success(`${response.alerts_sent} low stock alerts sent!`);
        addSystemNotification('Low stock alerts sent successfully', 'success');
      } else {
        toast.error('Failed to send low stock alerts');
        addSystemNotification('Failed to send low stock alerts', 'error');
      }
    } catch (error) {
      console.error('Failed to send low stock alerts:', error);
      toast.error('Failed to send low stock alerts');
      addSystemNotification('Failed to send low stock alerts', 'error');
    } finally {
      setLoading(false);
    }
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'success':
        return <FiCheckCircle className="text-green-500" />;
      case 'failed':
        return <FiAlertTriangle className="text-red-500" />;
      case 'pending':
        return <FiEye className="text-yellow-500" />;
      default:
        return <FiEye className="text-gray-500" />;
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'success':
        return 'bg-green-100 text-green-800';
      case 'failed':
        return 'bg-red-100 text-red-800';
      case 'pending':
        return 'bg-yellow-100 text-yellow-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-6xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 flex items-center">
            <FiBell className="mr-3 text-[#E72F7C]" />
            Notification Settings
          </h1>
          <p className="text-gray-600 mt-2">
            Manage your email notifications and alerts for {tenant?.name}
          </p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Settings Panel */}
          <div className="lg:col-span-2">
            <div className="bg-white rounded-xl shadow-sm border border-gray-200">
              <div className="p-6 border-b border-gray-200">
                <h2 className="text-xl font-semibold text-gray-900 flex items-center">
                  <FiSettings className="mr-2 text-[#E72F7C]" />
                  Email Notifications
                </h2>
              </div>

              <div className="p-6 space-y-6">
                {/* General Email Notifications */}
                <div>
                  <h3 className="text-lg font-medium text-gray-900 mb-4">General Settings</h3>
                  <div className="space-y-4">
                    <div className="flex items-center justify-between">
                      <div>
                        <label className="text-sm font-medium text-gray-700">Email Notifications</label>
                        <p className="text-sm text-gray-500">Receive email notifications for important events</p>
                      </div>
                      <label className="relative inline-flex items-center cursor-pointer">
                        <input
                          type="checkbox"
                          checked={settings.email_notifications}
                          onChange={(e) => handleSettingChange('email_notifications', e.target.checked)}
                          className="sr-only peer"
                        />
                        <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#E72F7C]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#E72F7C]"></div>
                      </label>
                    </div>

                    <div className="flex items-center justify-between">
                      <div>
                        <label className="text-sm font-medium text-gray-700">System Alerts</label>
                        <p className="text-sm text-gray-500">Receive alerts for system issues and updates</p>
                      </div>
                      <label className="relative inline-flex items-center cursor-pointer">
                        <input
                          type="checkbox"
                          checked={settings.system_alerts}
                          onChange={(e) => handleSettingChange('system_alerts', e.target.checked)}
                          className="sr-only peer"
                        />
                        <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#E72F7C]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#E72F7C]"></div>
                      </label>
                    </div>
                  </div>
                </div>

                {/* Business Notifications */}
                <div>
                  <h3 className="text-lg font-medium text-gray-900 mb-4">Business Notifications</h3>
                  <div className="space-y-4">
                    <div className="flex items-center justify-between">
                      <div>
                        <label className="text-sm font-medium text-gray-700">Low Stock Alerts</label>
                        <p className="text-sm text-gray-500">Get notified when products are running low</p>
                      </div>
                      <label className="relative inline-flex items-center cursor-pointer">
                        <input
                          type="checkbox"
                          checked={settings.low_stock_alerts}
                          onChange={(e) => handleSettingChange('low_stock_alerts', e.target.checked)}
                          className="sr-only peer"
                        />
                        <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#E72F7C]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#E72F7C]"></div>
                      </label>
                    </div>

                    <div className="flex items-center justify-between">
                      <div>
                        <label className="text-sm font-medium text-gray-700">Sales Reports</label>
                        <p className="text-sm text-gray-500">Receive periodic sales reports</p>
                      </div>
                      <label className="relative inline-flex items-center cursor-pointer">
                        <input
                          type="checkbox"
                          checked={settings.sales_reports}
                          onChange={(e) => handleSettingChange('sales_reports', e.target.checked)}
                          className="sr-only peer"
                        />
                        <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#E72F7C]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#E72F7C]"></div>
                      </label>
                    </div>

                    <div className="flex items-center justify-between">
                      <div>
                        <label className="text-sm font-medium text-gray-700">Payment Notifications</label>
                        <p className="text-sm text-gray-500">Get notified about payment confirmations and failures</p>
                      </div>
                      <label className="relative inline-flex items-center cursor-pointer">
                        <input
                          type="checkbox"
                          checked={settings.payment_notifications}
                          onChange={(e) => handleSettingChange('payment_notifications', e.target.checked)}
                          className="sr-only peer"
                        />
                        <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#E72F7C]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#E72F7C]"></div>
                      </label>
                    </div>
                  </div>
                </div>

                {/* Advanced Settings */}
                <div>
                  <h3 className="text-lg font-medium text-gray-900 mb-4">Advanced Settings</h3>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Low Stock Threshold
                      </label>
                      <input
                        type="number"
                        value={settings.low_stock_threshold}
                        onChange={(e) => handleSettingChange('low_stock_threshold', parseInt(e.target.value))}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#E72F7C] focus:border-transparent"
                        min="1"
                        max="100"
                      />
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Report Frequency
                      </label>
                      <select
                        value={settings.report_frequency}
                        onChange={(e) => handleSettingChange('report_frequency', e.target.value)}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#E72F7C] focus:border-transparent"
                      >
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                      </select>
                    </div>
                  </div>
                </div>

                {/* Action Buttons */}
                <div className="flex flex-wrap gap-4 pt-6 border-t border-gray-200">
                  <button
                    onClick={saveSettings}
                    disabled={loading}
                    className="px-6 py-2 bg-[#E72F7C] text-white rounded-lg hover:bg-[#9a0864] focus:ring-2 focus:ring-[#E72F7C] focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                  >
                    <FiSettings className="mr-2" />
                    {loading ? 'Saving...' : 'Save Settings'}
                  </button>

                  <button
                    onClick={() => setShowTestEmail(!showTestEmail)}
                    className="px-6 py-2 border border-[#E72F7C] text-[#E72F7C] rounded-lg hover:bg-[#E72F7C] hover:text-white focus:ring-2 focus:ring-[#E72F7C] focus:ring-offset-2 flex items-center"
                  >
                    <FiMail className="mr-2" />
                    Test Email
                  </button>

                  <button
                    onClick={sendLowStockAlert}
                    disabled={loading}
                    className="px-6 py-2 border border-[#a67c00] text-[#a67c00] rounded-lg hover:bg-[#a67c00] hover:text-white focus:ring-2 focus:ring-[#a67c00] focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                  >
                    <FiAlertTriangle className="mr-2" />
                    Send Low Stock Alert
                  </button>
                </div>

                {/* Test Email Section */}
                {showTestEmail && (
                  <div className="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <h4 className="text-sm font-medium text-gray-900 mb-3">Send Test Email</h4>
                    <div className="flex gap-3">
                      <input
                        type="email"
                        value={testEmail}
                        onChange={(e) => setTestEmail(e.target.value)}
                        placeholder="Enter email address"
                        className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#E72F7C] focus:border-transparent"
                      />
                      <button
                        onClick={sendTestEmail}
                        disabled={loading || !testEmail}
                        className="px-4 py-2 bg-[#E72F7C] text-white rounded-lg hover:bg-[#9a0864] focus:ring-2 focus:ring-[#E72F7C] focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                      >
                        <FiSend className="mr-2" />
                        Send
                      </button>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Notification Logs */}
          <div className="lg:col-span-1">
            <div className="bg-white rounded-xl shadow-sm border border-gray-200">
              <div className="p-6 border-b border-gray-200">
                <h2 className="text-xl font-semibold text-gray-900 flex items-center">
                  <FiEye className="mr-2 text-[#E72F7C]" />
                  Recent Notifications
                </h2>
              </div>

              <div className="p-6">
                {logsLoading ? (
                  <div className="text-center py-8">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#E72F7C] mx-auto"></div>
                    <p className="text-sm text-gray-500 mt-2">Loading logs...</p>
                  </div>
                ) : logs.length > 0 ? (
                  <div className="space-y-3">
                    {logs.slice(0, 10).map((log, index) => (
                      <div key={index} className="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                        <div className="flex-shrink-0 mt-1">
                          {getStatusIcon(log.status)}
                        </div>
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-medium text-gray-900 truncate">
                            {log.subject}
                          </p>
                          <p className="text-xs text-gray-500 truncate">
                            {log.to_email}
                          </p>
                          <p className="text-xs text-gray-400">
                            {new Date(log.sent_at).toLocaleDateString()}
                          </p>
                        </div>
                        <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(log.status)}`}>
                          {log.status}
                        </span>
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="text-center py-8">
                    <FiEye className="mx-auto h-12 w-12 text-gray-400" />
                    <p className="text-sm text-gray-500 mt-2">No notifications yet</p>
                  </div>
                )}

                <button
                  onClick={loadLogs}
                  className="w-full mt-4 px-4 py-2 text-sm text-[#E72F7C] border border-[#E72F7C] rounded-lg hover:bg-[#E72F7C] hover:text-white focus:ring-2 focus:ring-[#E72F7C] focus:ring-offset-2"
                >
                  Refresh Logs
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default NotificationSettingsPage;
