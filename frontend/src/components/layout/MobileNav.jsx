import useAuthStore from '../../stores/authStore'
import { FiX } from 'react-icons/fi'

const MobileNav = ({ isOpen, onClose, navigation }) => {
  const { user } = useAuthStore()

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 z-50 lg:hidden">
      {/* Backdrop */}
      <div className="fixed inset-0 bg-gray-600 bg-opacity-75" onClick={onClose} />

      {/* Sidebar */}
      <div className="fixed inset-y-0 left-0 flex w-64 flex-col bg-white">
        <div className="flex h-16 items-center justify-between px-4 border-b border-gray-200">
          <h2 className="text-lg font-semibold text-gray-900">Ardent POS</h2>
          <button
            onClick={onClose}
            className="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
          >
            <FiX className="h-6 w-6" />
          </button>
        </div>

        {/* User info */}
        <div className="px-4 py-3 border-b border-gray-200">
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
        <nav className="flex-1 px-2 py-4 space-y-1">
          {navigation.map((item) => (
            <a
              key={item.name}
              href={item.href}
              className={`group flex items-center px-2 py-2 text-sm font-medium rounded-md ${
                item.current
                  ? 'bg-indigo-100 text-indigo-700'
                  : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
              }`}
            >
              <item.icon
                className={`mr-3 h-5 w-5 ${
                  item.current ? 'text-indigo-500' : 'text-gray-400 group-hover:text-gray-500'
                }`}
              />
              {item.name}
            </a>
          ))}
        </nav>
      </div>
    </div>
  )
}

export default MobileNav
