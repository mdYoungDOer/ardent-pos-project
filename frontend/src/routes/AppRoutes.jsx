import React from 'react';
import { Routes, Route } from 'react-router-dom';
import AppLayout from '../layouts/AppLayout';
import DashboardPage from '../pages/app/DashboardPage';
import ProductsPage from '../pages/app/ProductsPage';
import InventoryPage from '../pages/app/InventoryPage';
import SalesPage from '../pages/app/SalesPage';
import CustomersPage from '../pages/app/CustomersPage';
import ReportsPage from '../pages/app/ReportsPage';
import SettingsPage from '../pages/app/SettingsPage';
import NotificationSettingsPage from '../pages/app/NotificationSettingsPage';
import UserManagementPage from '../pages/app/UserManagementPage';
import SubCategoriesPage from '../pages/app/SubCategoriesPage';
import DiscountsPage from '../pages/app/DiscountsPage';
import CouponsPage from '../pages/app/CouponsPage';
import POSPage from '../pages/app/POSPage';
import CategoriesPage from '../pages/app/CategoriesPage';
import LocationsPage from '../pages/app/LocationsPage';

const AppRoutes = () => {
  return (
    <AppLayout>
      <Routes>
        <Route path="/" element={<DashboardPage />} />
        <Route path="/dashboard" element={<DashboardPage />} />
        <Route path="/app" element={<DashboardPage />} />
        <Route path="/pos" element={<POSPage />} />
        <Route path="/products" element={<ProductsPage />} />
        <Route path="/categories" element={<CategoriesPage />} />
        <Route path="/locations" element={<LocationsPage />} />
        <Route path="/inventory" element={<InventoryPage />} />
        <Route path="/sales" element={<SalesPage />} />
        <Route path="/customers" element={<CustomersPage />} />
        <Route path="/reports" element={<ReportsPage />} />
        <Route path="/settings" element={<SettingsPage />} />
        <Route path="/notifications" element={<NotificationSettingsPage />} />
        <Route path="/user-management" element={<UserManagementPage />} />
        <Route path="/sub-categories" element={<SubCategoriesPage />} />
        <Route path="/discounts" element={<DiscountsPage />} />
        <Route path="/coupons" element={<CouponsPage />} />
      </Routes>
    </AppLayout>
  );
};

export default AppRoutes;
