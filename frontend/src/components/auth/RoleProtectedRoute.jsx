import useAuthStore from '../../stores/authStore'
import { Navigate } from 'react-router-dom'

const RoleProtectedRoute = ({ children, requiredRole = null }) => {
  const { isAuthenticated, user } = useAuthStore()

  if (!isAuthenticated) {
    return <Navigate to="/auth/login" replace />
  }

  // If a specific role is required, check if user has that role
  if (requiredRole && user?.role !== requiredRole) {
    // Redirect Super Admin to their dashboard
    if (user?.role === 'super_admin') {
      return <Navigate to="/app/super-admin" replace />
    }
    // Redirect regular users to their dashboard
    return <Navigate to="/app/dashboard" replace />
  }

  return children
}

export default RoleProtectedRoute
