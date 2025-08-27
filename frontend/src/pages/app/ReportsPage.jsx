import React, { useState, useEffect } from 'react';
import { FiBarChart2, FiTrendingUp, FiTrendingDown, FiDollarSign, FiPackage, FiUsers, FiCalendar, FiDownload } from 'react-icons/fi';
import { dashboardAPI } from '../../services/api';
import { useAuth } from '../../contexts/AuthContext';

const ReportsPage = () => {
  const { user } = useAuth();
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [dateRange, setDateRange] = useState('30'); // days

  const fetchStats = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await dashboardAPI.getStats();
      if (response.data.success) {
        setStats(response.data.data);
      } else {
        setError('Failed to load reports');
      }
    } catch (err) {
      setError('Error loading reports');
      console.error('Reports error:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchStats();
  }, [dateRange]);

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

  const getPercentageChange = (current, previous) => {
    if (previous === 0) return current > 0 ? 100 : 0;
    return ((current - previous) / previous) * 100;
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#e41e5b]"></div>
        <span className="ml-3 text-[#746354]">Loading reports...</span>
      </div>
    );
  }

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      {/* Header */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-[#2c2c2c]">Reports & Analytics</h1>
            <p className="text-[#746354] mt-1">
              Comprehensive insights into your business performance
            </p>
          </div>
          <div className="flex items-center space-x-4">
            <select
              className="px-4 py-2 border border-[#746354]/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#e41e5b] focus:border-[#e41e5b]"
              value={dateRange}
              onChange={(e) => setDateRange(e.target.value)}
            >
              <option value="7">Last 7 days</option>
              <option value="30">Last 30 days</option>
              <option value="90">Last 90 days</option>
              <option value="365">Last year</option>
            </select>
            <button className="flex items-center px-4 py-2 bg-[#e41e5b] text-white rounded-lg hover:bg-[#9a0864] transition-colors">
              <FiDownload className="h-4 w-4 mr-2" />
              Export
            </button>
          </div>
        </div>
      </div>

      {error ? (
        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-8 text-center">
          <div className="text-red-500 text-6xl mb-4">⚠️</div>
          <h3 className="text-lg font-semibold text-red-800 mb-2">Error Loading Reports</h3>
          <p className="text-red-600 mb-4">{error}</p>
          <button
            onClick={fetchStats}
            className="bg-[#e41e5b] text-white px-6 py-2 rounded-lg hover:bg-[#9a0864] transition-colors"
          >
            Try Again
          </button>
        </div>
      ) : stats ? (
        <div className="space-y-6">
          {/* Key Metrics */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-[#746354]">Total Sales</p>
                  <p className="text-2xl font-bold text-[#2c2c2c]">{formatCurrency(stats.totalSales)}</p>
                </div>
                <div className="w-12 h-12 bg-[#e41e5b]/10 rounded-xl flex items-center justify-center">
                  <FiDollarSign className="h-6 w-6 text-[#e41e5b]" />
                </div>
              </div>
              <div className="mt-4 flex items-center">
                <FiTrendingUp className="h-4 w-4 text-green-500 mr-1" />
                <span className="text-sm text-green-600">+12.5% from last month</span>
              </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-[#746354]">Total Orders</p>
                  <p className="text-2xl font-bold text-[#2c2c2c]">{stats.totalOrders}</p>
                </div>
                <div className="w-12 h-12 bg-[#9a0864]/10 rounded-xl flex items-center justify-center">
                  <FiBarChart2 className="h-6 w-6 text-[#9a0864]" />
                </div>
              </div>
              <div className="mt-4 flex items-center">
                <FiTrendingUp className="h-4 w-4 text-green-500 mr-1" />
                <span className="text-sm text-green-600">+8.2% from last month</span>
              </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-[#746354]">Total Customers</p>
                  <p className="text-2xl font-bold text-[#2c2c2c]">{stats.totalCustomers}</p>
                </div>
                <div className="w-12 h-12 bg-[#a67c00]/10 rounded-xl flex items-center justify-center">
                  <FiUsers className="h-6 w-6 text-[#a67c00]" />
                </div>
              </div>
              <div className="mt-4 flex items-center">
                <FiTrendingUp className="h-4 w-4 text-green-500 mr-1" />
                <span className="text-sm text-green-600">+15.3% from last month</span>
              </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-[#746354]">Total Products</p>
                  <p className="text-2xl font-bold text-[#2c2c2c]">{stats.totalProducts}</p>
                </div>
                <div className="w-12 h-12 bg-[#746354]/10 rounded-xl flex items-center justify-center">
                  <FiPackage className="h-6 w-6 text-[#746354]" />
                </div>
              </div>
              <div className="mt-4 flex items-center">
                <FiTrendingUp className="h-4 w-4 text-green-500 mr-1" />
                <span className="text-sm text-green-600">+5.7% from last month</span>
              </div>
            </div>
          </div>

          {/* Charts Section */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Monthly Trend */}
            <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
              <h3 className="text-lg font-semibold text-[#2c2c2c] mb-4">Monthly Sales Trend</h3>
              {stats.monthlyTrend && stats.monthlyTrend.length > 0 ? (
                <div className="space-y-4">
                  {stats.monthlyTrend.slice(0, 6).map((month, index) => (
                    <div key={index} className="flex items-center justify-between">
                      <div className="flex items-center">
                        <FiCalendar className="h-4 w-4 text-[#746354] mr-2" />
                        <span className="text-sm text-[#746354]">
                          {new Date(month.month).toLocaleDateString('en-GH', { month: 'short', year: 'numeric' })}
                        </span>
                      </div>
                      <div className="text-right">
                        <div className="text-sm font-semibold text-[#2c2c2c]">
                          {formatCurrency(month.monthly_sales)}
                        </div>
                        <div className="text-xs text-[#746354]">
                          {month.monthly_orders} orders
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8">
                  <FiBarChart2 className="h-12 w-12 text-[#746354]/40 mx-auto mb-4" />
                  <p className="text-[#746354]">No sales data available</p>
                </div>
              )}
            </div>

            {/* Top Products */}
            <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
              <h3 className="text-lg font-semibold text-[#2c2c2c] mb-4">Top Selling Products</h3>
              {stats.topProducts && stats.topProducts.length > 0 ? (
                <div className="space-y-4">
                  {stats.topProducts.slice(0, 5).map((product, index) => (
                    <div key={index} className="flex items-center justify-between">
                      <div className="flex items-center">
                        <div className="w-8 h-8 bg-[#e41e5b]/10 rounded-lg flex items-center justify-center mr-3">
                          <span className="text-sm font-semibold text-[#e41e5b]">{index + 1}</span>
                        </div>
                        <div>
                          <div className="text-sm font-semibold text-[#2c2c2c]">{product.name}</div>
                          <div className="text-xs text-[#746354]">{product.total_sold} units sold</div>
                        </div>
                      </div>
                      <div className="text-right">
                        <div className="text-sm font-semibold text-[#e41e5b]">
                          {formatCurrency(product.total_revenue)}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8">
                  <FiPackage className="h-12 w-12 text-[#746354]/40 mx-auto mb-4" />
                  <p className="text-[#746354]">No product sales data available</p>
                </div>
              )}
            </div>
          </div>

          {/* Recent Sales & Low Stock */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Recent Sales */}
            <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
              <h3 className="text-lg font-semibold text-[#2c2c2c] mb-4">Recent Sales</h3>
              {stats.recentSales && stats.recentSales.length > 0 ? (
                <div className="space-y-4">
                  {stats.recentSales.slice(0, 5).map((sale, index) => (
                    <div key={index} className="flex items-center justify-between">
                      <div className="flex items-center">
                        <div className="w-8 h-8 bg-[#a67c00]/10 rounded-lg flex items-center justify-center mr-3">
                          <FiUsers className="h-4 w-4 text-[#a67c00]" />
                        </div>
                        <div>
                          <div className="text-sm font-semibold text-[#2c2c2c]">
                            {sale.first_name && sale.last_name 
                              ? `${sale.first_name} ${sale.last_name}`
                              : 'Walk-in Customer'
                            }
                          </div>
                          <div className="text-xs text-[#746354]">
                            {formatDate(sale.created_at)}
                          </div>
                        </div>
                      </div>
                      <div className="text-right">
                        <div className="text-sm font-semibold text-[#e41e5b]">
                          {formatCurrency(sale.total_amount)}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8">
                  <FiDollarSign className="h-12 w-12 text-[#746354]/40 mx-auto mb-4" />
                  <p className="text-[#746354]">No recent sales</p>
                </div>
              )}
            </div>

            {/* Low Stock Alert */}
            <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-6">
              <h3 className="text-lg font-semibold text-[#2c2c2c] mb-4">Low Stock Alert</h3>
              {stats.lowStockProducts && stats.lowStockProducts.length > 0 ? (
                <div className="space-y-4">
                  {stats.lowStockProducts.slice(0, 5).map((product, index) => (
                    <div key={index} className="flex items-center justify-between">
                      <div className="flex items-center">
                        <div className="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                          <FiPackage className="h-4 w-4 text-red-600" />
                        </div>
                        <div>
                          <div className="text-sm font-semibold text-[#2c2c2c]">{product.name}</div>
                          <div className="text-xs text-[#746354]">
                            {formatCurrency(product.price)} each
                          </div>
                        </div>
                      </div>
                      <div className="text-right">
                        <div className={`text-sm font-semibold ${
                          product.stock === 0 ? 'text-red-600' : 'text-yellow-600'
                        }`}>
                          {product.stock} units
                        </div>
                        <div className="text-xs text-[#746354]">
                          {product.stock === 0 ? 'Out of stock' : 'Low stock'}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8">
                  <FiPackage className="h-12 w-12 text-green-500/40 mx-auto mb-4" />
                  <p className="text-green-600">All products well stocked!</p>
                </div>
              )}
            </div>
          </div>
        </div>
      ) : (
        <div className="bg-white rounded-xl shadow-sm border border-[#746354]/10 p-8 text-center">
          <FiBarChart2 className="h-16 w-16 text-[#746354]/40 mx-auto mb-4" />
          <h3 className="text-lg font-semibold text-[#2c2c2c] mb-2">No Data Available</h3>
          <p className="text-[#746354] mb-6">
            Start making sales to see your reports and analytics
          </p>
        </div>
      )}
    </div>
  );
};

export default ReportsPage;
