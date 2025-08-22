import React, { useState, useEffect } from 'react';
import { FiBarChart2, FiShoppingCart, FiUsers, FiPackage, FiTrendingUp, FiDollarSign, FiAlertTriangle, FiRefreshCw } from 'react-icons/fi';
import useAuthStore from '../../stores/authStore';
import { dashboardAPI } from '../../services/api';

const DashboardPage = () => {
  const { user, tenant } = useAuthStore();
  const [stats, setStats] = useState({
    totalSales: 0,
    totalOrders: 0,
    totalCustomers: 0,
    totalProducts: 0,
    recentSales: [],
    topProducts: [],
    monthlyTrend: [],
    lowStockProducts: []
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchDashboardData = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await dashboardAPI.getStats();
      if (response.data.success) {
        setStats(response.data.data);
      } else {
        setError('Failed to load dashboard data');
      }
    } catch (err) {
      setError('Error loading dashboard data');
      console.error('Dashboard error:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDashboardData();
  }, []);

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
      day: 'numeric'
    });
  };

  const StatCard = ({ title, value, icon: Icon, color, change, loading: cardLoading }) => (
    <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6 hover:shadow-md transition-shadow">
      <div className="flex items-center justify-between">
        <div className="flex items-center">
          <div className={`p-3 rounded-xl ${color} shadow-sm`}>
            <Icon className="h-6 w-6 text-white" />
          </div>
          <div className="ml-4">
            <p className="text-sm font-medium text-[#746354]">{title}</p>
            {cardLoading ? (
              <div className="h-8 w-20 bg-gray-200 rounded animate-pulse"></div>
            ) : (
              <p className="text-2xl font-bold text-[#2c2c2c]">
                {title.includes('Sales') ? formatCurrency(value) : value.toLocaleString()}
              </p>
            )}
          </div>
        </div>
        {change !== undefined && !cardLoading && (
          <div className={`text-right ${change >= 0 ? 'text-[#a67c00]' : 'text-red-500'}`}>
            <div className="flex items-center">
              <FiTrendingUp className={`h-4 w-4 ${change < 0 ? 'transform rotate-180' : ''}`} />
              <span className="text-sm font-medium ml-1">
                {change >= 0 ? '+' : ''}{change}%
              </span>
            </div>
            <p className="text-xs text-[#746354]">vs last month</p>
          </div>
        )}
      </div>
    </div>
  );

  const LoadingSpinner = () => (
    <div className="flex items-center justify-center p-8">
      <FiRefreshCw className="h-8 w-8 text-[#e41e5b] animate-spin" />
      <span className="ml-3 text-[#746354]">Loading dashboard data...</span>
    </div>
  );

  const ErrorMessage = ({ message, onRetry }) => (
    <div className="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
      <FiAlertTriangle className="h-12 w-12 text-red-500 mx-auto mb-4" />
      <h3 className="text-lg font-semibold text-red-800 mb-2">Error Loading Dashboard</h3>
      <p className="text-red-600 mb-4">{message}</p>
      <button
        onClick={onRetry}
        className="bg-[#e41e5b] text-white px-6 py-2 rounded-lg hover:bg-[#9a0864] transition-colors"
      >
        Try Again
      </button>
    </div>
  );

  if (loading) {
    return <LoadingSpinner />;
  }

  if (error) {
    return <ErrorMessage message={error} onRetry={fetchDashboardData} />;
  }

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      {/* Header */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-[#2c2c2c]">Dashboard</h1>
            <p className="text-[#746354] mt-1">
              Welcome back, {user?.first_name}! Here's your business overview.
            </p>
          </div>
          <button
            onClick={fetchDashboardData}
            className="flex items-center px-4 py-2 bg-white border border-[#746354]/20 rounded-lg hover:bg-[#e41e5b]/5 hover:border-[#e41e5b]/30 transition-colors"
          >
            <FiRefreshCw className="h-4 w-4 text-[#746354] mr-2" />
            <span className="text-sm font-medium text-[#746354]">Refresh</span>
          </button>
        </div>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <StatCard
          title="Total Sales"
          value={stats.totalSales}
          icon={FiDollarSign}
          color="bg-gradient-to-br from-[#e41e5b] to-[#9a0864]"
          change={12.5}
          loading={loading}
        />
        <StatCard
          title="Total Orders"
          value={stats.totalOrders}
          icon={FiShoppingCart}
          color="bg-gradient-to-br from-[#9a0864] to-[#746354]"
          change={8.2}
          loading={loading}
        />
        <StatCard
          title="Total Customers"
          value={stats.totalCustomers}
          icon={FiUsers}
          color="bg-gradient-to-br from-[#a67c00] to-[#e41e5b]"
          change={15.3}
          loading={loading}
        />
        <StatCard
          title="Total Products"
          value={stats.totalProducts}
          icon={FiPackage}
          color="bg-gradient-to-br from-[#746354] to-[#2c2c2c]"
          change={-2.1}
          loading={loading}
        />
      </div>

      {/* Main Content Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {/* Recent Sales */}
        <div className="lg:col-span-2 bg-white rounded-xl shadow-sm border border-[#746354]/10">
          <div className="px-6 py-4 border-b border-[#746354]/10">
            <h3 className="text-lg font-semibold text-[#2c2c2c]">Recent Sales</h3>
          </div>
          <div className="p-6">
            {stats.recentSales.length > 0 ? (
              <div className="space-y-4">
                {stats.recentSales.map((sale) => (
                  <div key={sale.id} className="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div className="flex items-center">
                      <div className="w-10 h-10 bg-[#e41e5b]/10 rounded-lg flex items-center justify-center">
                        <FiShoppingCart className="h-5 w-5 text-[#e41e5b]" />
                      </div>
                      <div className="ml-4">
                        <p className="text-sm font-semibold text-[#2c2c2c]">
                          {sale.first_name} {sale.last_name}
                        </p>
                        <p className="text-xs text-[#746354]">{formatDate(sale.created_at)}</p>
                      </div>
                    </div>
                    <div className="text-right">
                      <p className="text-sm font-bold text-[#e41e5b]">{formatCurrency(sale.total_amount)}</p>
                      <p className="text-xs text-[#746354]">Order #{sale.id.slice(-8)}</p>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-8">
                <FiShoppingCart className="h-12 w-12 text-[#746354]/40 mx-auto mb-4" />
                <p className="text-[#746354]">No recent sales</p>
              </div>
            )}
          </div>
        </div>

        {/* Low Stock Alert */}
        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10">
          <div className="px-6 py-4 border-b border-[#746354]/10">
            <h3 className="text-lg font-semibold text-[#2c2c2c]">Low Stock Alert</h3>
          </div>
          <div className="p-6">
            {stats.lowStockProducts.length > 0 ? (
              <div className="space-y-3">
                {stats.lowStockProducts.map((product) => (
                  <div key={product.id} className="flex items-center justify-between p-3 bg-red-50 rounded-lg border border-red-200">
                    <div>
                      <p className="text-sm font-medium text-[#2c2c2c]">{product.name}</p>
                      <p className="text-xs text-[#746354]">{formatCurrency(product.price)}</p>
                    </div>
                    <div className="text-right">
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                        product.stock === 0 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'
                      }`}>
                        {product.stock} left
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-8">
                <FiPackage className="h-12 w-12 text-green-400 mx-auto mb-4" />
                <p className="text-[#746354]">All products in stock</p>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Top Products */}
      <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 mb-8">
        <div className="px-6 py-4 border-b border-[#746354]/10">
          <h3 className="text-lg font-semibold text-[#2c2c2c]">Top Performing Products</h3>
        </div>
        <div className="p-6">
          {stats.topProducts.length > 0 ? (
            <div className="space-y-4">
              {stats.topProducts.map((product, index) => (
                <div key={index} className="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                  <div className="flex items-center">
                    <div className={`w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-sm ${
                      index === 0 ? 'bg-[#a67c00]' : 
                      index === 1 ? 'bg-[#746354]' : 
                      index === 2 ? 'bg-[#9a0864]' : 'bg-[#e41e5b]'
                    }`}>
                      {index + 1}
                    </div>
                    <div className="ml-4">
                      <p className="text-sm font-semibold text-[#2c2c2c]">{product.name}</p>
                      <p className="text-xs text-[#746354]">{product.total_sold} units sold</p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className="text-sm font-bold text-[#e41e5b]">{formatCurrency(product.total_revenue)}</p>
                    <p className="text-xs text-[#746354]">Revenue</p>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-8">
              <FiBarChart2 className="h-12 w-12 text-[#746354]/40 mx-auto mb-4" />
              <p className="text-[#746354]">No product data available</p>
            </div>
          )}
        </div>
      </div>

      {/* Quick Actions */}
      <div className="bg-white rounded-xl shadow-sm p-6 border border-[#746354]/10">
        <h3 className="text-lg font-semibold text-[#2c2c2c] mb-6">Quick Actions</h3>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <button className="flex flex-col items-center p-6 border-2 border-[#746354]/20 rounded-xl hover:bg-[#e41e5b]/5 hover:border-[#e41e5b]/40 transition-all duration-200 hover:scale-105">
            <div className="w-12 h-12 bg-[#e41e5b]/10 rounded-xl flex items-center justify-center mb-3">
              <FiShoppingCart className="h-6 w-6 text-[#e41e5b]" />
            </div>
            <span className="text-sm font-semibold text-[#2c2c2c]">New Sale</span>
            <span className="text-xs text-[#746354] mt-1">Create transaction</span>
          </button>
          <button className="flex flex-col items-center p-6 border-2 border-[#746354]/20 rounded-xl hover:bg-[#9a0864]/5 hover:border-[#9a0864]/40 transition-all duration-200 hover:scale-105">
            <div className="w-12 h-12 bg-[#9a0864]/10 rounded-xl flex items-center justify-center mb-3">
              <FiPackage className="h-6 w-6 text-[#9a0864]" />
            </div>
            <span className="text-sm font-semibold text-[#2c2c2c]">Add Product</span>
            <span className="text-xs text-[#746354] mt-1">Manage inventory</span>
          </button>
          <button className="flex flex-col items-center p-6 border-2 border-[#746354]/20 rounded-xl hover:bg-[#a67c00]/5 hover:border-[#a67c00]/40 transition-all duration-200 hover:scale-105">
            <div className="w-12 h-12 bg-[#a67c00]/10 rounded-xl flex items-center justify-center mb-3">
              <FiUsers className="h-6 w-6 text-[#a67c00]" />
            </div>
            <span className="text-sm font-semibold text-[#2c2c2c]">Add Customer</span>
            <span className="text-xs text-[#746354] mt-1">Customer management</span>
          </button>
          <button className="flex flex-col items-center p-6 border-2 border-[#746354]/20 rounded-xl hover:bg-[#746354]/5 hover:border-[#746354]/40 transition-all duration-200 hover:scale-105">
            <div className="w-12 h-12 bg-[#746354]/10 rounded-xl flex items-center justify-center mb-3">
              <FiBarChart2 className="h-6 w-6 text-[#746354]" />
            </div>
            <span className="text-sm font-semibold text-[#2c2c2c]">View Reports</span>
            <span className="text-xs text-[#746354] mt-1">Analytics & insights</span>
          </button>
        </div>
      </div>
    </div>
  );
};

export default DashboardPage;
