import { useAuth } from '../contexts/AuthContext'
import { Navigate, Outlet } from 'react-router-dom'

const AuthLayout = () => {
  const { isAuthenticated } = useAuth()

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
