import { Link, useLocation } from 'react-router-dom'
import useAuthStore from '../../stores/authStore'
import {
  FiHome,
  FiPackage,
  FiShoppingCart,
  FiUsers,
  FiBarChart2,
  FiSettings,
  FiClipboard,
  FiShield,
  FiDatabase,
  FiActivity,
  FiTrendingUp,
  FiGlobe,
  FiAward,
  FiTarget,
  FiLogOut,
  FiBell
} from 'react-icons/fi'

const Sidebar = () => {
  const location = useLocation()
  const { user, logout } = useAuthStore()

  // Super Admin Navigation
  const superAdminNavigation = [
    { name: 'Super Admin Dashboard', href: '/app/super-admin', icon: FiShield },
    { name: 'Tenant Management', href: '/app/tenants', icon: FiUsers },
    { name: 'System Analytics', href: '/app/analytics', icon: FiBarChart2 },
    { name: 'System Health', href: '/app/health', icon: FiActivity },
    { name: 'Revenue Overview', href: '/app/revenue', icon: FiTrendingUp },
    { name: 'Database Management', href: '/app/database', icon: FiDatabase },
    { name: 'Global Settings', href: '/app/global-settings', icon: FiSettings },
  ]

  // Regular User Navigation
  const regularUserNavigation = [
    { name: 'Dashboard', href: '/app/dashboard', icon: FiHome },
    { name: 'POS Terminal', href: '/app/pos', icon: FiShoppingCart },
    { name: 'Products', href: '/app/products', icon: FiPackage },
    { name: 'Categories', href: '/app/categories', icon: FiTag },
    { name: 'Locations', href: '/app/locations', icon: FiMapPin },
    { name: 'Sales', href: '/app/sales', icon: FiShoppingCart },
    { name: 'Inventory', href: '/app/inventory', icon: FiClipboard },
    { name: 'Customers', href: '/app/customers', icon: FiUsers },
    { name: 'Reports', href: '/app/reports', icon: FiBarChart2 },
    { name: 'Notifications', href: '/app/notifications', icon: FiBell },
    { name: 'Settings', href: '/app/settings', icon: FiSettings },
  ]

  // Determine which navigation to show based on user role
  const navigation = user?.role === 'super_admin' ? superAdminNavigation : regularUserNavigation

  const handleLogout = () => {
    logout()
  }

  return (
    <div className="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 lg:border-r lg:border-[#746354]/20 lg:bg-white lg:pt-5 lg:pb-4">
      {/* Logo */}
      <div className="flex items-center flex-shrink-0 px-6">
        <div className="flex items-center">
          <div className="w-8 h-8 bg-[#e41e5b] rounded-lg flex items-center justify-center mr-3">
            <FiTarget className="h-5 w-5 text-white" />
          </div>
          <h1 className="text-xl font-bold text-[#2c2c2c]">Ardent POS</h1>
        </div>
      </div>
      
      {/* User info */}
      <div className="mt-6 px-6">
        <div className="flex items-center p-3 bg-[#e41e5b]/5 rounded-lg border border-[#e41e5b]/10">
          <div className="h-10 w-10 rounded-full bg-[#e41e5b] flex items-center justify-center">
            <span className="text-sm font-medium text-white">
              {user?.first_name?.[0]}{user?.last_name?.[0]}
            </span>
          </div>
          <div className="ml-3 flex-1">
            <p className="text-sm font-semibold text-[#2c2c2c]">
              {user?.first_name} {user?.last_name}
            </p>
            <div className="flex items-center">
              <span className={`text-xs px-2 py-1 rounded-full ${
                user?.role === 'super_admin' 
                  ? 'bg-purple-100 text-purple-700' 
                  : 'bg-[#a67c00]/10 text-[#a67c00]'
              }`}>
                {user?.role === 'super_admin' ? 'Super Admin' : user?.role}
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Navigation */}
      <nav className="mt-8 flex-1 px-3 space-y-1">
        {navigation.map((item) => {
          const isActive = location.pathname === item.href
          return (
            <Link
              key={item.name}
              to={item.href}
              className={`group flex items-center px-3 py-3 text-sm font-medium rounded-xl transition-colors ${
                isActive
                  ? 'bg-[#e41e5b] text-white shadow-sm'
                  : 'text-[#746354] hover:bg-[#e41e5b]/5 hover:text-[#e41e5b]'
              }`}
            >
              <item.icon
                className={`mr-3 h-5 w-5 ${
                  isActive ? 'text-white' : 'text-[#746354] group-hover:text-[#e41e5b]'
                }`}
              />
              {item.name}
            </Link>
          )
        })}
      </nav>

      {/* Logout */}
      <div className="px-3 pb-4">
        <button
          onClick={handleLogout}
          className="group flex items-center w-full px-3 py-3 text-sm font-medium text-[#746354] rounded-xl hover:bg-red-50 hover:text-red-600 transition-colors"
        >
          <FiLogOut className="mr-3 h-5 w-5 text-[#746354] group-hover:text-red-600" />
          Logout
        </button>
      </div>
    </div>
  )
}

export default Sidebar
