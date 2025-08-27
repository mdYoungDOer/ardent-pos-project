import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import LandingPage from './pages/public/LandingPage';
import HomePage from './pages/public/HomePage';
import FeaturesPage from './pages/public/FeaturesPage';
import PricingPage from './pages/public/PricingPage';
import AboutPage from './pages/public/AboutPage';
import ContactPage from './pages/public/ContactPage';
import FAQPage from './pages/public/FAQPage';
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
                  <Routes>
            {/* Public Routes */}
            <Route path="/" element={<LandingPage />} />
            <Route path="/home" element={<HomePage />} />
            <Route path="/features" element={<FeaturesPage />} />
            <Route path="/pricing" element={<PricingPage />} />
            <Route path="/about" element={<AboutPage />} />
            <Route path="/contact" element={<ContactPage />} />
            <Route path="/faq" element={<FAQPage />} />
            <Route path="/auth/login" element={<LoginPage />} />
            <Route path="/auth/register" element={<RegisterPage />} />
            <Route path="/auth/super-admin" element={<SuperAdminLoginPage />} />
            
            {/* Protected App Routes */}
            <Route 
              path="/app" 
              element={
                <ProtectedRoute>
                  <AppLayout>
                    <DashboardPage />
                  </AppLayout>
                </ProtectedRoute>
              } 
            />
            <Route 
              path="/app/dashboard" 
              element={
                <ProtectedRoute>
                  <AppLayout>
                    <DashboardPage />
                  </AppLayout>
                </ProtectedRoute>
              } 
            />
            <Route 
              path="/app/pos" 
              element={
                <ProtectedRoute>
                  <AppLayout>
                    <POSPage />
                  </AppLayout>
                </ProtectedRoute>
              } 
            />
            <Route 
              path="/app/products" 
              element={
                <ProtectedRoute>
                  <AppLayout>
                    <ProductsPage />
                  </AppLayout>
                </ProtectedRoute>
              } 
            />
            <Route 
              path="/app/categories" 
              element={
                <ProtectedRoute>
                  <AppLayout>
                    <CategoriesPage />
                  </AppLayout>
                </ProtectedRoute>
              } 
            />
            <Route 
              path="/app/locations" 
              element={
                <ProtectedRoute>
                  <AppLayout>
                    <LocationsPage />
                  </AppLayout>
                </ProtectedRoute>
              } 
            />
            <Route 
              path="/app/inventory" 
              element={
                <ProtectedRoute>
                  <AppLayout>
                    <InventoryPage />
                  </AppLayout>
                </ProtectedRoute>
              } 
            />
            <Route 
              path="/app/sales" 
              element={
                <ProtectedRoute>
                  <AppLayout>
                    <SalesPage />
                  </AppLayout>
                </ProtectedRoute>
              } 
            />
            <Route 
              path="/app/customers" 
              element={
                <ProtectedRoute>
                  <AppLayout>
                    <CustomersPage />
                  </AppLayout>
                </ProtectedRoute>
              } 
            />
            <Route 
              path="/app/reports" 
              element={
                <ProtectedRoute>
                  <AppLayout>
                    <ReportsPage />
                  </AppLayout>
                </ProtectedRoute>
              } 
            />
            <Route 
              path="/app/settings" 
              element={
                <ProtectedRoute>
                  <AppLayout>
                    <SettingsPage />
                  </AppLayout>
                </ProtectedRoute>
              } 
            />
            <Route 
              path="/app/notifications" 
              element={
                <ProtectedRoute>
                  <AppLayout>
                    <NotificationSettingsPage />
                  </AppLayout>
                </ProtectedRoute>
              } 
            />
            <Route 
              path="/app/user-management" 
              element={
                <ProtectedRoute>
                  <AppLayout>
                    <UserManagementPage />
                  </AppLayout>
                </ProtectedRoute>
              } 
            />
            <Route 
              path="/app/sub-categories" 
              element={
                <ProtectedRoute>
                  <AppLayout>
                    <SubCategoriesPage />
                  </AppLayout>
                </ProtectedRoute>
              } 
            />
            <Route 
              path="/app/discounts" 
              element={
                <ProtectedRoute>
                  <AppLayout>
                    <DiscountsPage />
                  </AppLayout>
                </ProtectedRoute>
              } 
            />
            <Route 
              path="/app/coupons" 
              element={
                <ProtectedRoute>
                  <AppLayout>
                    <CouponsPage />
                  </AppLayout>
                </ProtectedRoute>
              } 
            />
            
            {/* Protected Super Admin Routes */}
            <Route 
              path="/super-admin" 
              element={
                <SuperAdminProtectedRoute>
                  <SuperAdminLayout>
                    <SuperAdminDashboard />
                  </SuperAdminLayout>
                </SuperAdminProtectedRoute>
              } 
            />
            <Route 
              path="/super-admin/dashboard" 
              element={
                <SuperAdminProtectedRoute>
                  <SuperAdminLayout>
                    <SuperAdminDashboard />
                  </SuperAdminLayout>
                </SuperAdminProtectedRoute>
              } 
            />
            <Route 
              path="/super-admin/analytics" 
              element={
                <SuperAdminProtectedRoute>
                  <SuperAdminLayout>
                    <SuperAdminAnalyticsPage />
                  </SuperAdminLayout>
                </SuperAdminProtectedRoute>
              } 
            />
            <Route 
              path="/super-admin/tenants" 
              element={
                <SuperAdminProtectedRoute>
                  <SuperAdminLayout>
                    <SuperAdminTenants />
                  </SuperAdminLayout>
                </SuperAdminProtectedRoute>
              } 
            />
            <Route 
              path="/super-admin/users" 
              element={
                <SuperAdminProtectedRoute>
                  <SuperAdminLayout>
                    <SuperAdminUsers />
                  </SuperAdminLayout>
                </SuperAdminProtectedRoute>
              } 
            />
            <Route 
              path="/super-admin/subscriptions" 
              element={
                <SuperAdminProtectedRoute>
                  <SuperAdminLayout>
                    <SuperAdminSubscriptionPlans />
                  </SuperAdminLayout>
                </SuperAdminProtectedRoute>
              } 
            />
            <Route 
              path="/super-admin/settings" 
              element={
                <SuperAdminProtectedRoute>
                  <SuperAdminLayout>
                    <SuperAdminSettings />
                  </SuperAdminLayout>
                </SuperAdminProtectedRoute>
              } 
            />
            
            {/* Default redirect */}
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </div>
      </AuthProvider>
  );
}

export default App;
