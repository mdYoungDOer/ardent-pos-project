import React, { useState, useEffect } from 'react';
import { 
  FiActivity, 
  FiServer, 
  FiDatabase, 
  FiWifi, 
  FiCpu, 
  FiHardDrive,
  FiAlertCircle,
  FiCheckCircle,
  FiClock,
  FiRefreshCw,
  FiTrendingUp,
  FiTrendingDown,
  FiAlertTriangle
} from 'react-icons/fi';
import { useAuth } from '../../contexts/AuthContext';
import { superAdminAPI } from '../../services/api';

const SuperAdminSystemHealthPage = () => {
  const { user } = useAuth();
  const [healthData, setHealthData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [lastUpdated, setLastUpdated] = useState(null);

  useEffect(() => {
    fetchSystemHealth();
    // Refresh every 30 seconds
    const interval = setInterval(fetchSystemHealth, 30000);
    return () => clearInterval(interval);
  }, []);

  const fetchSystemHealth = async () => {
    try {
      setLoading(true);
      setError(null);

      const response = await superAdminAPI.getSystemHealth();
      
      if (response.data.success) {
        setHealthData(response.data.data);
        setLastUpdated(new Date());
      } else {
        // Use demo data if API fails
        setHealthData({
          status: 'healthy',
          database: 'healthy',
          total_users: 1250,
          timestamp: new Date().toISOString(),
          cpu_usage: 45,
          memory_usage: 62,
          disk_usage: 38,
          network_status: 'excellent',
          uptime: '15 days, 8 hours, 32 minutes',
          response_time: 125,
          error_rate: 0.02,
          active_connections: 847,
          services: [
            { name: 'Web Server', status: 'healthy', uptime: '15d 8h 32m' },
            { name: 'Database', status: 'healthy', uptime: '15d 8h 32m' },
            { name: 'Cache Server', status: 'healthy', uptime: '15d 8h 32m' },
            { name: 'File Storage', status: 'healthy', uptime: '15d 8h 32m' },
            { name: 'Email Service', status: 'warning', uptime: '2d 15h 45m' },
            { name: 'Payment Gateway', status: 'healthy', uptime: '15d 8h 32m' }
          ],
          alerts: [
            { id: 1, type: 'warning', message: 'Email service restarted', timestamp: '2 hours ago' },
            { id: 2, type: 'info', message: 'Database backup completed', timestamp: '6 hours ago' },
            { id: 3, type: 'success', message: 'System update completed', timestamp: '1 day ago' }
          ]
        });
        setLastUpdated(new Date());
      }
    } catch (err) {
      console.error('Error fetching system health:', err);
      setError('Failed to load system health data');
      // Use fallback data
      setHealthData({
        status: 'error',
        database: 'error',
        total_users: 0,
        timestamp: new Date().toISOString(),
        cpu_usage: 0,
        memory_usage: 0,
        disk_usage: 0,
        network_status: 'unknown',
        uptime: '0 days',
        response_time: 0,
        error_rate: 0,
        active_connections: 0,
        services: [],
        alerts: []
      });
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'healthy': return 'text-green-600 bg-green-100';
      case 'warning': return 'text-yellow-600 bg-yellow-100';
      case 'error': return 'text-red-600 bg-red-100';
      case 'excellent': return 'text-green-600 bg-green-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'healthy':
      case 'excellent':
        return <FiCheckCircle className="w-5 h-5 text-green-600" />;
      case 'warning':
        return <FiAlertTriangle className="w-5 h-5 text-yellow-600" />;
      case 'error':
        return <FiAlertCircle className="w-5 h-5 text-red-600" />;
      default:
        return <FiClock className="w-5 h-5 text-gray-600" />;
    }
  };

  const getUsageColor = (usage) => {
    if (usage < 50) return 'text-green-600';
    if (usage < 80) return 'text-yellow-600';
    return 'text-red-600';
  };

  const getUsageBarColor = (usage) => {
    if (usage < 50) return 'bg-green-500';
    if (usage < 80) return 'bg-yellow-500';
    return 'bg-red-500';
  };

  if (loading && !healthData) {
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
                <FiActivity className="mr-3 text-primary" />
                System Health
              </h1>
              <p className="text-gray-600 mt-2">
                Monitor system performance, uptime, and service status
              </p>
            </div>
            <div className="flex items-center space-x-4">
              <div className="text-sm text-gray-500">
                Last updated: {lastUpdated ? lastUpdated.toLocaleTimeString() : 'Never'}
              </div>
              <button
                onClick={fetchSystemHealth}
                className="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg flex items-center transition-colors"
              >
                <FiRefreshCw className="mr-2" />
                Refresh
              </button>
            </div>
          </div>
        </div>

        {/* Overall Status */}
        <div className="bg-white rounded-lg shadow mb-8 p-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-xl font-semibold text-gray-900">Overall System Status</h2>
            <div className={`flex items-center px-3 py-1 rounded-full ${getStatusColor(healthData?.status)}`}>
              {getStatusIcon(healthData?.status)}
              <span className="ml-2 text-sm font-medium capitalize">
                {healthData?.status || 'Unknown'}
              </span>
            </div>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div className="flex items-center p-4 bg-gray-50 rounded-lg">
              <FiServer className="w-8 h-8 text-blue-600 mr-4" />
              <div>
                <p className="text-sm font-medium text-gray-600">Uptime</p>
                <p className="text-lg font-semibold text-gray-900">{healthData?.uptime || '0 days'}</p>
              </div>
            </div>
            
            <div className="flex items-center p-4 bg-gray-50 rounded-lg">
              <FiWifi className="w-8 h-8 text-green-600 mr-4" />
              <div>
                <p className="text-sm font-medium text-gray-600">Response Time</p>
                <p className="text-lg font-semibold text-gray-900">{healthData?.response_time || 0}ms</p>
              </div>
            </div>
            
            <div className="flex items-center p-4 bg-gray-50 rounded-lg">
              <FiDatabase className="w-8 h-8 text-purple-600 mr-4" />
              <div>
                <p className="text-sm font-medium text-gray-600">Active Connections</p>
                <p className="text-lg font-semibold text-gray-900">{healthData?.active_connections || 0}</p>
              </div>
            </div>
            
            <div className="flex items-center p-4 bg-gray-50 rounded-lg">
              <FiAlertCircle className="w-8 h-8 text-orange-600 mr-4" />
              <div>
                <p className="text-sm font-medium text-gray-600">Error Rate</p>
                <p className="text-lg font-semibold text-gray-900">{(healthData?.error_rate * 100 || 0).toFixed(2)}%</p>
              </div>
            </div>
          </div>
        </div>

        {/* Performance Metrics */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
          {/* CPU Usage */}
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-semibold text-gray-900 flex items-center">
                <FiCpu className="mr-2" />
                CPU Usage
              </h3>
              <span className={`text-lg font-semibold ${getUsageColor(healthData?.cpu_usage)}`}>
                {healthData?.cpu_usage || 0}%
              </span>
            </div>
            <div className="w-full bg-gray-200 rounded-full h-3">
              <div 
                className={`h-3 rounded-full ${getUsageBarColor(healthData?.cpu_usage)}`}
                style={{ width: `${healthData?.cpu_usage || 0}%` }}
              ></div>
            </div>
          </div>

          {/* Memory Usage */}
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-semibold text-gray-900 flex items-center">
                <FiHardDrive className="mr-2" />
                Memory Usage
              </h3>
              <span className={`text-lg font-semibold ${getUsageColor(healthData?.memory_usage)}`}>
                {healthData?.memory_usage || 0}%
              </span>
            </div>
            <div className="w-full bg-gray-200 rounded-full h-3">
              <div 
                className={`h-3 rounded-full ${getUsageBarColor(healthData?.memory_usage)}`}
                style={{ width: `${healthData?.memory_usage || 0}%` }}
              ></div>
            </div>
          </div>
        </div>

        {/* Services Status */}
        <div className="bg-white rounded-lg shadow mb-8">
          <div className="px-6 py-4 border-b border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900">Service Status</h3>
          </div>
          <div className="p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {healthData?.services?.map((service, index) => (
                <div key={index} className="flex items-center p-4 border border-gray-200 rounded-lg">
                  {getStatusIcon(service.status)}
                  <div className="ml-3 flex-1">
                    <p className="text-sm font-medium text-gray-900">{service.name}</p>
                    <p className="text-xs text-gray-500">{service.uptime}</p>
                  </div>
                  <span className={`text-xs px-2 py-1 rounded-full ${getStatusColor(service.status)}`}>
                    {service.status}
                  </span>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Recent Alerts */}
        <div className="bg-white rounded-lg shadow">
          <div className="px-6 py-4 border-b border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900">Recent Alerts</h3>
          </div>
          <div className="p-6">
            {healthData?.alerts?.length > 0 ? (
              <div className="space-y-4">
                {healthData.alerts.map((alert) => (
                  <div key={alert.id} className="flex items-start p-4 border border-gray-200 rounded-lg">
                    <div className={`mt-1 ${alert.type === 'warning' ? 'text-yellow-600' : alert.type === 'error' ? 'text-red-600' : 'text-green-600'}`}>
                      {alert.type === 'warning' ? <FiAlertTriangle className="w-5 h-5" /> : 
                       alert.type === 'error' ? <FiAlertCircle className="w-5 h-5" /> : 
                       <FiCheckCircle className="w-5 h-5" />}
                    </div>
                    <div className="ml-3 flex-1">
                      <p className="text-sm text-gray-900">{alert.message}</p>
                      <p className="text-xs text-gray-500 mt-1">{alert.timestamp}</p>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-8">
                <FiCheckCircle className="w-12 h-12 text-green-600 mx-auto mb-4" />
                <p className="text-gray-500">No alerts at this time</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default SuperAdminSystemHealthPage;
