import React from 'react';
import { Routes, Route } from 'react-router-dom';
import SuperAdminLayout from '../components/layout/SuperAdminLayout';
import SuperAdminDashboard from '../pages/app/SuperAdminDashboard';
import SuperAdminAnalytics from '../pages/app/SuperAdminAnalytics';
import SuperAdminTenants from '../pages/app/SuperAdminTenants';
import SuperAdminUsers from '../pages/app/SuperAdminUsers';
import SuperAdminSettings from '../pages/app/SuperAdminSettings';
import SuperAdminSubscriptionPlans from '../pages/app/SuperAdminSubscriptionPlansPage';

const SuperAdminRoutes = () => {
  return (
    <SuperAdminLayout>
      <Routes>
        <Route path="/dashboard" element={<SuperAdminDashboard />} />
        <Route path="/analytics" element={<SuperAdminAnalytics />} />
        <Route path="/tenants" element={<SuperAdminTenants />} />
        <Route path="/users" element={<SuperAdminUsers />} />
        <Route path="/subscriptions" element={<SuperAdminSubscriptionPlans />} />
        <Route path="/settings" element={<SuperAdminSettings />} />
      </Routes>
    </SuperAdminLayout>
  );
};

export default SuperAdminRoutes;
