import { FiShield, FiBell } from 'react-icons/fi'
import useSuperAdminAuthStore from '../../stores/superAdminAuthStore'

const SuperAdminHeader = () => {
  const { user } = useSuperAdminAuthStore()

  return (
    <header className="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-6">
      <div className="flex items-center space-x-4">
        <div className="flex items-center space-x-2">
          <FiShield className="h-6 w-6 text-[#E72F7C]" />
          <h1 className="text-xl font-semibold text-gray-900">Super Admin Portal</h1>
        </div>
      </div>

      <div className="flex items-center space-x-4">
        {/* Notifications */}
        <button className="p-2 text-gray-500 hover:text-gray-700 transition-colors">
          <FiBell className="h-5 w-5" />
        </button>

        {/* User Info */}
        <div className="flex items-center space-x-3">
          <div className="text-right">
            <p className="text-sm font-medium text-gray-900">
              {user?.first_name} {user?.last_name}
            </p>
            <p className="text-xs text-gray-500">Super Administrator</p>
          </div>
          <div className="w-8 h-8 bg-[#E72F7C] rounded-full flex items-center justify-center">
            <span className="text-white text-sm font-semibold">
              {user?.first_name?.[0]}{user?.last_name?.[0]}
            </span>
          </div>
        </div>
      </div>
    </header>
  )
}

export default SuperAdminHeader
