import { Outlet } from 'react-router-dom'
import { useAuthStore } from '../stores/authStore'
import { Navigate } from 'react-router-dom'

const AuthLayout = () => {
  const { isAuthenticated } = useAuthStore()

  // Redirect to app if already authenticated
  if (isAuthenticated) {
    return <Navigate to="/app" replace />
  }

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
      <div className="sm:mx-auto sm:w-full sm:max-w-md">
        <div className="text-center">
          <h1 className="text-3xl font-bold text-primary">Ardent POS</h1>
          <p className="mt-2 text-sm text-gray-600">Smart Point of Sale Solution</p>
        </div>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
          <Outlet />
        </div>
      </div>

      <div className="mt-8 text-center">
        <p className="text-xs text-gray-500">
          © 2024 Ardent POS. All rights reserved.
        </p>
      </div>
    </div>
  )
}

export default AuthLayout
