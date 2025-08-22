import { useState } from 'react'
import useAuthStore from '../../stores/authStore'

const SettingsPage = () => {
  const { user, tenant } = useAuthStore()
  const [isLoading, setIsLoading] = useState(false)

  const handleSaveSettings = async () => {
    setIsLoading(true)
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    setIsLoading(false)
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Settings</h1>
        <p className="text-gray-600">Manage your account and business settings.</p>
      </div>

      {/* Settings Sections */}
      <div className="space-y-6">
        {/* Account Information */}
        <div className="bg-white shadow rounded-lg p-6">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Account Information</h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700">First Name</label>
              <input
                type="text"
                defaultValue={user?.first_name}
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700">Last Name</label>
              <input
                type="text"
                defaultValue={user?.last_name}
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700">Email</label>
              <input
                type="email"
                defaultValue={user?.email}
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700">Role</label>
              <input
                type="text"
                defaultValue={user?.role}
                disabled
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50"
              />
            </div>
          </div>
        </div>

        {/* Business Information */}
        <div className="bg-white shadow rounded-lg p-6">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Business Information</h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700">Business Name</label>
              <input
                type="text"
                defaultValue={tenant?.name}
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700">Business ID</label>
              <input
                type="text"
                defaultValue={tenant?.id}
                disabled
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50"
              />
            </div>
          </div>
        </div>

        {/* Security Settings */}
        <div className="bg-white shadow rounded-lg p-6">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Security</h3>
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700">Current Password</label>
              <input
                type="password"
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Enter current password"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700">New Password</label>
              <input
                type="password"
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Enter new password"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700">Confirm New Password</label>
              <input
                type="password"
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Confirm new password"
              />
            </div>
          </div>
        </div>

        {/* Save Button */}
        <div className="flex justify-end">
          <button
            onClick={handleSaveSettings}
            disabled={isLoading}
            className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            {isLoading ? 'Saving...' : 'Save Changes'}
          </button>
        </div>
      </div>
    </div>
  )
}

export default SettingsPage
