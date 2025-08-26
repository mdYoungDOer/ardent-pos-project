import { Link, useLocation } from 'react-router-dom'
import { FiShield, FiUsers, FiSettings, FiBarChart3, FiLogOut } from 'react-icons/fi'
import useSuperAdminAuthStore from '../../stores/superAdminAuthStore'
import Logo from '../ui/Logo'

const SuperAdminSidebar = () => {
  const location = useLocation()
  const { logout, user } = useSuperAdminAuthStore()

  const navigation = [
    { name: 'Dashboard', href: '/super-admin/dashboard', icon: FiShield },
    { name: 'Tenant Management', href: '/super-admin/tenants', icon: FiUsers },
    { name: 'System Settings', href: '/super-admin/settings', icon: FiSettings },
    { name: 'Analytics', href: '/super-admin/analytics', icon: FiBarChart3 },
  ]

  const isActive = (href) => location.pathname === href

  return (
    <div className="w-64 bg-white shadow-lg border-r border-gray-200">
      <div className="flex flex-col h-full">
        {/* Logo */}
        <div className="flex items-center justify-center h-16 px-4 border-b border-gray-200">
          <div className="flex items-center space-x-2">
            <Logo size="medium" />
            <div className="bg-[#E72F7C] rounded-full p-1">
              <FiShield className="h-4 w-4 text-white" />
            </div>
          </div>
        </div>

        {/* Navigation */}
        <nav className="flex-1 px-4 py-6 space-y-2">
          {navigation.map((item) => (
            <Link
              key={item.name}
              to={item.href}
              className={`flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors ${
                isActive(item.href)
                  ? 'bg-[#E72F7C] text-white'
                  : 'text-gray-700 hover:bg-gray-100'
              }`}
            >
              <item.icon className="h-5 w-5 mr-3" />
              {item.name}
            </Link>
          ))}
        </nav>

        {/* User Info & Logout */}
        <div className="p-4 border-t border-gray-200">
          <div className="flex items-center mb-3">
            <div className="w-8 h-8 bg-[#E72F7C] rounded-full flex items-center justify-center">
              <span className="text-white text-sm font-semibold">
                {user?.first_name?.[0]}{user?.last_name?.[0]}
              </span>
            </div>
            <div className="ml-3">
              <p className="text-sm font-medium text-gray-900">
                {user?.first_name} {user?.last_name}
              </p>
              <p className="text-xs text-gray-500">Super Admin</p>
            </div>
          </div>
          <button
            onClick={logout}
            className="w-full flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <FiLogOut className="h-5 w-5 mr-3" />
            Logout
          </button>
        </div>
      </div>
    </div>
  )
}

export default SuperAdminSidebar
