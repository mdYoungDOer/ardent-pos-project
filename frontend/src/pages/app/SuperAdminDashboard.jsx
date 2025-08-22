import React, { useState, useEffect } from 'react';
import {
  FiUsers, FiDollarSign, FiTrendingUp, FiTrendingDown, FiAlertCircle,
  FiBarChart2, FiMapPin, FiActivity, FiShield, FiSettings, FiDatabase,
  FiCreditCard, FiPackage, FiShoppingCart, FiUserCheck, FiUserX,
  FiCalendar, FiClock, FiStar, FiAward, FiTarget, FiPieChart,
  FiGrid, FiList, FiRefreshCw, FiDownload, FiFilter, FiSearch
} from 'react-icons/fi';
import useAuthStore from '../../stores/authStore';
import { superAdminAPI } from '../../services/api';

const SuperAdminDashboard = () => {
  const { user } = useAuthStore();
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState(null);
  const [recentActivity, setRecentActivity] = useState([]);
  const [topTenants, setTopTenants] = useState([]);
  const [systemHealth, setSystemHealth] = useState({});
  const [viewMode, setViewMode] = useState('grid'); // grid or list
  const [timeRange, setTimeRange] = useState('30'); // days
  const [error, setError] = useState(null);

  // Fetch real data from API
  const fetchSuperAdminData = async () => {
    setLoading(true);
    try {
      // Fetch system stats
      const statsResponse = await superAdminAPI.getStats();
      if (statsResponse.data.success) {
        setStats(statsResponse.data.data);
      }

      // Fetch recent activity
      const activityResponse = await superAdminAPI.getActivity();
      if (activityResponse.data.success) {
        setRecentActivity(activityResponse.data.data);
      }

      // Fetch top tenants
      const tenantsResponse = await superAdminAPI.getTenants({ limit: 5 });
      if (tenantsResponse.data.success) {
        setTopTenants(tenantsResponse.data.data.tenants);
      }

      // System health is included in stats response
      if (statsResponse.data.success && statsResponse.data.data.systemHealth) {
        setSystemHealth(statsResponse.data.data.systemHealth);
      }

    } catch (error) {
      console.error('Error fetching super admin data:', error);
      setError('Failed to load dashboard data');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchSuperAdminData();
  }, [timeRange]);

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-GH', {
      style: 'currency',
      currency: 'GHS'
    }).format(amount);
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'active': return 'text-green-600 bg-green-100';
      case 'suspended': return 'text-red-600 bg-red-100';
      case 'pending': return 'text-yellow-600 bg-yellow-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  const getActivityIcon = (type) => {
    switch (type) {
      case 'tenant_created': return <FiUsers className="h-4 w-4" />;
      case 'payment_received': return <FiDollarSign className="h-4 w-4" />;
      case 'system_alert': return <FiAlertCircle className="h-4 w-4" />;
      case 'user_suspended': return <FiUserX className="h-4 w-4" />;
      case 'backup_completed': return <FiDatabase className="h-4 w-4" />;
      default: return <FiActivity className="h-4 w-4" />;
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#e41e5b]"></div>
        <span className="ml-3 text-[#746354]">Loading Super Admin Dashboard...</span>
      </div>
    );
  }

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      {/* Error Display */}
      {error && (
        <div className="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
          <div className="flex items-center">
            <FiAlertCircle className="h-5 w-5 text-red-500 mr-2" />
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
      
      {/* Header */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-[#2c2c2c]">Super Admin Dashboard</h1>
            <p className="text-[#746354] mt-1">
              Enterprise-wide overview and management console
            </p>
          </div>
          <div className="flex items-center space-x-4">
            <select
              className="px-4 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
              value={timeRange}
              onChange={(e) => setTimeRange(e.target.value)}
            >
              <option value="7">Last 7 days</option>
              <option value="30">Last 30 days</option>
              <option value="90">Last 90 days</option>
              <option value="365">Last year</option>
            </select>
                         <button 
               onClick={() => {
                 setTimeRange('30');
                 fetchSuperAdminData();
               }}
               className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors"
             >
               <FiRefreshCw className="h-4 w-4 mr-2" />
               Refresh
             </button>
          </div>
        </div>
      </div>

      {/* Key Metrics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">Total Tenants</p>
                             <p className="text-2xl font-bold text-[#2c2c2c]">{stats?.totalTenants || 0}</p>
              <p className="text-xs text-green-600 mt-1">
                <FiTrendingUp className="inline h-3 w-3 mr-1" />
                                 +{stats?.monthlyGrowth || 0}% this month
              </p>
            </div>
            <div className="w-12 h-12 bg-[#e41e5b]/10 rounded-xl flex items-center justify-center">
              <FiUsers className="h-6 w-6 text-[#e41e5b]" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">Total Revenue</p>
                             <p className="text-2xl font-bold text-[#2c2c2c]">{formatCurrency(stats?.totalRevenue || 0)}</p>
              <p className="text-xs text-green-600 mt-1">
                <FiTrendingUp className="inline h-3 w-3 mr-1" />
                +8.2% vs last month
              </p>
            </div>
            <div className="w-12 h-12 bg-[#9a0864]/10 rounded-xl flex items-center justify-center">
              <FiDollarSign className="h-6 w-6 text-[#9a0864]" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">System Uptime</p>
                             <p className="text-2xl font-bold text-[#2c2c2c]">{stats?.systemUptime || 99.8}%</p>
              <p className="text-xs text-green-600 mt-1">
                <FiActivity className="inline h-3 w-3 mr-1" />
                All systems operational
              </p>
            </div>
            <div className="w-12 h-12 bg-[#a67c00]/10 rounded-xl flex items-center justify-center">
              <FiShield className="h-6 w-6 text-[#a67c00]" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">Pending Actions</p>
                             <p className="text-2xl font-bold text-[#2c2c2c]">{stats?.pendingApprovals || 0}</p>
              <p className="text-xs text-yellow-600 mt-1">
                <FiAlertCircle className="inline h-3 w-3 mr-1" />
                                 {stats?.criticalIssues || 0} critical issues
              </p>
            </div>
            <div className="w-12 h-12 bg-[#746354]/10 rounded-xl flex items-center justify-center">
              <FiSettings className="h-6 w-6 text-[#746354]" />
            </div>
          </div>
        </div>
      </div>

      {/* System Health & Quick Actions */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {/* System Health */}
        <div className="lg:col-span-2 bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <h3 className="text-lg font-semibold text-[#2c2c2c] mb-4">System Health</h3>
          <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div className="text-center">
              <div className="w-16 h-16 mx-auto mb-2 bg-[#e41e5b]/10 rounded-full flex items-center justify-center">
                <FiActivity className="h-6 w-6 text-[#e41e5b]" />
              </div>
              <p className="text-sm font-medium text-[#2c2c2c]">CPU</p>
                             <p className="text-lg font-bold text-[#e41e5b]">{systemHealth?.cpu || 0}%</p>
            </div>
            <div className="text-center">
              <div className="w-16 h-16 mx-auto mb-2 bg-[#9a0864]/10 rounded-full flex items-center justify-center">
                <FiDatabase className="h-6 w-6 text-[#9a0864]" />
              </div>
              <p className="text-sm font-medium text-[#2c2c2c]">Memory</p>
                             <p className="text-lg font-bold text-[#9a0864]">{systemHealth?.memory || 0}%</p>
            </div>
            <div className="text-center">
              <div className="w-16 h-16 mx-auto mb-2 bg-[#a67c00]/10 rounded-full flex items-center justify-center">
                <FiBarChart2 className="h-6 w-6 text-[#a67c00]" />
              </div>
              <p className="text-sm font-medium text-[#2c2c2c]">Disk</p>
                             <p className="text-lg font-bold text-[#a67c00]">{systemHealth?.disk || 0}%</p>
            </div>
            <div className="text-center">
              <div className="w-16 h-16 mx-auto mb-2 bg-[#746354]/10 rounded-full flex items-center justify-center">
                <FiTarget className="h-6 w-6 text-[#746354]" />
              </div>
              <p className="text-sm font-medium text-[#2c2c2c]">Network</p>
                             <p className="text-lg font-bold text-[#746354]">{systemHealth?.network || 0}%</p>
            </div>
            <div className="text-center">
              <div className="w-16 h-16 mx-auto mb-2 bg-green-100 rounded-full flex items-center justify-center">
                <FiDatabase className="h-6 w-6 text-green-600" />
              </div>
              <p className="text-sm font-medium text-[#2c2c2c]">Database</p>
                             <p className="text-lg font-bold text-green-600">{systemHealth?.database || 99.9}%</p>
            </div>
            <div className="text-center">
              <div className="w-16 h-16 mx-auto mb-2 bg-blue-100 rounded-full flex items-center justify-center">
                <FiBarChart2 className="h-6 w-6 text-blue-600" />
              </div>
              <p className="text-sm font-medium text-[#2c2c2c]">API</p>
                             <p className="text-lg font-bold text-blue-600">{systemHealth?.api || 99.7}%</p>
            </div>
          </div>
        </div>

        {/* Quick Actions */}
        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <h3 className="text-lg font-semibold text-[#2c2c2c] mb-4">Quick Actions</h3>
          <div className="space-y-3">
            <button className="w-full flex items-center px-4 py-3 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors">
              <FiUsers className="h-4 w-4 mr-3" />
              Manage Tenants
            </button>
            <button className="w-full flex items-center px-4 py-3 bg-[#9a0864] text-white rounded-lg hover:bg-[#746354] transition-colors">
              <FiSettings className="h-4 w-4 mr-3" />
              System Settings
            </button>
            <button className="w-full flex items-center px-4 py-3 bg-[#a67c00] text-white rounded-lg hover:bg-[#8b6b00] transition-colors">
              <FiShield className="h-4 w-4 mr-3" />
              Security Center
            </button>
            <button className="w-full flex items-center px-4 py-3 bg-[#746354] text-white rounded-lg hover:bg-[#5a4d42] transition-colors">
              <FiDownload className="h-4 w-4 mr-3" />
              Export Reports
            </button>
          </div>
        </div>
      </div>

      {/* Top Tenants & Recent Activity */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {/* Top Tenants */}
        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold text-[#2c2c2c]">Top Performing Tenants</h3>
            <div className="flex items-center space-x-2">
              <button
                onClick={() => setViewMode('grid')}
                className={`p-2 rounded-lg ${viewMode === 'grid' ? 'bg-[#e41e5b] text-white' : 'text-[#746354] hover:bg-gray-100'}`}
              >
                <FiGrid className="h-4 w-4" />
              </button>
              <button
                onClick={() => setViewMode('list')}
                className={`p-2 rounded-lg ${viewMode === 'list' ? 'bg-[#e41e5b] text-white' : 'text-[#746354] hover:bg-gray-100'}`}
              >
                <FiList className="h-4 w-4" />
              </button>
            </div>
          </div>
          <div className="space-y-4">
                         {topTenants?.map((tenant) => (
              <div key={tenant.id} className="flex items-center justify-between p-4 border border-[#746354]/10 rounded-lg hover:bg-gray-50 transition-colors">
                <div className="flex items-center">
                  <div className="w-10 h-10 bg-[#e41e5b]/10 rounded-lg flex items-center justify-center mr-3">
                    <FiUsers className="h-5 w-5 text-[#e41e5b]" />
                  </div>
                  <div>
                    <div className="text-sm font-semibold text-[#2c2c2c]">{tenant.name}</div>
                    <div className="text-xs text-[#746354]">{tenant.users} users</div>
                  </div>
                </div>
                <div className="text-right">
                  <div className="text-sm font-semibold text-[#e41e5b]">{formatCurrency(tenant.revenue)}</div>
                  <div className={`text-xs px-2 py-1 rounded-full ${getStatusColor(tenant.status)}`}>
                    {tenant.status}
                  </div>
                  <div className={`text-xs ${tenant.growth >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                    {tenant.growth >= 0 ? '+' : ''}{tenant.growth}%
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Recent Activity */}
        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <h3 className="text-lg font-semibold text-[#2c2c2c] mb-4">Recent Activity</h3>
          <div className="space-y-4">
                         {recentActivity?.map((activity) => (
              <div key={activity.id} className="flex items-start space-x-3">
                <div className={`w-8 h-8 rounded-full flex items-center justify-center ${
                  activity.status === 'success' ? 'bg-green-100' :
                  activity.status === 'warning' ? 'bg-yellow-100' :
                  'bg-red-100'
                }`}>
                  {getActivityIcon(activity.type)}
                </div>
                <div className="flex-1">
                  <p className="text-sm text-[#2c2c2c]">{activity.message}</p>
                  <p className="text-xs text-[#746354]">{activity.time}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Analytics Overview */}
      <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
        <div className="flex items-center justify-between mb-6">
          <h3 className="text-lg font-semibold text-[#2c2c2c]">Analytics Overview</h3>
          <div className="flex items-center space-x-2">
            <button className="flex items-center px-3 py-1 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors text-sm">
              <FiDownload className="h-4 w-4 mr-1" />
              Export
            </button>
            <button className="flex items-center px-3 py-1 bg-gray-100 text-[#2c2c2c] rounded-lg hover:bg-gray-200 transition-colors text-sm">
              <FiFilter className="h-4 w-4 mr-1" />
              Filter
            </button>
          </div>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div className="text-center p-4 border border-[#746354]/10 rounded-lg">
            <div className="w-12 h-12 mx-auto mb-3 bg-[#e41e5b]/10 rounded-full flex items-center justify-center">
              <FiShoppingCart className="h-6 w-6 text-[#e41e5b]" />
            </div>
            <p className="text-sm font-medium text-[#746354]">Total Transactions</p>
            <p className="text-2xl font-bold text-[#2c2c2c]">45,892</p>
            <p className="text-xs text-green-600">+12.3% vs last month</p>
          </div>
          
          <div className="text-center p-4 border border-[#746354]/10 rounded-lg">
            <div className="w-12 h-12 mx-auto mb-3 bg-[#9a0864]/10 rounded-full flex items-center justify-center">
              <FiUserCheck className="h-6 w-6 text-[#9a0864]" />
            </div>
            <p className="text-sm font-medium text-[#746354]">Active Users</p>
            <p className="text-2xl font-bold text-[#2c2c2c]">2,847</p>
            <p className="text-xs text-green-600">+8.7% vs last month</p>
          </div>
          
          <div className="text-center p-4 border border-[#746354]/10 rounded-lg">
            <div className="w-12 h-12 mx-auto mb-3 bg-[#a67c00]/10 rounded-full flex items-center justify-center">
              <FiPackage className="h-6 w-6 text-[#a67c00]" />
            </div>
            <p className="text-sm font-medium text-[#746354]">Products Sold</p>
            <p className="text-2xl font-bold text-[#2c2c2c]">156,234</p>
            <p className="text-xs text-green-600">+15.2% vs last month</p>
          </div>
          
          <div className="text-center p-4 border border-[#746354]/10 rounded-lg">
            <div className="w-12 h-12 mx-auto mb-3 bg-[#746354]/10 rounded-full flex items-center justify-center">
              <FiStar className="h-6 w-6 text-[#746354]" />
            </div>
            <p className="text-sm font-medium text-[#746354]">Customer Rating</p>
            <p className="text-2xl font-bold text-[#2c2c2c]">4.8/5</p>
            <p className="text-xs text-green-600">+0.2 vs last month</p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SuperAdminDashboard;
