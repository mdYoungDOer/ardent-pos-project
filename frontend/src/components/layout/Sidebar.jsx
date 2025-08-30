import { Link, useLocation } from 'react-router-dom'
import { useAuth } from '../../contexts/AuthContext'
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
  FiBell,
  FiTag,
  FiMapPin,
  FiUserCheck,
  FiPercent,
  FiGift,
  FiFolder,
  FiMail,
  FiKey,
  FiDollarSign,
  FiFileText,
  FiHelpCircle,
  FiBookOpen,
  FiMessageSquare
} from 'react-icons/fi'

const Sidebar = () => {
  const location = useLocation()
  const { user, logout } = useAuth()

  // Super Admin Navigation
  const superAdminNavigation = [
    { name: 'Super Admin Dashboard', href: '/super-admin/dashboard', icon: FiShield },
    { name: 'Tenant Management', href: '/super-admin/tenants', icon: FiUsers },
    { name: 'Knowledgebase Management', href: '/super-admin/knowledgebase', icon: FiBookOpen },
    { name: 'Support Tickets', href: '/super-admin/support-tickets', icon: FiMessageSquare },
    { name: 'Contact Submissions', href: '/super-admin/contact-submissions', icon: FiMail },
    { name: 'API Keys Management', href: '/super-admin/api-keys', icon: FiKey },
    { name: 'System Analytics', href: '/super-admin/analytics', icon: FiBarChart2 },
    { name: 'Billing & Payments', href: '/super-admin/billing', icon: FiDollarSign },
    { name: 'Security Management', href: '/super-admin/security', icon: FiShield },
    { name: 'System Health', href: '/super-admin/health', icon: FiActivity },
    { name: 'System Logs', href: '/super-admin/logs', icon: FiFileText },
    { name: 'Database Management', href: '/super-admin/database', icon: FiDatabase },
    { name: 'Global Settings', href: '/super-admin/settings', icon: FiSettings },
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
    { name: 'Support', href: '/app/support', icon: FiHelpCircle },
    { name: 'Notifications', href: '/app/notifications', icon: FiBell },
    { name: 'Settings', href: '/app/settings', icon: FiSettings },
  ]

  // Admin Navigation (includes User Management, Discounts, and Coupons)
  const adminNavigation = [
    ...regularUserNavigation.slice(0, -1), // All regular items except Settings
    { name: 'User Management', href: '/app/user-management', icon: FiUserCheck },
    { name: 'Sub-Categories', href: '/app/sub-categories', icon: FiFolder },
    { name: 'Discounts', href: '/app/discounts', icon: FiPercent },
    { name: 'Coupons', href: '/app/coupons', icon: FiGift },
    { name: 'Settings', href: '/app/settings', icon: FiSettings },
  ]

  // Determine which navigation to show based on user role
  let navigation
  if (user?.role === 'super_admin') {
    navigation = superAdminNavigation
  } else if (user?.role === 'admin') {
    navigation = adminNavigation
  } else {
    navigation = regularUserNavigation
  }

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
          <div className="ml-3 flex-1 min-w-0">
            <p className="text-sm font-semibold text-[#2c2c2c] truncate">
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
      <nav className="mt-6 flex-1 px-3 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100 hover:scrollbar-thumb-gray-400">
        <div className="space-y-1">
          {navigation.map((item) => {
            const isActive = location.pathname === item.href
            return (
              <Link
                key={item.name}
                to={item.href}
                className={`group flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 ${
                  isActive
                    ? 'bg-[#e41e5b] text-white shadow-sm'
                    : 'text-[#746354] hover:bg-[#e41e5b]/5 hover:text-[#e41e5b]'
                }`}
              >
                <item.icon
                  className={`mr-3 h-5 w-5 flex-shrink-0 ${
                    isActive ? 'text-white' : 'text-[#746354] group-hover:text-[#e41e5b]'
                  }`}
                />
                <span className="truncate">{item.name}</span>
              </Link>
            )
          })}
        </div>
      </nav>

      {/* Logout */}
      <div className="px-3 mt-6">
        <button
          onClick={handleLogout}
          className="group flex items-center w-full px-3 py-2.5 text-sm font-medium text-[#746354] rounded-xl hover:bg-red-50 hover:text-red-600 transition-all duration-200"
        >
          <FiLogOut className="mr-3 h-5 w-5 flex-shrink-0 text-[#746354] group-hover:text-red-600" />
          <span>Logout</span>
        </button>
      </div>
    </div>
  )
}

export default Sidebar
