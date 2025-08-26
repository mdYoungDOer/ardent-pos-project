import React, { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { 
  FiBarChart2, FiTrendingUp, FiTrendingDown, FiDollarSign, FiPackage, FiUsers, FiCalendar, FiDownload,
  FiShoppingCart, FiUserCheck, FiStar, FiActivity, FiAlertCircle, FiRefreshCw
} from 'react-icons/fi';
import { dashboardAPI } from '../../services/api';
import useAuthStore from '../../stores/authStore';

const DashboardPage = () => {
  const { user, isAuthenticated } = useAuthStore();
  const navigate = useNavigate();
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [timeRange, setTimeRange] = useState('30');
  const [debugInfo, setDebugInfo] = useState({});

  // Debug authentication state
  useEffect(() => {
    console.log('DashboardPage - Authentication State:', {
      isAuthenticated,
      user,
      hasToken: !!localStorage.getItem('token')
    });
    
    setDebugInfo({
      isAuthenticated,
      userRole: user?.role,
      hasToken: !!localStorage.getItem('token'),
      timestamp: new Date().toISOString()
    });
  }, [isAuthenticated, user]);

  // Redirect super admin to super admin dashboard
  useEffect(() => {
    if (user?.role === 'super_admin') {
      console.log('Redirecting Super Admin to /super-admin/dashboard');
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
        console.log('Dashboard stats loaded successfully:', response.data.data);
      } else {
        const errorMsg = response.data.message || 'Unknown error';
        console.error('Dashboard API returned error:', errorMsg);
        setError(`Failed to load dashboard: ${errorMsg}`);
      }
    } catch (err) {
      console.error('Dashboard fetch error:', err);
      setError(`Error loading dashboard: ${err.message || 'Network error'}`);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    // Only fetch stats if user is authenticated and not a super admin
    if (isAuthenticated && user?.role !== 'super_admin') {
      console.log('User authenticated, fetching dashboard stats');
      fetchStats();
    } else if (!isAuthenticated) {
      console.log('User not authenticated, skipping dashboard fetch');
      setLoading(false);
    }
  }, [isAuthenticated, user, timeRange]);

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

  // Show authentication error if not authenticated
  if (!isAuthenticated) {
    return (
      <div className="p-6 bg-gray-50 min-h-screen">
        <div className="flex items-center justify-center p-8">
          <div className="text-center">
            <div className="text-red-500 mb-4">
              <FiAlertCircle className="h-12 w-12 mx-auto" />
            </div>
            <h3 className="text-lg font-semibold text-[#2c2c2c] mb-2">Authentication Required</h3>
            <p className="text-[#746354] mb-4">Please log in to access the dashboard</p>
            <button 
              onClick={() => navigate('/auth/login')}
              className="px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors"
            >
              Go to Login
            </button>
          </div>
        </div>
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
            
            {/* Debug Information */}
            <div className="mt-6 p-4 bg-gray-100 rounded-lg text-left">
              <h4 className="font-medium text-[#2c2c2c] mb-2">Debug Information:</h4>
              <pre className="text-xs text-[#746354] overflow-auto">
                {JSON.stringify(debugInfo, null, 2)}
              </pre>
            </div>
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
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {/* Recent Sales */}
        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold text-[#2c2c2c]">Recent Sales</h3>
            <Link 
              to="/app/sales" 
              className="text-sm text-[#e41e5b] hover:text-[#9a0864] transition-colors"
            >
              View All
            </Link>
          </div>
          <div className="space-y-3">
            {stats?.recentSales?.length > 0 ? (
              stats.recentSales.map((sale) => (
                <div key={sale.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                  <div>
                    <p className="font-medium text-[#2c2c2c]">
                      {sale.first_name} {sale.last_name}
                    </p>
                    <p className="text-sm text-[#746354]">{formatDate(sale.created_at)}</p>
                  </div>
                  <div className="text-right">
                    <p className="font-semibold text-[#2c2c2c]">{formatCurrency(sale.total_amount)}</p>
                  </div>
                </div>
              ))
            ) : (
              <div className="text-center py-8 text-[#746354]">
                <FiShoppingCart className="h-12 w-12 mx-auto mb-3 text-[#746354]/30" />
                <p>No recent sales</p>
              </div>
            )}
          </div>
        </div>

        {/* Low Stock Alert */}
        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold text-[#2c2c2c]">Low Stock Alert</h3>
            <Link 
              to="/app/inventory" 
              className="text-sm text-[#e41e5b] hover:text-[#9a0864] transition-colors"
            >
              View All
            </Link>
          </div>
          <div className="space-y-3">
            {stats?.lowStockProducts?.length > 0 ? (
              stats.lowStockProducts.map((product) => (
                <div key={product.id} className="flex items-center justify-between p-3 bg-red-50 rounded-lg border border-red-200">
                  <div>
                    <p className="font-medium text-[#2c2c2c]">{product.name}</p>
                    <p className="text-sm text-[#746354]">{formatCurrency(product.price)}</p>
                  </div>
                  <div className="text-right">
                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                      product.stock === 0 
                        ? 'bg-red-100 text-red-800' 
                        : 'bg-yellow-100 text-yellow-800'
                    }`}>
                      {product.stock === 0 ? 'Out of Stock' : `${product.stock} left`}
                    </span>
                  </div>
                </div>
              ))
            ) : (
              <div className="text-center py-8 text-[#746354]">
                <FiPackage className="h-12 w-12 mx-auto mb-3 text-[#746354]/30" />
                <p>All products well stocked</p>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
        <h3 className="text-lg font-semibold text-[#2c2c2c] mb-4">Quick Actions</h3>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <Link 
            to="/app/pos" 
            className="flex flex-col items-center p-4 bg-[#e41e5b]/10 rounded-lg hover:bg-[#e41e5b]/20 transition-colors"
          >
            <FiShoppingCart className="h-8 w-8 text-[#e41e5b] mb-2" />
            <span className="text-sm font-medium text-[#2c2c2c]">New Sale</span>
          </Link>
          
          <Link 
            to="/app/products" 
            className="flex flex-col items-center p-4 bg-[#9a0864]/10 rounded-lg hover:bg-[#9a0864]/20 transition-colors"
          >
            <FiPackage className="h-8 w-8 text-[#9a0864] mb-2" />
            <span className="text-sm font-medium text-[#2c2c2c]">Add Product</span>
          </Link>
          
          <Link 
            to="/app/customers" 
            className="flex flex-col items-center p-4 bg-[#a67c00]/10 rounded-lg hover:bg-[#a67c00]/20 transition-colors"
          >
            <FiUsers className="h-8 w-8 text-[#a67c00] mb-2" />
            <span className="text-sm font-medium text-[#2c2c2c]">Add Customer</span>
          </Link>
          
          <Link 
            to="/app/reports" 
            className="flex flex-col items-center p-4 bg-[#746354]/10 rounded-lg hover:bg-[#746354]/20 transition-colors"
          >
            <FiBarChart2 className="h-8 w-8 text-[#746354] mb-2" />
            <span className="text-sm font-medium text-[#2c2c2c]">View Reports</span>
          </Link>
        </div>
      </div>
    </div>
  );
};

export default DashboardPage;
