import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import LandingPage from './pages/public/LandingPage';
import LoginPage from './pages/auth/LoginPage';
import RegisterPage from './pages/auth/RegisterPage';
import SuperAdminLoginPage from './pages/auth/SuperAdminLoginPage';
import AppRoutes from './routes/AppRoutes';
import SuperAdminRoutes from './routes/SuperAdminRoutes';
import ProtectedRoute from './components/auth/ProtectedRoute';
import SuperAdminProtectedRoute from './components/auth/SuperAdminProtectedRoute';

function App() {
  return (
    <AuthProvider>
      <Router>
        <div className="App">
          <Routes>
            {/* Public Routes */}
            <Route path="/" element={<LandingPage />} />
            <Route path="/auth/login" element={<LoginPage />} />
            <Route path="/auth/register" element={<RegisterPage />} />
            <Route path="/auth/super-admin" element={<SuperAdminLoginPage />} />
            
            {/* Protected App Routes */}
            <Route 
              path="/app/*" 
              element={
                <ProtectedRoute>
                  <AppRoutes />
                </ProtectedRoute>
              } 
            />
            
            {/* Protected Super Admin Routes */}
            <Route 
              path="/super-admin/*" 
              element={
                <SuperAdminProtectedRoute>
                  <SuperAdminRoutes />
                </SuperAdminProtectedRoute>
              } 
            />
            
            {/* Default redirect */}
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </div>
      </Router>
    </AuthProvider>
  );
}

export default App;
