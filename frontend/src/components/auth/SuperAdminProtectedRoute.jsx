import useSuperAdminAuthStore from '../../stores/superAdminAuthStore'
import { Navigate } from 'react-router-dom'

const SuperAdminProtectedRoute = ({ children }) => {
  const { isAuthenticated, user } = useSuperAdminAuthStore()

  if (!isAuthenticated) {
    return <Navigate to="/auth/super-admin" replace />
  }

  // Ensure user is actually a super admin
  if (user?.role !== 'super_admin') {
    return <Navigate to="/auth/super-admin" replace />
  }

  return children
}

export default SuperAdminProtectedRoute
