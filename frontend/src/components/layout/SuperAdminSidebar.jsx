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
  FiTrendingUp,
  FiHelpCircle,
  FiBookOpen,
  FiMessageSquare,
  FiMail,
  FiUserCheck,
  FiDatabase,
  FiMonitor,
  FiTarget
} from 'react-icons/fi';
import { useAuth } from '../../contexts/AuthContext';

const SuperAdminSidebar = () => {
  const location = useLocation();
  const { logout, user } = useAuth();

  // Organized navigation categories for Super Admin
  const navigationCategories = [
    {
      category: 'Dashboard',
      items: [
        { 
          path: '/super-admin/dashboard', 
          name: 'Super Admin Dashboard', 
          icon: FiTarget,
          status: 'active'
        }
      ]
    },
    {
      category: 'Core Management',
      items: [
        { 
          path: '/super-admin/tenants', 
          name: 'Tenant Management', 
          icon: FiBriefcase,
          status: 'active'
        },
        { 
          path: '/super-admin/users', 
          name: 'User Management', 
          icon: FiUserCheck,
          status: 'active'
        },
        { 
          path: '/super-admin/subscription-plans', 
          name: 'Subscription Plans', 
          icon: FiCreditCard,
          status: 'active'
        }
      ]
    },
    {
      category: 'Support & Knowledge',
      items: [
        { 
          path: '/super-admin/knowledgebase', 
          name: 'Knowledgebase Management', 
          icon: FiBookOpen,
          status: 'active'
        },
        { 
          path: '/super-admin/support-tickets', 
          name: 'Support Tickets', 
          icon: FiMessageSquare,
          status: 'active'
        },
        { 
          path: '/super-admin/contact-submissions', 
          name: 'Contact Submissions', 
          icon: FiMail,
          status: 'active'
        }
      ]
    },
    {
      category: 'Analytics & Reports',
      items: [
        { 
          path: '/super-admin/analytics', 
          name: 'System Analytics', 
          icon: FiBarChart2,
          status: 'active'
        },
        { 
          path: '/super-admin/billing', 
          name: 'Billing & Payments', 
          icon: FiActivity,
          status: 'active'
        }
      ]
    },
    {
      category: 'System Administration',
      items: [
        { 
          path: '/super-admin/api-keys', 
          name: 'API Keys Management', 
          icon: FiKey,
          status: 'active'
        },
        { 
          path: '/super-admin/security', 
          name: 'Security Management', 
          icon: FiShield,
          status: 'active'
        },
        { 
          path: '/super-admin/system-health', 
          name: 'System Health', 
          icon: FiTrendingUp,
          status: 'active'
        },
        { 
          path: '/super-admin/system-logs', 
          name: 'System Logs', 
          icon: FiFileText,
          status: 'active'
        }
      ]
    },
    {
      category: 'Configuration',
      items: [
        { 
          path: '/super-admin/settings', 
          name: 'Global Settings', 
          icon: FiSettings,
          status: 'active'
        }
      ]
    }
  ];

  const handleLogout = () => {
    logout();
  };

  return (
    <div className="bg-white border-r border-gray-200 w-56 min-h-screen flex flex-col shadow-sm">
      {/* Logo */}
      <div className="p-4 border-b border-gray-200">
        <div className="flex items-center">
          <div className="w-7 h-7 bg-[#e41e5b] rounded-lg flex items-center justify-center mr-2">
            <FiTarget className="h-4 w-4 text-white" />
          </div>
          <h1 className="text-lg font-bold text-[#2c2c2c]">Ardent POS</h1>
          <div className="ml-2 px-1.5 py-0.5 bg-purple-100 text-purple-700 text-xs font-medium rounded-full">
            Super Admin
          </div>
        </div>
      </div>

      {/* User info */}
      <div className="p-4 border-b border-gray-200">
        <div className="flex items-center p-2 bg-[#e41e5b]/5 rounded-lg border border-[#e41e5b]/10">
          <div className="h-8 w-8 rounded-full bg-[#e41e5b] flex items-center justify-center">
            <span className="text-xs font-medium text-white">
              {user?.first_name?.[0]}{user?.last_name?.[0]}
            </span>
          </div>
          <div className="ml-2 flex-1 min-w-0">
            <p className="text-xs font-semibold text-[#2c2c2c] truncate">
              {user?.first_name} {user?.last_name}
            </p>
            <span className="text-xs px-1.5 py-0.5 rounded-full bg-purple-100 text-purple-700">
              Super Admin
            </span>
          </div>
        </div>
      </div>

      {/* Navigation Menu */}
      <nav className="flex-1 px-3 py-4 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100 hover:scrollbar-thumb-gray-400">
        <div className="space-y-4">
          {navigationCategories.map((category) => (
            <div key={category.category}>
              <h3 className="px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                {category.category}
              </h3>
              <div className="space-y-1">
                {category.items.map((item) => {
                  const Icon = item.icon;
                  const isActive = location.pathname === item.path;
                  
                  return (
                    <Link
                      key={item.path}
                      to={item.path}
                      className={`group flex items-center px-2 py-2 text-sm font-medium rounded-lg transition-all duration-200 ${
                        isActive
                          ? 'bg-[#e41e5b] text-white shadow-sm'
                          : 'text-[#746354] hover:bg-[#e41e5b]/5 hover:text-[#e41e5b]'
                      }`}
                    >
                      <Icon
                        className={`mr-3 h-4 w-4 flex-shrink-0 ${
                          isActive ? 'text-white' : 'text-[#746354] group-hover:text-[#e41e5b]'
                        }`}
                      />
                      <span className="truncate flex-1 text-xs">{item.name}</span>
                      {item.status === 'coming-soon' && (
                        <span className="ml-2 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                          Soon
                        </span>
                      )}
                    </Link>
                  );
                })}
              </div>
            </div>
          ))}
        </div>
      </nav>

      {/* Logout Button */}
      <div className="p-3 border-t border-gray-200">
        <button
          onClick={handleLogout}
          className="flex items-center w-full px-2 py-2 text-[#746354] hover:text-[#e41e5b] hover:bg-[#e41e5b]/5 rounded-lg transition-all duration-200 text-sm"
        >
          <FiLogOut className="h-4 w-4 mr-3" />
          <span className="font-medium text-xs">Logout</span>
        </button>
      </div>
    </div>
  );
};

export default SuperAdminSidebar;
