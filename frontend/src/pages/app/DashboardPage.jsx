import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { 
  FiBarChart3, 
  FiShoppingCart, 
  FiUsers, 
  FiDollarSign,
  FiTrendingUp,
  FiPackage
} from 'react-icons/fi'
import useAuthStore from '../../stores/authStore'

const DashboardPage = () => {
  const { user, tenant } = useAuthStore()
  const [stats, setStats] = useState({
    totalSales: 0,
    totalOrders: 0,
    totalCustomers: 0,
    totalProducts: 0,
    todaySales: 0,
    monthlyGrowth: 0
  })
  const [recentSales, setRecentSales] = useState([])
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    // Simulate loading dashboard data
    const loadDashboardData = async () => {
      try {
        // In a real app, you would fetch this data from your API
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        setStats({
          totalSales: 125000,
          totalOrders: 1247,
          totalCustomers: 89,
          totalProducts: 156,
          todaySales: 3200,
          monthlyGrowth: 12.5
        })
        
        setRecentSales([
          { id: 1, customer: 'John Doe', amount: 150.00, status: 'completed', date: '2024-01-15' },
          { id: 2, customer: 'Jane Smith', amount: 89.99, status: 'pending', date: '2024-01-15' },
          { id: 3, customer: 'Bob Johnson', amount: 245.50, status: 'completed', date: '2024-01-14' },
          { id: 4, customer: 'Alice Brown', amount: 67.25, status: 'completed', date: '2024-01-14' },
          { id: 5, customer: 'Charlie Wilson', amount: 189.99, status: 'pending', date: '2024-01-13' }
        ])
      } catch (error) {
        console.error('Error loading dashboard data:', error)
      } finally {
        setIsLoading(false)
      }
    }

    loadDashboardData()
  }, [])

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p className="text-gray-600">
          Welcome back, {user?.first_name}! Here's what's happening with {tenant?.name}.
        </p>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center">
            <div className="p-2 bg-green-100 rounded-lg">
              <FiDollarSign className="h-6 w-6 text-green-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Total Sales</p>
              <p className="text-2xl font-bold text-gray-900">${stats.totalSales.toLocaleString()}</p>
            </div>
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center">
            <div className="p-2 bg-blue-100 rounded-lg">
              <FiShoppingCart className="h-6 w-6 text-blue-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Total Orders</p>
              <p className="text-2xl font-bold text-gray-900">{stats.totalOrders}</p>
            </div>
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center">
            <div className="p-2 bg-purple-100 rounded-lg">
              <FiUsers className="h-6 w-6 text-purple-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Customers</p>
              <p className="text-2xl font-bold text-gray-900">{stats.totalCustomers}</p>
            </div>
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center">
            <div className="p-2 bg-orange-100 rounded-lg">
              <FiPackage className="h-6 w-6 text-orange-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Products</p>
              <p className="text-2xl font-bold text-gray-900">{stats.totalProducts}</p>
            </div>
          </div>
        </div>
      </div>

      {/* Recent Activity */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Recent Sales */}
        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold text-gray-900">Recent Sales</h3>
            <Link to="/app/sales" className="text-sm text-indigo-600 hover:text-indigo-500">
              View all
            </Link>
          </div>
          <div className="space-y-4">
            {recentSales.map((sale) => (
              <div key={sale.id} className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-900">{sale.customer}</p>
                  <p className="text-sm text-gray-500">{sale.date}</p>
                </div>
                <div className="text-right">
                  <p className="text-sm font-medium text-gray-900">${sale.amount}</p>
                  <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                    sale.status === 'completed' 
                      ? 'bg-green-100 text-green-800' 
                      : 'bg-yellow-100 text-yellow-800'
                  }`}>
                    {sale.status}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Quick Actions */}
        <div className="bg-white p-6 rounded-lg shadow">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
          <div className="space-y-3">
            <Link
              to="/app/sales"
              className="flex items-center p-3 text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
            >
              <FiShoppingCart className="h-5 w-5 mr-3 text-indigo-600" />
              Create New Sale
            </Link>
            <Link
              to="/app/products"
              className="flex items-center p-3 text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
            >
              <FiPackage className="h-5 w-5 mr-3 text-indigo-600" />
              Add New Product
            </Link>
            <Link
              to="/app/customers"
              className="flex items-center p-3 text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
            >
              <FiUsers className="h-5 w-5 mr-3 text-indigo-600" />
              Add New Customer
            </Link>
            <Link
              to="/app/reports"
              className="flex items-center p-3 text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
            >
              <FiBarChart3 className="h-5 w-5 mr-3 text-indigo-600" />
              View Reports
            </Link>
          </div>
        </div>
      </div>
    </div>
  )
}

export default DashboardPage
