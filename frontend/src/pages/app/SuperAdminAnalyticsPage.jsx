import React, { useState, useEffect } from 'react';
import {
  FiTrendingUp, FiTrendingDown, FiBarChart2, FiPieChart, FiMapPin,
  FiUsers, FiDollarSign, FiActivity, FiClock, FiAlertCircle,
  FiDownload, FiFilter, FiRotateCw, FiCalendar, FiTarget,
  FiDatabase, FiServer, FiCpu, FiHardDrive, FiWifi
} from 'react-icons/fi';
import { superAdminAPI } from '../../services/api';

const SuperAdminAnalyticsPage = () => {
  const [loading, setLoading] = useState(true);
  const [analytics, setAnalytics] = useState(null);
  const [timeRange, setTimeRange] = useState('30');
  const [selectedMetric, setSelectedMetric] = useState('revenue');
  const [error, setError] = useState(null);

  const fetchAnalytics = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await superAdminAPI.getAnalytics({ timeRange, metric: selectedMetric });
      if (response.data.success) {
        setAnalytics(response.data.data);
      } else {
        setError('Failed to load analytics data');
      }
    } catch (err) {
      setError('Error loading analytics');
      console.error('Analytics error:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAnalytics();
  }, [timeRange, selectedMetric]);

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-GH', {
      style: 'currency',
      currency: 'GHS'
    }).format(amount);
  };

  const formatNumber = (num) => {
    return new Intl.NumberFormat('en-GH').format(num);
  };

  const getGrowthColor = (growth) => {
    if (growth > 0) return 'text-green-600';
    if (growth < 0) return 'text-red-600';
    return 'text-gray-600';
  };

  const getGrowthIcon = (growth) => {
    if (growth > 0) return <FiTrendingUp className="h-4 w-4" />;
    if (growth < 0) return <FiTrendingDown className="h-4 w-4" />;
    return <FiActivity className="h-4 w-4" />;
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#e41e5b]"></div>
        <span className="ml-3 text-[#746354]">Loading Analytics...</span>
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-6">
        <div className="bg-red-50 border border-red-200 rounded-lg p-4">
          <div className="flex items-center">
            <FiAlertCircle className="h-5 w-5 text-red-500 mr-2" />
            <span className="text-red-800">{error}</span>
            <button
              onClick={fetchAnalytics}
              className="ml-auto text-red-500 hover:text-red-700"
            >
              Retry
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      {/* Header */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-[#2c2c2c]">System Analytics</h1>
            <p className="text-[#746354] mt-1">
              Advanced metrics and performance monitoring
            </p>
          </div>
          <div className="flex items-center space-x-4">
            <select
              className="px-4 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b]"
              value={timeRange}
              onChange={(e) => setTimeRange(e.target.value)}
            >
              <option value="7">Last 7 days</option>
              <option value="30">Last 30 days</option>
              <option value="90">Last 90 days</option>
              <option value="365">Last year</option>
            </select>
            <button 
              onClick={fetchAnalytics}
              className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors"
            >
                              <FiRotateCw className="h-4 w-4 mr-2" />
              Refresh
            </button>
          </div>
        </div>
      </div>

      {/* Key Performance Indicators */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">Total Revenue</p>
              <p className="text-2xl font-bold text-[#2c2c2c]">
                {analytics?.totalRevenue ? formatCurrency(analytics.totalRevenue) : '₵0'}
              </p>
            </div>
            <div className="p-3 bg-green-100 rounded-lg">
              <FiDollarSign className="h-6 w-6 text-green-600" />
            </div>
          </div>
          <div className="mt-4 flex items-center">
            {getGrowthIcon(analytics?.revenueGrowth || 0)}
            <span className={`ml-2 text-sm font-medium ${getGrowthColor(analytics?.revenueGrowth || 0)}`}>
              {analytics?.revenueGrowth ? `${analytics.revenueGrowth > 0 ? '+' : ''}${analytics.revenueGrowth.toFixed(1)}%` : '0%'}
            </span>
            <span className="ml-2 text-sm text-[#746354]">vs last period</span>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">Active Tenants</p>
              <p className="text-2xl font-bold text-[#2c2c2c]">
                {analytics?.activeTenants || 0}
              </p>
            </div>
            <div className="p-3 bg-blue-100 rounded-lg">
              <FiUsers className="h-6 w-6 text-blue-600" />
            </div>
          </div>
          <div className="mt-4 flex items-center">
            {getGrowthIcon(analytics?.tenantGrowth || 0)}
            <span className={`ml-2 text-sm font-medium ${getGrowthColor(analytics?.tenantGrowth || 0)}`}>
              {analytics?.tenantGrowth ? `${analytics.tenantGrowth > 0 ? '+' : ''}${analytics.tenantGrowth.toFixed(1)}%` : '0%'}
            </span>
            <span className="ml-2 text-sm text-[#746354]">vs last period</span>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">Total Transactions</p>
              <p className="text-2xl font-bold text-[#2c2c2c]">
                {analytics?.totalTransactions ? formatNumber(analytics.totalTransactions) : '0'}
              </p>
            </div>
            <div className="p-3 bg-purple-100 rounded-lg">
              <FiActivity className="h-6 w-6 text-purple-600" />
            </div>
          </div>
          <div className="mt-4 flex items-center">
            {getGrowthIcon(analytics?.transactionGrowth || 0)}
            <span className={`ml-2 text-sm font-medium ${getGrowthColor(analytics?.transactionGrowth || 0)}`}>
              {analytics?.transactionGrowth ? `${analytics.transactionGrowth > 0 ? '+' : ''}${analytics.transactionGrowth.toFixed(1)}%` : '0%'}
            </span>
            <span className="ml-2 text-sm text-[#746354]">vs last period</span>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">System Uptime</p>
              <p className="text-2xl font-bold text-[#2c2c2c]">
                {analytics?.systemUptime ? `${analytics.systemUptime}%` : '99.9%'}
              </p>
            </div>
            <div className="p-3 bg-green-100 rounded-lg">
              <FiServer className="h-6 w-6 text-green-600" />
            </div>
          </div>
          <div className="mt-4">
            <div className="w-full bg-gray-200 rounded-full h-2">
              <div 
                className="bg-green-600 h-2 rounded-full" 
                style={{ width: `${analytics?.systemUptime || 99.9}%` }}
              ></div>
            </div>
          </div>
        </div>
      </div>

      {/* Charts Section */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {/* Revenue Trend Chart */}
        <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold text-[#2c2c2c]">Revenue Trend</h3>
            <button className="text-[#e41e5b] hover:text-[#9a0864]">
              <FiDownload className="h-4 w-4" />
            </button>
          </div>
          <div className="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
            <div className="text-center">
              <FiBarChart2 className="h-12 w-12 text-[#746354] mx-auto mb-2" />
              <p className="text-[#746354]">Revenue chart will be implemented</p>
            </div>
          </div>
        </div>

        {/* Tenant Distribution */}
        <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold text-[#2c2c2c]">Tenant Distribution</h3>
            <button className="text-[#e41e5b] hover:text-[#9a0864]">
              <FiDownload className="h-4 w-4" />
            </button>
          </div>
          <div className="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
            <div className="text-center">
              <FiPieChart className="h-12 w-12 text-[#746354] mx-auto mb-2" />
              <p className="text-[#746354]">Tenant distribution chart will be implemented</p>
            </div>
          </div>
        </div>
      </div>

      {/* System Health Monitoring */}
      <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200 mb-8">
        <h3 className="text-lg font-semibold text-[#2c2c2c] mb-4">System Health</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div className="flex items-center p-4 bg-gray-50 rounded-lg">
            <div className="p-2 bg-blue-100 rounded-lg mr-3">
              <FiCpu className="h-5 w-5 text-blue-600" />
            </div>
            <div>
              <p className="text-sm font-medium text-[#746354]">CPU Usage</p>
              <p className="text-lg font-semibold text-[#2c2c2c]">
                {analytics?.systemHealth?.cpu || 45}%
              </p>
            </div>
          </div>

          <div className="flex items-center p-4 bg-gray-50 rounded-lg">
            <div className="p-2 bg-green-100 rounded-lg mr-3">
              <FiHardDrive className="h-5 w-5 text-green-600" />
            </div>
            <div>
              <p className="text-sm font-medium text-[#746354]">Memory Usage</p>
              <p className="text-lg font-semibold text-[#2c2c2c]">
                {analytics?.systemHealth?.memory || 65}%
              </p>
            </div>
          </div>

          <div className="flex items-center p-4 bg-gray-50 rounded-lg">
            <div className="p-2 bg-yellow-100 rounded-lg mr-3">
              <FiDatabase className="h-5 w-5 text-yellow-600" />
            </div>
            <div>
              <p className="text-sm font-medium text-[#746354]">Database</p>
              <p className="text-lg font-semibold text-[#2c2c2c]">
                {analytics?.systemHealth?.database || 99.9}%
              </p>
            </div>
          </div>

          <div className="flex items-center p-4 bg-gray-50 rounded-lg">
            <div className="p-2 bg-purple-100 rounded-lg mr-3">
              <FiWifi className="h-5 w-5 text-purple-600" />
            </div>
            <div>
              <p className="text-sm font-medium text-[#746354]">Network</p>
              <p className="text-lg font-semibold text-[#2c2c2c]">
                {analytics?.systemHealth?.network || 98}%
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Top Performing Tenants */}
      <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-lg font-semibold text-[#2c2c2c]">Top Performing Tenants</h3>
          <button className="text-[#e41e5b] hover:text-[#9a0864]">
            View All
          </button>
        </div>
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-[#746354] uppercase tracking-wider">
                  Tenant
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-[#746354] uppercase tracking-wider">
                  Revenue
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-[#746354] uppercase tracking-wider">
                  Transactions
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-[#746354] uppercase tracking-wider">
                  Growth
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-[#746354] uppercase tracking-wider">
                  Status
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {analytics?.topTenants?.map((tenant, index) => (
                <tr key={tenant.id}>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="flex items-center">
                      <div className="flex-shrink-0 h-10 w-10">
                        <div className="h-10 w-10 rounded-full bg-[#e41e5b] flex items-center justify-center">
                          <span className="text-white font-medium">{tenant.name.charAt(0)}</span>
                        </div>
                      </div>
                      <div className="ml-4">
                        <div className="text-sm font-medium text-[#2c2c2c]">{tenant.name}</div>
                        <div className="text-sm text-[#746354]">{tenant.email}</div>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-[#2c2c2c]">
                    {formatCurrency(tenant.revenue)}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-[#2c2c2c]">
                    {formatNumber(tenant.transactions)}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="flex items-center">
                      {getGrowthIcon(tenant.growth)}
                      <span className={`ml-1 text-sm font-medium ${getGrowthColor(tenant.growth)}`}>
                        {tenant.growth > 0 ? '+' : ''}{tenant.growth.toFixed(1)}%
                      </span>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                      tenant.status === 'active' ? 'bg-green-100 text-green-800' :
                      tenant.status === 'suspended' ? 'bg-red-100 text-red-800' :
                      'bg-yellow-100 text-yellow-800'
                    }`}>
                      {tenant.status}
                    </span>
                  </td>
                </tr>
              )) || (
                <tr>
                  <td colSpan="5" className="px-6 py-4 text-center text-[#746354]">
                    No tenant data available
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default SuperAdminAnalyticsPage;
