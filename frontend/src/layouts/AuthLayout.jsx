import useAuthStore from '../stores/authStore'
import { Navigate, Outlet } from 'react-router-dom'

const AuthLayout = () => {
  const { isAuthenticated } = useAuthStore()

  // Redirect to dashboard if already authenticated
  if (isAuthenticated) {
    return <Navigate to="/app/dashboard" replace />
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <Outlet />
    </div>
  )
}

export default AuthLayout
