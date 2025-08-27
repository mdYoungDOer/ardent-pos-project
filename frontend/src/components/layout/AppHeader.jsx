import { useAuth } from '../../contexts/AuthContext'
import { FiMenu, FiUser } from 'react-icons/fi'
import NotificationBell from '../ui/NotificationBell'

const AppHeader = ({ onMenuClick }) => {
  const { user, logout } = useAuth()

  const handleLogout = () => {
    logout()
  }

  return (
    <header className="bg-white shadow-sm border-b border-gray-200">
      <div className="flex items-center justify-between px-4 py-3">
        {/* Left side - Menu button and title */}
        <div className="flex items-center">
          <button
            onClick={onMenuClick}
            className="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
          >
            <FiMenu className="h-6 w-6" />
          </button>
          <h1 className="ml-3 text-lg font-semibold text-gray-900">Ardent POS</h1>
        </div>

        {/* Right side - Notifications and user menu */}
        <div className="flex items-center space-x-4">
          {/* Notifications */}
          <NotificationBell />

          {/* User menu */}
          <div className="relative">
            <button className="flex items-center space-x-2 p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
              <FiUser className="h-6 w-6" />
              <span className="text-sm font-medium text-gray-700">
                {user?.first_name} {user?.last_name}
              </span>
            </button>
          </div>

          {/* Logout button */}
          <button
            onClick={handleLogout}
            className="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
          >
            Logout
          </button>
        </div>
      </div>
    </header>
  )
}

export default AppHeader
