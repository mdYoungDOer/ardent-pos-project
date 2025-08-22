import React, { useState, useEffect } from 'react';
import { FiBarChart2, FiShoppingCart, FiUsers, FiPackage, FiTrendingUp, FiDollarSign } from 'react-icons/fi';
import useAuthStore from '../../stores/authStore';

const DashboardPage = () => {
  const { user, tenant } = useAuthStore();
  const [stats, setStats] = useState({
    totalSales: 0,
    totalOrders: 0,
    totalCustomers: 0,
    totalProducts: 0,
    recentSales: [],
    topProducts: []
  });

  useEffect(() => {
    // Mock data for now - replace with actual API calls
    setStats({
      totalSales: 125000,
      totalOrders: 342,
      totalCustomers: 156,
      totalProducts: 89,
      recentSales: [
        { id: 1, customer: 'John Doe', amount: 2500, date: '2024-01-15' },
        { id: 2, customer: 'Jane Smith', amount: 1800, date: '2024-01-14' },
        { id: 3, customer: 'Bob Johnson', amount: 3200, date: '2024-01-13' },
      ],
      topProducts: [
        { id: 1, name: 'Product A', sales: 45, revenue: 22500 },
        { id: 2, name: 'Product B', sales: 38, revenue: 19000 },
        { id: 3, name: 'Product C', sales: 32, revenue: 16000 },
      ]
    });
  }, []);

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-GH', {
      style: 'currency',
      currency: 'GHS'
    }).format(amount);
  };

  const StatCard = ({ title, value, icon: Icon, color, change }) => (
    <div className="bg-white rounded-lg shadow p-6 border border-[#746354]/20">
      <div className="flex items-center">
        <div className={`p-3 rounded-lg ${color}`}>
          <Icon className="h-6 w-6 text-white" />
        </div>
        <div className="ml-4">
          <p className="text-sm font-medium text-[#746354]">{title}</p>
          <p className="text-2xl font-semibold text-[#2c2c2c]">
            {title.includes('Sales') ? formatCurrency(value) : value.toLocaleString()}
          </p>
          {change && (
            <p className={`text-sm ${change > 0 ? 'text-[#a67c00]' : 'text-red-600'}`}>
              {change > 0 ? '+' : ''}{change}% from last month
            </p>
          )}
        </div>
      </div>
    </div>
  );

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-[#2c2c2c]">Dashboard</h1>
        <p className="text-[#746354]">
          Welcome back, {user?.first_name}! Here's what's happening with your business.
        </p>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <StatCard
          title="Total Sales"
          value={stats.totalSales}
          icon={FiDollarSign}
          color="bg-[#e41e5b]"
          change={12.5}
        />
        <StatCard
          title="Total Orders"
          value={stats.totalOrders}
          icon={FiShoppingCart}
          color="bg-[#9a0864]"
          change={8.2}
        />
        <StatCard
          title="Total Customers"
          value={stats.totalCustomers}
          icon={FiUsers}
          color="bg-[#a67c00]"
          change={15.3}
        />
        <StatCard
          title="Total Products"
          value={stats.totalProducts}
          icon={FiPackage}
          color="bg-[#746354]"
          change={-2.1}
        />
      </div>

      {/* Charts and Tables */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Recent Sales */}
        <div className="bg-white rounded-lg shadow border border-[#746354]/20">
          <div className="px-6 py-4 border-b border-[#746354]/20">
            <h3 className="text-lg font-medium text-[#2c2c2c]">Recent Sales</h3>
          </div>
          <div className="p-6">
            <div className="space-y-4">
              {stats.recentSales.map((sale) => (
                <div key={sale.id} className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium text-[#2c2c2c]">{sale.customer}</p>
                    <p className="text-sm text-[#746354]">{sale.date}</p>
                  </div>
                  <div className="text-right">
                    <p className="text-sm font-medium text-[#e41e5b]">{formatCurrency(sale.amount)}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Top Products */}
        <div className="bg-white rounded-lg shadow border border-[#746354]/20">
          <div className="px-6 py-4 border-b border-[#746354]/20">
            <h3 className="text-lg font-medium text-[#2c2c2c]">Top Products</h3>
          </div>
          <div className="p-6">
            <div className="space-y-4">
              {stats.topProducts.map((product) => (
                <div key={product.id} className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium text-[#2c2c2c]">{product.name}</p>
                    <p className="text-sm text-[#746354]">{product.sales} units sold</p>
                  </div>
                  <div className="text-right">
                    <p className="text-sm font-medium text-[#e41e5b]">{formatCurrency(product.revenue)}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="mt-8 bg-white rounded-lg shadow p-6 border border-[#746354]/20">
        <h3 className="text-lg font-medium text-[#2c2c2c] mb-4">Quick Actions</h3>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <button className="flex flex-col items-center p-4 border border-[#746354]/30 rounded-lg hover:bg-[#e41e5b]/5 hover:border-[#e41e5b]/30 transition-colors">
            <FiShoppingCart className="h-8 w-8 text-[#e41e5b] mb-2" />
            <span className="text-sm font-medium text-[#2c2c2c]">New Sale</span>
          </button>
          <button className="flex flex-col items-center p-4 border border-[#746354]/30 rounded-lg hover:bg-[#9a0864]/5 hover:border-[#9a0864]/30 transition-colors">
            <FiPackage className="h-8 w-8 text-[#9a0864] mb-2" />
            <span className="text-sm font-medium text-[#2c2c2c]">Add Product</span>
          </button>
          <button className="flex flex-col items-center p-4 border border-[#746354]/30 rounded-lg hover:bg-[#a67c00]/5 hover:border-[#a67c00]/30 transition-colors">
            <FiUsers className="h-8 w-8 text-[#a67c00] mb-2" />
            <span className="text-sm font-medium text-[#2c2c2c]">Add Customer</span>
          </button>
          <button className="flex flex-col items-center p-4 border border-[#746354]/30 rounded-lg hover:bg-[#746354]/5 hover:border-[#746354]/30 transition-colors">
            <FiBarChart2 className="h-8 w-8 text-[#746354] mb-2" />
            <span className="text-sm font-medium text-[#2c2c2c]">View Reports</span>
          </button>
        </div>
      </div>
    </div>
  );
};

export default DashboardPage;
