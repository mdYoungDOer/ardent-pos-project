import { Navigate, useLocation } from 'react-router-dom'
import { useAuthStore } from '../../stores/authStore'
import LoadingSpinner from '../ui/LoadingSpinner'

const ProtectedRoute = ({ children }) => {
  const { isAuthenticated, isLoading, user, token } = useAuthStore()
  const location = useLocation()

  console.log('ProtectedRoute Debug:', {
    isAuthenticated,
    isLoading,
    hasUser: !!user,
    hasToken: !!token,
    pathname: location.pathname
  })

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <LoadingSpinner size="lg" />
      </div>
    )
  }

  if (!isAuthenticated || !user || !token) {
    console.log('Redirecting to login - not authenticated')
    // Redirect to login page with return url
    return <Navigate to="/auth/login" state={{ from: location }} replace />
  }

  console.log('Rendering protected content for user:', user.email)
  return children
}

export default ProtectedRoute
