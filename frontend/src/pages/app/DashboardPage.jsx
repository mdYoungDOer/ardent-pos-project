import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { 
  FiBarChart2, FiTrendingUp, FiTrendingDown, FiDollarSign, FiPackage, FiUsers, FiCalendar, FiDownload,
  FiShoppingCart, FiUserCheck, FiStar, FiActivity, FiAlertCircle, FiRefreshCw
} from 'react-icons/fi';
import { dashboardAPI } from '../../services/api';
import useAuthStore from '../../stores/authStore';

const DashboardPage = () => {
  const { user } = useAuthStore();
  const navigate = useNavigate();
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [timeRange, setTimeRange] = useState('30'); // days

  // Redirect super admin to super admin dashboard
  useEffect(() => {
    if (user?.role === 'super_admin') {
      navigate('/super-admin/dashboard');
      return;
    }
  }, [user, navigate]);

  const fetchStats = async () => {
    try {
      setLoading(true);
      setError(null);
      console.log('Fetching dashboard stats for user:', user);
      const response = await dashboardAPI.getStats();
      console.log('Dashboard API response:', response);
      if (response.data.success) {
        setStats(response.data.data);
      } else {
        setError('Failed to load dashboard: ' + (response.data.message || 'Unknown error'));
      }
    } catch (err) {
      console.error('Dashboard error:', err);
      setError('Error loading dashboard: ' + (err.message || 'Network error'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    // Always fetch stats for non-super-admin users, even if user is not loaded yet
    if (!user || user?.role !== 'super_admin') {
      fetchStats();
    }
  }, [timeRange, user]);

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-GH', {
      style: 'currency',
      currency: 'GHS'
    }).format(amount);
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-GH', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  // Don't render anything if user is super admin (will be redirected)
  if (user?.role === 'super_admin') {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#e41e5b]"></div>
        <span className="ml-3 text-[#746354]">Redirecting to Super Admin Dashboard...</span>
      </div>
    );
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#e41e5b]"></div>
        <span className="ml-3 text-[#746354]">Loading dashboard...</span>
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-6 bg-gray-50 min-h-screen">
        <div className="flex items-center justify-center p-8">
          <div className="text-center">
            <div className="text-red-500 mb-4">
              <FiAlertCircle className="h-12 w-12 mx-auto" />
            </div>
            <h3 className="text-lg font-semibold text-[#2c2c2c] mb-2">Dashboard Error</h3>
            <p className="text-[#746354] mb-4">{error}</p>
            <button 
              onClick={fetchStats}
              className="px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors"
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
            <h1 className="text-3xl font-bold text-[#2c2c2c]">Dashboard</h1>
            <p className="text-[#746354] mt-1">
              Welcome back, {user?.first_name}! Here's what's happening with your business today.
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
              onClick={fetchStats}
              className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors"
            >
              <FiRefreshCw className="h-4 w-4 mr-2" />
              Refresh
            </button>
          </div>
        </div>
      </div>

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

      {/* Key Metrics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">Total Sales</p>
              <p className="text-2xl font-bold text-[#2c2c2c]">{formatCurrency(stats?.totalSales || 0)}</p>
              <p className="text-xs text-green-600 mt-1">
                <FiTrendingUp className="inline h-3 w-3 mr-1" />
                +{stats?.salesGrowth || 0}% vs last period
              </p>
            </div>
            <div className="w-12 h-12 bg-[#e41e5b]/10 rounded-xl flex items-center justify-center">
              <FiDollarSign className="h-6 w-6 text-[#e41e5b]" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">Total Orders</p>
              <p className="text-2xl font-bold text-[#2c2c2c]">{stats?.totalOrders || 0}</p>
              <p className="text-xs text-green-600 mt-1">
                <FiTrendingUp className="inline h-3 w-3 mr-1" />
                +{stats?.ordersGrowth || 0}% vs last period
              </p>
            </div>
            <div className="w-12 h-12 bg-[#e41e5b]/10 rounded-xl flex items-center justify-center">
              <FiShoppingCart className="h-6 w-6 text-[#e41e5b]" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">Total Products</p>
              <p className="text-2xl font-bold text-[#2c2c2c]">{stats?.totalProducts || 0}</p>
              <p className="text-xs text-green-600 mt-1">
                <FiTrendingUp className="inline h-3 w-3 mr-1" />
                +{stats?.productsGrowth || 0}% vs last period
              </p>
            </div>
            <div className="w-12 h-12 bg-[#e41e5b]/10 rounded-xl flex items-center justify-center">
              <FiPackage className="h-6 w-6 text-[#e41e5b]" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[#746354]">Total Customers</p>
              <p className="text-2xl font-bold text-[#2c2c2c]">{stats?.totalCustomers || 0}</p>
              <p className="text-xs text-green-600 mt-1">
                <FiTrendingUp className="inline h-3 w-3 mr-1" />
                +{stats?.customersGrowth || 0}% vs last period
              </p>
            </div>
            <div className="w-12 h-12 bg-[#e41e5b]/10 rounded-xl flex items-center justify-center">
              <FiUsers className="h-6 w-6 text-[#e41e5b]" />
            </div>
          </div>
        </div>
      </div>

      {/* Dashboard Content */}
      <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
        <h2 className="text-xl font-semibold text-[#2c2c2c] mb-4">Dashboard Loaded Successfully</h2>
        <p className="text-[#746354]">Your business dashboard is now working properly!</p>
        {stats && (
          <div className="mt-4 p-4 bg-gray-50 rounded-lg">
            <h3 className="font-medium text-[#2c2c2c] mb-2">Current Stats:</h3>
            <pre className="text-sm text-[#746354] overflow-auto">
              {JSON.stringify(stats, null, 2)}
            </pre>
          </div>
        )}
      </div>
    </div>
  );
};

export default DashboardPage;
