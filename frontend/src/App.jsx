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
import FAQPage from './pages/public/FAQPage';
import PrivacyPolicyPage from './pages/public/PrivacyPolicyPage';
import TermsOfUsePage from './pages/public/TermsOfUsePage';
import CookiePolicyPage from './pages/public/CookiePolicyPage';
import LoginPage from './pages/auth/LoginPage';
import RegisterPage from './pages/auth/RegisterPage';
import SuperAdminLoginPage from './pages/auth/SuperAdminLoginPage';
import AppLayout from './layouts/AppLayout';
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

// Import all super admin pages
import SuperAdminDashboard from './pages/app/SuperAdminDashboard';
import SuperAdminAnalyticsPage from './pages/app/SuperAdminAnalyticsPage';
import SuperAdminTenants from './pages/app/TenantManagementPage';
import SuperAdminUsers from './pages/app/SuperAdminUserManagementPage';
import SuperAdminSettings from './pages/app/SuperAdminSettingsPage';
import SuperAdminSubscriptionPlans from './pages/app/SuperAdminSubscriptionPlansPage';

function App() {
  return (
    <AuthProvider>
      <div className="App">
        <CookieConsent />
        <Routes>
            {/* Public Routes */}
            <Route path="/" element={<LandingPage />} />
            <Route path="/home" element={<HomePage />} />
            <Route path="/features" element={<FeaturesPage />} />
            <Route path="/pricing" element={<PricingPage />} />
            <Route path="/about" element={<AboutPage />} />
            <Route path="/contact" element={<ContactPage />} />
            <Route path="/faq" element={<FAQPage />} />
            <Route path="/privacy-policy" element={<PrivacyPolicyPage />} />
            <Route path="/terms-of-use" element={<TermsOfUsePage />} />
            <Route path="/cookie-policy" element={<CookiePolicyPage />} />
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
              <Route path="subscriptions" element={<SuperAdminSubscriptionPlans />} />
              <Route path="settings" element={<SuperAdminSettings />} />
              {/* Add placeholder routes for missing pages */}
              <Route path="billing" element={<SuperAdminDashboard />} />
              <Route path="security" element={<SuperAdminDashboard />} />
              <Route path="logs" element={<SuperAdminDashboard />} />
              <Route path="api-keys" element={<SuperAdminDashboard />} />
              <Route path="monitoring" element={<SuperAdminDashboard />} />
            </Route>
            
            {/* Default redirect */}
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </div>
      </AuthProvider>
  );
}

export default App;
