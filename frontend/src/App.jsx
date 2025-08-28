import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import CookieConsent from './components/ui/CookieConsent';
import LandingPage from './pages/public/LandingPage';
import HomePage from './pages/public/HomePage';
import FeaturesPage from './pages/public/FeaturesPage';
import PricingPage from './pages/public/PricingPage';
import AboutPage from './pages/public/AboutPage';
import ContactPage from './pages/public/ContactPage';
import SupportPortalPage from './pages/public/SupportPortalPage';
import FAQPage from './pages/public/FAQPage';
import PrivacyPolicyPage from './pages/public/PrivacyPolicyPage';
import TermsOfUsePage from './pages/public/TermsOfUsePage';
import CookiePolicyPage from './pages/public/CookiePolicyPage';
import LoginPage from './pages/auth/LoginPage';
import RegisterPage from './pages/auth/RegisterPage';
import SuperAdminLoginPage from './pages/auth/SuperAdminLoginPage';
import AppLayout from './layouts/AppLayout';
import PublicLayout from './layouts/PublicLayout';
import SuperAdminLayout from './layouts/SuperAdminLayout';
import ProtectedRoute from './components/auth/ProtectedRoute';
import SuperAdminProtectedRoute from './components/auth/SuperAdminProtectedRoute';

// Import all app pages
import DashboardPage from './pages/app/DashboardPage';
import ProductsPage from './pages/app/ProductsPage';
import InventoryPage from './pages/app/InventoryPage';
import SalesPage from './pages/app/SalesPage';
import CustomersPage from './pages/app/CustomersPage';
import ReportsPage from './pages/app/ReportsPage';
import SettingsPage from './pages/app/SettingsPage';
import NotificationSettingsPage from './pages/app/NotificationSettingsPage';
import UserManagementPage from './pages/app/UserManagementPage';
import SubCategoriesPage from './pages/app/SubCategoriesPage';
import DiscountsPage from './pages/app/DiscountsPage';
import CouponsPage from './pages/app/CouponsPage';
import POSPage from './pages/app/POSPage';
import CategoriesPage from './pages/app/CategoriesPage';
import LocationsPage from './pages/app/LocationsPage';
import SupportPage from './pages/app/SupportPage';

// Import all super admin pages
import SuperAdminDashboard from './pages/app/SuperAdminDashboard';
import SuperAdminAnalyticsPage from './pages/app/SuperAdminAnalyticsPage';
import SuperAdminTenants from './pages/app/TenantManagementPage';
import SuperAdminUsers from './pages/app/SuperAdminUserManagementPage';
import SuperAdminSettings from './pages/app/SuperAdminSettingsPage';
import SuperAdminSubscriptionPlans from './pages/app/SuperAdminSubscriptionPlansPage';
import SuperAdminBillingPage from './pages/app/SuperAdminBillingPage';
import SuperAdminSecurityPage from './pages/app/SuperAdminSecurityPage';
import SuperAdminContactSubmissionsPage from './pages/app/SuperAdminContactSubmissionsPage';
import SuperAdminSupportPortalPage from './pages/app/SuperAdminSupportPortalPage';
import SuperAdminAPIKeysPage from './pages/app/SuperAdminAPIKeysPage';
import SuperAdminSystemHealthPage from './pages/app/SuperAdminSystemHealthPage';
import SuperAdminSystemLogsPage from './pages/app/SuperAdminSystemLogsPage';

function App() {
  return (
    <AuthProvider>
      <div className="App">
        <CookieConsent />
        <Routes>
            {/* Landing Page (Special case - no header/footer) */}
            <Route path="/" element={<LandingPage />} />
            
            {/* Public Routes with Header/Footer and Chat Widget - Clean URLs */}
            <Route path="/home" element={<PublicLayout><HomePage /></PublicLayout>} />
            <Route path="/features" element={<PublicLayout><FeaturesPage /></PublicLayout>} />
            <Route path="/pricing" element={<PublicLayout><PricingPage /></PublicLayout>} />
            <Route path="/about" element={<PublicLayout><AboutPage /></PublicLayout>} />
            <Route path="/contact" element={<PublicLayout><ContactPage /></PublicLayout>} />
            <Route path="/support" element={<PublicLayout><SupportPortalPage /></PublicLayout>} />
            <Route path="/faq" element={<PublicLayout><FAQPage /></PublicLayout>} />
            <Route path="/privacy-policy" element={<PublicLayout><PrivacyPolicyPage /></PublicLayout>} />
            <Route path="/terms-of-use" element={<PublicLayout><TermsOfUsePage /></PublicLayout>} />
            <Route path="/cookie-policy" element={<PublicLayout><CookiePolicyPage /></PublicLayout>} />
            
            {/* Auth Routes */}
            <Route path="/auth/login" element={<LoginPage />} />
            <Route path="/auth/register" element={<RegisterPage />} />
            <Route path="/auth/super-admin" element={<SuperAdminLoginPage />} />
            
            {/* Protected App Routes - Using Nested Routing */}
            <Route 
              path="/app" 
              element={
                <ProtectedRoute>
                  <AppLayout />
                </ProtectedRoute>
              }
            >
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
              <Route path="user-management" element={<UserManagementPage />} />
              <Route path="sub-categories" element={<SubCategoriesPage />} />
              <Route path="discounts" element={<DiscountsPage />} />
              <Route path="coupons" element={<CouponsPage />} />
              <Route path="support" element={<SupportPage />} />
            </Route>
            
            {/* Protected Super Admin Routes - Using Nested Routing */}
            <Route 
              path="/super-admin" 
              element={
                <SuperAdminProtectedRoute>
                  <SuperAdminLayout />
                </SuperAdminProtectedRoute>
              }
            >
              <Route index element={<SuperAdminDashboard />} />
              <Route path="dashboard" element={<SuperAdminDashboard />} />
              <Route path="analytics" element={<SuperAdminAnalyticsPage />} />
              <Route path="tenants" element={<SuperAdminTenants />} />
              <Route path="users" element={<SuperAdminUsers />} />
              <Route path="settings" element={<SuperAdminSettings />} />
              <Route path="subscription-plans" element={<SuperAdminSubscriptionPlans />} />
              <Route path="billing" element={<SuperAdminBillingPage />} />
              <Route path="security" element={<SuperAdminSecurityPage />} />
              <Route path="contact-submissions" element={<SuperAdminContactSubmissionsPage />} />
              <Route path="support-portal" element={<SuperAdminSupportPortalPage />} />
              <Route path="api-keys" element={<SuperAdminAPIKeysPage />} />
              <Route path="system-health" element={<SuperAdminSystemHealthPage />} />
              <Route path="system-logs" element={<SuperAdminSystemLogsPage />} />
            </Route>
            
            {/* Default redirect */}
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
      </div>
    </AuthProvider>
  );
}

export default App;
