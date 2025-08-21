import { Link, useLocation } from 'react-router-dom'
import { useAuthStore } from '../../stores/authStore'
import { clsx } from 'clsx'
import {
  HiHome,
  HiCube,
  HiClipboardList,
  HiCreditCard,
  HiUsers,
  HiChartBar,
  HiCog,
  HiLogout,
  HiX
} from 'react-icons/hi'

const Sidebar = ({ onClose }) => {
  const location = useLocation()
  const { user, tenant, logout, canAccess } = useAuthStore()

  const navigation = [
    { name: 'Dashboard', href: '/app/dashboard', icon: HiHome, access: 'dashboard' },
    { name: 'Products', href: '/app/products', icon: HiCube, access: 'products' },
    { name: 'Inventory', href: '/app/inventory', icon: HiClipboardList, access: 'inventory' },
    { name: 'Sales', href: '/app/sales', icon: HiCreditCard, access: 'sales' },
    { name: 'Customers', href: '/app/customers', icon: HiUsers, access: 'customers' },
    { name: 'Reports', href: '/app/reports', icon: HiChartBar, access: 'reports' },
    { name: 'Settings', href: '/app/settings', icon: HiCog, access: 'settings' },
  ]

  const handleLogout = async () => {
    await logout()
    if (onClose) onClose()
  }

  return (
    <div className="flex flex-col h-full bg-white border-r border-gray-200">
      {/* Header */}
      <div className="flex items-center justify-between h-16 px-4 border-b border-gray-200">
        <div className="flex items-center">
          <span className="text-xl font-bold text-primary">Ardent POS</span>
        </div>
        {onClose && (
          <button
            onClick={onClose}
            className="md:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
          >
            <HiX className="h-6 w-6" />
          </button>
        )}
      </div>

      {/* Tenant Info */}
      <div className="px-4 py-3 border-b border-gray-200">
        <div className="text-sm font-medium text-gray-900 truncate">
          {tenant?.name}
        </div>
        <div className="text-xs text-gray-500 truncate">
          {user?.first_name} {user?.last_name}
        </div>
        <div className="text-xs text-gray-400 capitalize">
          {user?.role?.replace('_', ' ')}
        </div>
      </div>

      {/* Navigation */}
      <nav className="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
        {navigation.map((item) => {
          if (!canAccess(item.access)) return null
          
          const isActive = location.pathname === item.href
          const Icon = item.icon

          return (
            <Link
              key={item.name}
              to={item.href}
              onClick={onClose}
              className={clsx(
                'group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors',
                isActive
                  ? 'bg-primary-50 text-primary border-r-2 border-primary'
                  : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
              )}
            >
              <Icon
                className={clsx(
                  'mr-3 flex-shrink-0 h-5 w-5',
                  isActive ? 'text-primary' : 'text-gray-400 group-hover:text-gray-500'
                )}
              />
              {item.name}
            </Link>
          )
        })}
      </nav>

      {/* Logout */}
      <div className="px-2 py-4 border-t border-gray-200">
        <button
          onClick={handleLogout}
          className="group flex items-center w-full px-2 py-2 text-sm font-medium text-gray-600 rounded-md hover:bg-gray-50 hover:text-gray-900 transition-colors"
        >
          <HiLogout className="mr-3 flex-shrink-0 h-5 w-5 text-gray-400 group-hover:text-gray-500" />
          Sign out
        </button>
      </div>
    </div>
  )
}

export default Sidebar
