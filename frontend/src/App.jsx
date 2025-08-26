import { useEffect } from 'react'
import { Routes, Route } from 'react-router-dom'
import useAuthStore from './stores/authStore'

// Layouts
import AppLayout from './layouts/AppLayout'
import AuthLayout from './layouts/AuthLayout'
import PublicLayout from './layouts/PublicLayout'
import SuperAdminLayout from './layouts/SuperAdminLayout'

// Auth Pages
import LoginPage from './pages/auth/LoginPage'
import RegisterPage from './pages/auth/RegisterPage'
import SuperAdminLoginPage from './pages/auth/SuperAdminLoginPage'
import ForgotPasswordPage from './pages/auth/ForgotPasswordPage'

// Public Pages
import HomePage from './pages/public/HomePage'
import AboutPage from './pages/public/AboutPage'
import ContactPage from './pages/public/ContactPage'
import FeaturesPage from './pages/public/FeaturesPage'
import PricingPage from './pages/public/PricingPage'
import FAQPage from './pages/public/FAQPage'


// App Pages - Regular Users
import DashboardPage from './pages/app/DashboardPage'
import ProductsPage from './pages/app/ProductsPage'
import InventoryPage from './pages/app/InventoryPage'
import SalesPage from './pages/app/SalesPage'
import CustomersPage from './pages/app/CustomersPage'
import ReportsPage from './pages/app/ReportsPage'
import SettingsPage from './pages/app/SettingsPage'
import NotificationSettingsPage from './pages/app/NotificationSettingsPage';

// Super Admin Pages
import SuperAdminDashboard from './pages/app/SuperAdminDashboard'
import TenantManagementPage from './pages/app/TenantManagementPage'
import SuperAdminAnalyticsPage from './pages/app/SuperAdminAnalyticsPage'
import SuperAdminUserManagementPage from './pages/app/SuperAdminUserManagementPage'
import SuperAdminSettingsPage from './pages/app/SuperAdminSettingsPage'
import POSPage from './pages/app/POSPage'
import CategoriesPage from './pages/app/CategoriesPage'
import LocationsPage from './pages/app/LocationsPage'

// Components
import ProtectedRoute from './components/auth/ProtectedRoute'
import SuperAdminProtectedRoute from './components/auth/SuperAdminProtectedRoute'

function App() {
  const { initialize } = useAuthStore()

  useEffect(() => {
    // Initialize auth state from localStorage
    initialize()
  }, [initialize])

  return (
    <Routes>
      {/* Public Routes */}
      <Route path="/" element={<PublicLayout />}>
        <Route index element={<HomePage />} />
        <Route path="about" element={<AboutPage />} />
        <Route path="contact" element={<ContactPage />} />
        <Route path="features" element={<FeaturesPage />} />
        <Route path="pricing" element={<PricingPage />} />
        <Route path="faq" element={<FAQPage />} />
      </Route>

      {/* Auth Routes */}
      <Route path="/auth" element={<AuthLayout />}>
        <Route path="login" element={<LoginPage />} />
        <Route path="register" element={<RegisterPage />} />
        <Route path="forgot-password" element={<ForgotPasswordPage />} />
        <Route path="super-admin" element={<SuperAdminLoginPage />} />
      </Route>

      {/* Protected App Routes - Regular Users */}
      <Route path="/app" element={<ProtectedRoute><AppLayout /></ProtectedRoute>}>
        <Route index element={<DashboardPage />} />
        <Route path="dashboard" element={<DashboardPage />} />
        <Route path="pos" element={<POSPage />} />
        <Route path="products" element={<ProductsPage />} />
        <Route path="categories" element={<CategoriesPage />} />
        <Route path="locations" element={<LocationsPage />} />
        <Route path="inventory" element={<InventoryPage />} />
        <Route path="sales" element={<SalesPage />} />
        <Route path="customers" element={<CustomersPage />} />
        <Route path="reports" element={<ReportsPage />} />
        <Route path="settings" element={<SettingsPage />} />
        <Route path="notifications" element={<NotificationSettingsPage />} />
      </Route>

      {/* Super Admin Routes - Completely Separate */}
      <Route path="/super-admin" element={<SuperAdminProtectedRoute><SuperAdminLayout /></SuperAdminProtectedRoute>}>
        <Route index element={<SuperAdminDashboard />} />
        <Route path="dashboard" element={<SuperAdminDashboard />} />
        <Route path="tenants" element={<TenantManagementPage />} />
        <Route path="users" element={<SuperAdminUserManagementPage />} />
        <Route path="analytics" element={<SuperAdminAnalyticsPage />} />
        <Route path="settings" element={<SuperAdminSettingsPage />} />
      </Route>

      {/* Catch-all route for 404 */}
      <Route path="*" element={<div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <h1 className="text-4xl font-bold text-gray-900 mb-4">404</h1>
          <p className="text-gray-600 mb-4">Page not found</p>
          <a href="/" className="text-blue-600 hover:text-blue-800">Go back home</a>
        </div>
      </div>} />
    </Routes>
  )
}

export default App
