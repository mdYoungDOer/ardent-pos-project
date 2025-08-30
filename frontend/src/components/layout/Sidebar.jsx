import React from 'react'
import { Link, useLocation } from 'react-router-dom'
import { useAuth } from '../../contexts/AuthContext'
import {
  FiHome,
  FiPackage,
  FiShoppingCart,
  FiUsers,
  FiBarChart2,
  FiSettings,
  FiClipboard,
  FiTarget,
  FiLogOut,
  FiGrid,
  FiMapPin,
  FiDatabase,
  FiUserCheck,
  FiTag,
  FiGift,
  FiHelpCircle
} from 'react-icons/fi'

const Sidebar = () => {
  const location = useLocation()
  const { user, logout } = useAuth()

  // Regular client navigation
  const navigation = [
    { name: 'Dashboard', href: '/app/dashboard', icon: FiHome },
    { name: 'POS', href: '/app/pos', icon: FiShoppingCart },
    { name: 'Products', href: '/app/products', icon: FiPackage },
    { name: 'Categories', href: '/app/categories', icon: FiGrid },
    { name: 'Locations', href: '/app/locations', icon: FiMapPin },
    { name: 'Inventory', href: '/app/inventory', icon: FiDatabase },
    { name: 'Sales', href: '/app/sales', icon: FiBarChart2 },
    { name: 'Customers', href: '/app/customers', icon: FiUsers },
    { name: 'Reports', href: '/app/reports', icon: FiClipboard },
    { name: 'User Management', href: '/app/user-management', icon: FiUserCheck },
    { name: 'Sub-categories', href: '/app/sub-categories', icon: FiGrid },
    { name: 'Discounts', href: '/app/discounts', icon: FiTag },
    { name: 'Coupons', href: '/app/coupons', icon: FiGift },
    { name: 'Support', href: '/app/support', icon: FiHelpCircle },
    { name: 'Settings', href: '/app/settings', icon: FiSettings },
  ]

  const isSuperAdmin = user?.role === 'super_admin'

  const handleLogout = () => {
    logout()
  }

  return (
    <div className="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 lg:border-r lg:border-[#746354]/20 lg:bg-white lg:pt-5 lg:pb-4">
      {/* Logo */}
      <div className="flex items-center flex-shrink-0 px-6">
        <div className="flex items-center">
          <div className="w-8 h-8 bg-[#e41e5b] rounded-lg flex items-center justify-center mr-3">
            <FiTarget className="h-5 w-5 text-white" />
          </div>
          <h1 className="text-xl font-bold text-[#2c2c2c]">Ardent POS</h1>
        </div>
      </div>
      
      {/* User info */}
      <div className="mt-6 px-6">
        <div className="flex items-center p-3 bg-[#e41e5b]/5 rounded-lg border border-[#e41e5b]/10">
          <div className="h-10 w-10 rounded-full bg-[#e41e5b] flex items-center justify-center">
            <span className="text-sm font-medium text-white">
              {user?.first_name?.[0]}{user?.last_name?.[0]}
            </span>
          </div>
          <div className="ml-3 flex-1 min-w-0">
            <p className="text-sm font-semibold text-[#2c2c2c] truncate">
              {user?.first_name} {user?.last_name}
            </p>
            <div className="flex items-center">
              <span className={`text-xs px-2 py-1 rounded-full ${
                user?.role === 'super_admin' 
                  ? 'bg-purple-100 text-purple-700' 
                  : 'bg-[#a67c00]/10 text-[#a67c00]'
              }`}>
                {user?.role === 'super_admin' ? 'Super Admin' : user?.role}
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Navigation */}
      <nav className="mt-6 flex-1 px-3 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100 hover:scrollbar-thumb-gray-400">
        <div className="space-y-1">
          {navigation.map((item) => {
            const isActive = location.pathname === item.href
            return (
              <Link
                key={item.name}
                to={item.href}
                className={`group flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 ${
                  isActive
                    ? 'bg-[#e41e5b] text-white shadow-lg'
                    : 'text-[#746354] hover:bg-[#e41e5b]/5 hover:text-[#e41e5b]'
                }`}
              >
                <item.icon
                  className={`mr-3 h-5 w-5 flex-shrink-0 ${
                    isActive ? 'text-white' : 'text-[#746354] group-hover:text-[#e41e5b]'
                  }`}
                />
                <span className="truncate">{item.name}</span>
              </Link>
            )
          })}
        </div>
      </nav>

      {/* Logout Button */}
      <div className="p-3 border-t border-[#746354]/20">
        <button
          onClick={handleLogout}
          className="flex items-center w-full px-3 py-2 text-[#746354] hover:text-[#e41e5b] hover:bg-[#e41e5b]/5 rounded-lg transition-colors duration-200 text-sm"
        >
          <FiLogOut className="h-4 w-4 mr-2" />
          <span className="font-medium">Logout</span>
        </button>
      </div>
    </div>
  )
}

export default Sidebar
