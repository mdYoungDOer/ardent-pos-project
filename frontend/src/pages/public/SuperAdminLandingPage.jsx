import React from 'react';
import { Link } from 'react-router-dom';
import { 
  FiShield, FiUsers, FiBarChart2, FiSettings, FiDatabase, 
  FiActivity, FiTrendingUp, FiAward, FiTarget, FiGlobe 
} from 'react-icons/fi';

const SuperAdminLandingPage = () => {
  return (
    <div className="min-h-screen bg-gradient-to-br from-[#e41e5b]/5 via-[#9a0864]/5 to-[#a67c00]/5">
      {/* Header */}
      <div className="bg-white shadow-sm border-b border-[#746354]/10">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center py-6">
            <div className="flex items-center">
              <div className="w-10 h-10 bg-gradient-to-br from-[#e41e5b] to-[#9a0864] rounded-lg flex items-center justify-center mr-3">
                <FiShield className="h-6 w-6 text-white" />
              </div>
              <h1 className="text-2xl font-bold text-[#2c2c2c]">Ardent POS</h1>
            </div>
            <div className="flex items-center space-x-4">
              <Link 
                to="/auth/login"
                className="text-[#746354] hover:text-[#2c2c2c] transition-colors"
              >
                Business Login
              </Link>
              <Link 
                to="/auth/super-admin"
                className="bg-gradient-to-r from-[#e41e5b] to-[#9a0864] text-white px-6 py-2 rounded-lg hover:from-[#9a0864] hover:to-[#e41e5b] transition-all duration-200 font-medium"
              >
                Super Admin Access
              </Link>
            </div>
          </div>
        </div>
      </div>

      {/* Hero Section */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div className="text-center">
          <div className="mx-auto h-24 w-24 bg-gradient-to-br from-[#e41e5b] to-[#9a0864] rounded-3xl flex items-center justify-center shadow-2xl mb-8">
            <FiShield className="h-12 w-12 text-white" />
          </div>
          <h1 className="text-5xl font-bold text-[#2c2c2c] mb-6">
            Super Admin Portal
          </h1>
          <p className="text-xl text-[#746354] mb-8 max-w-3xl mx-auto">
            Secure system administration and multi-tenant management portal for the Ardent POS platform.
            Manage all businesses, monitor system health, and ensure platform security.
          </p>
          <div className="flex justify-center space-x-4">
            <Link 
              to="/auth/super-admin"
              className="bg-gradient-to-r from-[#e41e5b] to-[#9a0864] text-white px-8 py-4 rounded-xl hover:from-[#9a0864] hover:to-[#e41e5b] transition-all duration-200 font-semibold text-lg shadow-lg hover:shadow-xl"
            >
              Access Super Admin Portal
            </Link>
            <Link 
              to="/auth/login"
              className="bg-white text-[#2c2c2c] px-8 py-4 rounded-xl border-2 border-[#746354]/20 hover:border-[#e41e5b] hover:text-[#e41e5b] transition-all duration-200 font-semibold text-lg"
            >
              Business Login
            </Link>
          </div>
        </div>
      </div>

      {/* Features Section */}
      <div className="bg-white py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl font-bold text-[#2c2c2c] mb-4">
              Super Admin Capabilities
            </h2>
            <p className="text-[#746354] max-w-2xl mx-auto">
              Comprehensive system administration tools designed for enterprise-level management
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {/* Tenant Management */}
            <div className="bg-gradient-to-br from-[#e41e5b]/5 to-[#9a0864]/5 rounded-2xl p-8 border border-[#e41e5b]/10">
              <div className="w-12 h-12 bg-[#e41e5b] rounded-xl flex items-center justify-center mb-6">
                <FiUsers className="h-6 w-6 text-white" />
              </div>
              <h3 className="text-xl font-semibold text-[#2c2c2c] mb-3">Tenant Management</h3>
              <p className="text-[#746354]">
                Create, manage, and monitor all business tenants. Control access, subscriptions, and account status.
              </p>
            </div>

            {/* System Analytics */}
            <div className="bg-gradient-to-br from-[#9a0864]/5 to-[#a67c00]/5 rounded-2xl p-8 border border-[#9a0864]/10">
              <div className="w-12 h-12 bg-[#9a0864] rounded-xl flex items-center justify-center mb-6">
                <FiBarChart2 className="h-6 w-6 text-white" />
              </div>
              <h3 className="text-xl font-semibold text-[#2c2c2c] mb-3">System Analytics</h3>
              <p className="text-[#746354]">
                Real-time system-wide analytics, performance metrics, and business intelligence.
              </p>
            </div>

            {/* System Health */}
            <div className="bg-gradient-to-br from-[#a67c00]/5 to-[#746354]/5 rounded-2xl p-8 border border-[#a67c00]/10">
              <div className="w-12 h-12 bg-[#a67c00] rounded-xl flex items-center justify-center mb-6">
                <FiActivity className="h-6 w-6 text-white" />
              </div>
              <h3 className="text-xl font-semibold text-[#2c2c2c] mb-3">System Health</h3>
              <p className="text-[#746354]">
                Monitor server performance, database health, and API status in real-time.
              </p>
            </div>

            {/* Security Management */}
            <div className="bg-gradient-to-br from-[#746354]/5 to-[#2c2c2c]/5 rounded-2xl p-8 border border-[#746354]/10">
              <div className="w-12 h-12 bg-[#746354] rounded-xl flex items-center justify-center mb-6">
                <FiShield className="h-6 w-6 text-white" />
              </div>
              <h3 className="text-xl font-semibold text-[#2c2c2c] mb-3">Security Management</h3>
              <p className="text-[#746354]">
                Advanced security controls, user access management, and audit logging.
              </p>
            </div>

            {/* Database Management */}
            <div className="bg-gradient-to-br from-[#2c2c2c]/5 to-[#e41e5b]/5 rounded-2xl p-8 border border-[#2c2c2c]/10">
              <div className="w-12 h-12 bg-[#2c2c2c] rounded-xl flex items-center justify-center mb-6">
                <FiDatabase className="h-6 w-6 text-white" />
              </div>
              <h3 className="text-xl font-semibold text-[#2c2c2c] mb-3">Database Management</h3>
              <p className="text-[#746354]">
                Database administration, backup management, and data integrity monitoring.
              </p>
            </div>

            {/* Global Settings */}
            <div className="bg-gradient-to-br from-[#e41e5b]/5 to-[#9a0864]/5 rounded-2xl p-8 border border-[#e41e5b]/10">
              <div className="w-12 h-12 bg-[#e41e5b] rounded-xl flex items-center justify-center mb-6">
                <FiSettings className="h-6 w-6 text-white" />
              </div>
              <h3 className="text-xl font-semibold text-[#2c2c2c] mb-3">Global Settings</h3>
              <p className="text-[#746354]">
                Platform-wide configuration, feature toggles, and system preferences.
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Security Notice */}
      <div className="bg-gradient-to-r from-[#a67c00]/10 to-[#9a0864]/10 py-16">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <div className="bg-white rounded-2xl p-8 shadow-lg border border-[#a67c00]/20">
            <div className="w-16 h-16 bg-[#a67c00] rounded-2xl flex items-center justify-center mx-auto mb-6">
              <FiShield className="h-8 w-8 text-white" />
            </div>
            <h3 className="text-2xl font-bold text-[#2c2c2c] mb-4">Enterprise Security</h3>
            <p className="text-[#746354] mb-6">
              The Super Admin portal is protected by enterprise-grade security measures. 
              All access attempts are logged and monitored. Only authorized personnel should access this portal.
            </p>
            <div className="flex justify-center">
              <Link 
                to="/auth/super-admin"
                className="bg-gradient-to-r from-[#a67c00] to-[#9a0864] text-white px-8 py-3 rounded-xl hover:from-[#9a0864] hover:to-[#a67c00] transition-all duration-200 font-semibold"
              >
                Secure Access Portal
              </Link>
            </div>
          </div>
        </div>
      </div>

      {/* Footer */}
      <div className="bg-[#2c2c2c] text-white py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <p className="text-[#746354]">
            © 2024 Ardent POS. Super Admin Portal - Enterprise Access Only.
          </p>
        </div>
      </div>
    </div>
  );
};

export default SuperAdminLandingPage;
