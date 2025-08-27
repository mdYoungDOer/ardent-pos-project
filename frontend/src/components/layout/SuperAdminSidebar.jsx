import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import { 
  FiHome, 
  FiBarChart2, 
  FiUsers, 
  FiSettings, 
  FiLogOut, 
  FiBriefcase,
  FiCreditCard,
  FiActivity,
  FiShield,
  FiFileText,
  FiKey,
  FiTrendingUp
} from 'react-icons/fi';
import { useAuth } from '../../contexts/AuthContext';

const SuperAdminSidebar = () => {
  const location = useLocation();
  const { logout } = useAuth();

  const menuItems = [
    { 
      path: '/super-admin/dashboard', 
      name: 'Dashboard', 
      icon: FiHome,
      status: 'active'
    },
    { 
      path: '/super-admin/analytics', 
      name: 'Analytics', 
      icon: FiBarChart2,
      status: 'active'
    },
    { 
      path: '/super-admin/tenants', 
      name: 'Tenant Management', 
      icon: FiBriefcase,
      status: 'active'
    },
    { 
      path: '/super-admin/users', 
      name: 'User Management', 
      icon: FiUsers,
      status: 'active'
    },
    { 
      path: '/super-admin/subscriptions', 
      name: 'Subscription Plans', 
      icon: FiCreditCard,
      status: 'active'
    },
    { 
      path: '/super-admin/contact-submissions', 
      name: 'Contact Submissions', 
      icon: FiFileText,
      status: 'active'
    },
    { 
      path: '/super-admin/api-keys', 
      name: 'API Keys Management', 
      icon: FiKey,
      status: 'active'
    },
    { 
      path: '/super-admin/billing', 
      name: 'Billing & Payments', 
      icon: FiActivity,
      status: 'active'
    },
    { 
      path: '/super-admin/security', 
      name: 'Security Management', 
      icon: FiShield,
      status: 'active'
    },
    { 
      path: '/super-admin/health', 
      name: 'System Health', 
      icon: FiTrendingUp,
      status: 'active'
    },
    { 
      path: '/super-admin/logs', 
      name: 'System Logs', 
      icon: FiFileText,
      status: 'active'
    },
    { 
      path: '/super-admin/settings', 
      name: 'System Settings', 
      icon: FiSettings,
      status: 'active'
    }
  ];

  const handleLogout = () => {
    logout();
  };

  return (
    <div className="bg-dark text-white w-64 min-h-screen flex flex-col">
      {/* Logo */}
      <div className="p-6 border-b border-neutral">
        <h1 className="text-xl font-bold text-primary">Ardent POS</h1>
        <p className="text-sm text-neutral mt-1">Super Admin</p>
      </div>

      {/* Navigation Menu */}
      <nav className="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        {menuItems.map((item) => {
          const Icon = item.icon;
          const isActive = location.pathname === item.path;
          
          return (
            <Link
              key={item.path}
              to={item.path}
              className={`flex items-center px-4 py-3 rounded-lg transition-colors duration-200 ${
                isActive
                  ? 'bg-primary text-white'
                  : 'text-neutral hover:bg-neutral hover:text-white'
              }`}
            >
              <Icon className="h-5 w-5 mr-3" />
              <span className="font-medium">{item.name}</span>
              {item.status === 'coming-soon' && (
                <span className="ml-auto text-xs bg-yellow-500 text-white px-2 py-1 rounded-full">
                  Soon
                </span>
              )}
            </Link>
          );
        })}
      </nav>

      {/* Logout Button - Always visible at bottom */}
      <div className="p-4 border-t border-neutral">
        <button
          onClick={handleLogout}
          className="flex items-center w-full px-4 py-3 text-neutral hover:text-white hover:bg-neutral rounded-lg transition-colors duration-200"
        >
          <FiLogOut className="h-5 w-5 mr-3" />
          <span className="font-medium">Logout</span>
        </button>
      </div>
    </div>
  );
};

export default SuperAdminSidebar;
