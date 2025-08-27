import React from 'react';
import { Routes, Route } from 'react-router-dom';
import SuperAdminLayout from '../layouts/SuperAdminLayout';
import SuperAdminDashboard from '../pages/app/SuperAdminDashboard';
import SuperAdminAnalyticsPage from '../pages/app/SuperAdminAnalyticsPage';
import SuperAdminTenants from '../pages/app/TenantManagementPage';
import SuperAdminUsers from '../pages/app/SuperAdminUserManagementPage';
import SuperAdminSettings from '../pages/app/SuperAdminSettingsPage';
import SuperAdminSubscriptionPlans from '../pages/app/SuperAdminSubscriptionPlansPage';

const SuperAdminRoutes = () => {
  return (
    <SuperAdminLayout>
      <Routes>
        <Route path="/" element={<SuperAdminDashboard />} />
        <Route path="/dashboard" element={<SuperAdminDashboard />} />
        <Route path="/analytics" element={<SuperAdminAnalyticsPage />} />
        <Route path="/tenants" element={<SuperAdminTenants />} />
        <Route path="/users" element={<SuperAdminUsers />} />
        <Route path="/subscriptions" element={<SuperAdminSubscriptionPlans />} />
        <Route path="/settings" element={<SuperAdminSettings />} />
      </Routes>
    </SuperAdminLayout>
  );
};

export default SuperAdminRoutes;
