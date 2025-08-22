import { Link, useLocation } from 'react-router-dom'
import useAuthStore from '../../stores/authStore'
import {
  HomeIcon,
  CubeIcon,
  ShoppingCartIcon,
  UsersIcon,
  ChartBarIcon,
  Cog6ToothIcon,
  ClipboardDocumentListIcon
} from '@heroicons/react/24/outline'

const Sidebar = () => {
  const location = useLocation()
  const { user } = useAuthStore()

  const navigation = [
    { name: 'Dashboard', href: '/app/dashboard', icon: HomeIcon },
    { name: 'Products', href: '/app/products', icon: CubeIcon },
    { name: 'Sales', href: '/app/sales', icon: ShoppingCartIcon },
    { name: 'Inventory', href: '/app/inventory', icon: ClipboardDocumentListIcon },
    { name: 'Customers', href: '/app/customers', icon: UsersIcon },
    { name: 'Reports', href: '/app/reports', icon: ChartBarIcon },
    { name: 'Settings', href: '/app/settings', icon: Cog6ToothIcon },
  ]

  return (
    <div className="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 lg:border-r lg:border-gray-200 lg:bg-gray-50 lg:pt-5 lg:pb-4">
      <div className="flex items-center flex-shrink-0 px-6">
        <h1 className="text-xl font-semibold text-gray-900">Ardent POS</h1>
      </div>
      
      {/* User info */}
      <div className="mt-6 px-6">
        <div className="flex items-center">
          <div className="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center">
            <span className="text-sm font-medium text-white">
              {user?.first_name?.[0]}{user?.last_name?.[0]}
            </span>
          </div>
          <div className="ml-3">
            <p className="text-sm font-medium text-gray-700">
              {user?.first_name} {user?.last_name}
            </p>
            <p className="text-xs text-gray-500">{user?.role}</p>
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
              className={`group flex items-center px-2 py-2 text-sm font-medium rounded-md ${
                isActive
                  ? 'bg-indigo-100 text-indigo-700'
                  : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
              }`}
            >
              <item.icon
                className={`mr-3 h-5 w-5 ${
                  isActive ? 'text-indigo-500' : 'text-gray-400 group-hover:text-gray-500'
                }`}
              />
              {item.name}
            </Link>
          )
        })}
      </nav>
    </div>
  )
}

export default Sidebar
