import React, { useState, useEffect } from 'react';
import { 
  FiShield, 
  FiLock, 
  FiUnlock, 
  FiEye, 
  FiEyeOff,
  FiUserCheck,
  FiUserX,
  FiAlertTriangle,
  FiCheckCircle,
  FiXCircle,
  FiClock,
  FiSearch,
  FiFilter,
  FiDownload,
  FiRefreshCw,
  FiSettings,
  FiKey,
  FiDatabase,
  FiServer,
  FiActivity,
  FiBarChart2
} from 'react-icons/fi';
import { useAuth } from '../../contexts/AuthContext';
import { superAdminAPI } from '../../services/api';

const SuperAdminSecurityPage = () => {
  const { user } = useAuth();
  const [securityData, setSecurityData] = useState({
    accessLogs: [],
    failedLogins: [],
    securityEvents: [],
    activeSessions: [],
    systemAlerts: []
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [filter, setFilter] = useState('all');
  const [searchTerm, setSearchTerm] = useState('');
  const [timeRange, setTimeRange] = useState('24');

  useEffect(() => {
    fetchSecurityData();
  }, [timeRange]);

  const fetchSecurityData = async () => {
    try {
      setLoading(true);
      setError(null);

      // Fetch security data from API
      const response = await superAdminAPI.getSecurityLogs();
      
      if (response.data.success) {
        setSecurityData(response.data.data);
      } else {
        // Use demo data if API fails
        setSecurityData({
          accessLogs: [
            {
              id: 1,
              user: 'admin@restaurant.com',
              action: 'login',
              ip_address: '192.168.1.100',
              user_agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
              status: 'success',
              timestamp: '2024-01-15T10:30:00Z'
            },
            {
              id: 2,
              user: 'cashier@retail.com',
              action: 'logout',
              ip_address: '192.168.1.101',
              user_agent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0)',
              status: 'success',
              timestamp: '2024-01-15T10:25:00Z'
            },
            {
              id: 3,
              user: 'unknown@example.com',
              action: 'login',
              ip_address: '203.0.113.45',
              user_agent: 'Mozilla/5.0 (Unknown)',
              status: 'failed',
              timestamp: '2024-01-15T10:20:00Z'
            }
          ],
          failedLogins: [
            {
              id: 1,
              email: 'unknown@example.com',
              ip_address: '203.0.113.45',
              attempts: 5,
              last_attempt: '2024-01-15T10:20:00Z',
              blocked: true
            },
            {
              id: 2,
              email: 'test@test.com',
              ip_address: '198.51.100.10',
              attempts: 3,
              last_attempt: '2024-01-15T09:15:00Z',
              blocked: false
            }
          ],
          securityEvents: [
            {
              id: 1,
              type: 'suspicious_login',
              severity: 'high',
              description: 'Multiple failed login attempts from suspicious IP',
              timestamp: '2024-01-15T10:20:00Z',
              resolved: false
            },
            {
              id: 2,
              type: 'password_change',
              severity: 'medium',
              description: 'Password changed for admin account',
              timestamp: '2024-01-15T09:00:00Z',
              resolved: true
            },
            {
              id: 3,
              type: 'api_rate_limit',
              severity: 'low',
              description: 'API rate limit exceeded for tenant',
              timestamp: '2024-01-15T08:30:00Z',
              resolved: true
            }
          ],
          activeSessions: [
            {
              id: 1,
              user: 'admin@restaurant.com',
              ip_address: '192.168.1.100',
              user_agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
              last_activity: '2024-01-15T10:30:00Z',
              session_duration: '2h 15m'
            },
            {
              id: 2,
              user: 'manager@retail.com',
              ip_address: '192.168.1.102',
              user_agent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15)',
              last_activity: '2024-01-15T10:25:00Z',
              session_duration: '45m'
            }
          ],
          systemAlerts: [
            {
              id: 1,
              type: 'security',
              message: 'High number of failed login attempts detected',
              severity: 'high',
              timestamp: '2024-01-15T10:20:00Z',
              acknowledged: false
            },
            {
              id: 2,
              type: 'system',
              message: 'Database backup completed successfully',
              severity: 'low',
              timestamp: '2024-01-15T08:00:00Z',
              acknowledged: true
            }
          ]
        });
      }
    } catch (err) {
      console.error('Error fetching security data:', err);
      setError('Failed to load security data');
    } finally {
      setLoading(false);
    }
  };

  const getSeverityColor = (severity) => {
    switch (severity) {
      case 'high': return 'text-red-600 bg-red-100';
      case 'medium': return 'text-yellow-600 bg-yellow-100';
      case 'low': return 'text-green-600 bg-green-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'success': return 'text-green-600 bg-green-100';
      case 'failed': return 'text-red-600 bg-red-100';
      case 'blocked': return 'text-red-600 bg-red-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  const formatTimestamp = (timestamp) => {
    return new Date(timestamp).toLocaleString();
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 p-6">
        <div className="max-w-7xl mx-auto">
          <div className="animate-pulse">
            <div className="h-8 bg-gray-200 rounded w-1/4 mb-6"></div>
            <div className="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
              {[1, 2, 3, 4].map(i => (
                <div key={i} className="bg-white rounded-lg shadow p-6">
                  <div className="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
                  <div className="h-3 bg-gray-200 rounded w-1/2 mb-2"></div>
                  <div className="h-3 bg-gray-200 rounded w-2/3"></div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-900 flex items-center">
                <FiShield className="mr-3 text-red-500" />
                Security Management
              </h1>
              <p className="text-gray-600 mt-2">
                Monitor security events, access logs, and system alerts
              </p>
            </div>
            <div className="flex items-center space-x-4">
              <select
                value={timeRange}
                onChange={(e) => setTimeRange(e.target.value)}
                className="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
              >
                <option value="1">Last hour</option>
                <option value="24">Last 24 hours</option>
                <option value="168">Last 7 days</option>
                <option value="720">Last 30 days</option>
              </select>
              <button
                onClick={fetchSecurityData}
                className="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors"
              >
                <FiRefreshCw className="mr-2" />
                Refresh
              </button>
            </div>
          </div>
        </div>

        {/* Security Stats */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="p-3 bg-red-100 rounded-lg">
                <FiAlertTriangle className="text-red-600 text-xl" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Security Alerts</p>
                <p className="text-2xl font-bold text-gray-900">
                  {securityData.systemAlerts.filter(alert => !alert.acknowledged).length}
                </p>
              </div>
            </div>
          </div>
          
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="p-3 bg-yellow-100 rounded-lg">
                <FiUserX className="text-yellow-600 text-xl" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Failed Logins</p>
                <p className="text-2xl font-bold text-gray-900">
                  {securityData.failedLogins.length}
                </p>
              </div>
            </div>
          </div>
          
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="p-3 bg-green-100 rounded-lg">
                <FiUserCheck className="text-green-600 text-xl" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Active Sessions</p>
                <p className="text-2xl font-bold text-gray-900">
                  {securityData.activeSessions.length}
                </p>
              </div>
            </div>
          </div>
          
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="p-3 bg-blue-100 rounded-lg">
                <FiActivity className="text-blue-600 text-xl" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Total Events</p>
                <p className="text-2xl font-bold text-gray-900">
                  {securityData.accessLogs.length}
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Security Events */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
          {/* Recent Security Events */}
          <div className="bg-white rounded-lg shadow">
            <div className="px-6 py-4 border-b border-gray-200">
              <h3 className="text-lg font-semibold text-gray-900">Recent Security Events</h3>
            </div>
            <div className="p-6">
              <div className="space-y-4">
                {securityData.securityEvents.map((event) => (
                  <div key={event.id} className="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                    <div className={`p-2 rounded-full ${getSeverityColor(event.severity)}`}>
                      <FiAlertTriangle className="w-4 h-4" />
                    </div>
                    <div className="flex-1">
                      <div className="flex items-center justify-between">
                        <h4 className="text-sm font-medium text-gray-900">{event.type}</h4>
                        <span className={`text-xs px-2 py-1 rounded-full ${getSeverityColor(event.severity)}`}>
                          {event.severity}
                        </span>
                      </div>
                      <p className="text-sm text-gray-600 mt-1">{event.description}</p>
                      <p className="text-xs text-gray-500 mt-2">{formatTimestamp(event.timestamp)}</p>
                    </div>
                    <div className="flex items-center space-x-2">
                      {event.resolved ? (
                        <FiCheckCircle className="text-green-500 w-4 h-4" />
                      ) : (
                        <FiXCircle className="text-red-500 w-4 h-4" />
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* System Alerts */}
          <div className="bg-white rounded-lg shadow">
            <div className="px-6 py-4 border-b border-gray-200">
              <h3 className="text-lg font-semibold text-gray-900">System Alerts</h3>
            </div>
            <div className="p-6">
              <div className="space-y-4">
                {securityData.systemAlerts.map((alert) => (
                  <div key={alert.id} className="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                    <div className={`p-2 rounded-full ${getSeverityColor(alert.severity)}`}>
                      <FiAlertTriangle className="w-4 h-4" />
                    </div>
                    <div className="flex-1">
                      <div className="flex items-center justify-between">
                        <h4 className="text-sm font-medium text-gray-900">{alert.type}</h4>
                        <span className={`text-xs px-2 py-1 rounded-full ${getSeverityColor(alert.severity)}`}>
                          {alert.severity}
                        </span>
                      </div>
                      <p className="text-sm text-gray-600 mt-1">{alert.message}</p>
                      <p className="text-xs text-gray-500 mt-2">{formatTimestamp(alert.timestamp)}</p>
                    </div>
                    <div className="flex items-center space-x-2">
                      {alert.acknowledged ? (
                        <FiCheckCircle className="text-green-500 w-4 h-4" />
                      ) : (
                        <FiClock className="text-yellow-500 w-4 h-4" />
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>

        {/* Access Logs */}
        <div className="bg-white rounded-lg shadow overflow-hidden">
          <div className="px-6 py-4 border-b border-gray-200">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-semibold text-gray-900">Access Logs</h3>
              <div className="flex items-center space-x-4">
                <select
                  value={filter}
                  onChange={(e) => setFilter(e.target.value)}
                  className="border border-gray-300 rounded-lg px-3 py-1 text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent"
                >
                  <option value="all">All Events</option>
                  <option value="success">Success</option>
                  <option value="failed">Failed</option>
                  <option value="blocked">Blocked</option>
                </select>
                <div className="relative">
                  <input
                    type="text"
                    placeholder="Search logs..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="border border-gray-300 rounded-lg pl-8 pr-4 py-1 text-sm w-48 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                  />
                  <FiSearch className="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                </div>
              </div>
            </div>
          </div>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    User
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Action
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    IP Address
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Timestamp
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {securityData.accessLogs
                  .filter(log => filter === 'all' || log.status === filter)
                  .filter(log => 
                    log.user.toLowerCase().includes(searchTerm.toLowerCase()) ||
                    log.ip_address.includes(searchTerm)
                  )
                  .map((log) => (
                  <tr key={log.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 text-sm text-gray-900">
                      {log.user}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-900">
                      {log.action}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-500">
                      {log.ip_address}
                    </td>
                    <td className="px-6 py-4">
                      <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(log.status)}`}>
                        {log.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-500">
                      {formatTimestamp(log.timestamp)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Failed Login Attempts */}
        <div className="mt-8 bg-white rounded-lg shadow overflow-hidden">
          <div className="px-6 py-4 border-b border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900">Failed Login Attempts</h3>
          </div>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Email
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    IP Address
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Attempts
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Last Attempt
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {securityData.failedLogins.map((login) => (
                  <tr key={login.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 text-sm text-gray-900">
                      {login.email}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-500">
                      {login.ip_address}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-900">
                      {login.attempts}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-500">
                      {formatTimestamp(login.last_attempt)}
                    </td>
                    <td className="px-6 py-4">
                      <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(login.blocked ? 'blocked' : 'failed')}`}>
                        {login.blocked ? 'Blocked' : 'Failed'}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center space-x-2">
                        <button
                          className="text-blue-400 hover:text-blue-600"
                          title="View Details"
                        >
                          <FiEye size={14} />
                        </button>
                        {login.blocked && (
                          <button
                            className="text-green-400 hover:text-green-600"
                            title="Unblock IP"
                          >
                            <FiUnlock size={14} />
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SuperAdminSecurityPage;
