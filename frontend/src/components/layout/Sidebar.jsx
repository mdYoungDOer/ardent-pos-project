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
  FiShield,
  FiDatabase,
  FiActivity,
  FiTrendingUp,
  FiGlobe,
  FiAward,
  FiTarget,
  FiLogOut,
  FiBell,
  FiTag,
  FiMapPin,
  FiUserCheck,
  FiPercent,
  FiGift,
  FiFolder,
  FiMail,
  FiKey,
  FiDollarSign,
  FiFileText,
  FiHelpCircle,
  FiBookOpen,
  FiMessageSquare,
  FiMonitor,
  FiServer,
  FiAlertTriangle,
  FiGrid,
  FiLayers,
  FiCreditCard,
  FiArchive,
  FiEye,
  FiEdit,
  FiTrash,
  FiPlus,
  FiSearch,
  FiFilter,
  FiDownload,
  FiUpload,
  FiRefreshCw,
  FiCalendar,
  FiClock,
  FiStar,
  FiHeart,
  FiThumbsUp,
  FiThumbsDown,
  FiCheckCircle,
  FiXCircle,
  FiInfo,
  FiExternalLink,
  FiLock,
  FiUnlock,
  FiUser,
  FiUserPlus,
  FiUserMinus,
  FiUserX,
  FiUsers as FiUserGroup,
  FiBuilding,
  FiMap,
  FiNavigation,
  FiCompass,
  FiFlag,
  FiGift as FiReward,
  FiAward as FiBadge,
  FiTrendingUp as FiGrowth,
  FiTrendingDown as FiDecline,
  FiPieChart,
  FiBarChart,
  FiLineChart,
  FiScatterChart,
  FiMinusSquare,
  FiPlusSquare,
  FiSquare,
  FiCheckSquare,
  FiRadio,
  FiToggleLeft,
  FiToggleRight,
  FiChevronDown,
  FiChevronUp,
  FiChevronLeft,
  FiChevronRight,
  FiArrowLeft,
  FiArrowRight,
  FiArrowUp,
  FiArrowDown,
  FiMove,
  FiCopy,
  FiSave,
  FiPrinter,
  FiShare,
  FiLink,
  FiUnlink,
  FiAnchor,
  FiHash,
  FiAtSign,
  FiPhone,
  FiSmartphone,
  FiTablet,
  FiMonitor as FiDesktop,
  FiLaptop,
  FiHardDrive,
  FiCpu,
  FiWifi,
  FiBluetooth,
  FiZap,
  FiBattery,
  FiBatteryCharging,
  FiVolume,
  FiVolume1,
  FiVolume2,
  FiVolumeX,
  FiMic,
  FiMicOff,
  FiVideo,
  FiVideoOff,
  FiCamera,
  FiImage,
  FiMusic,
  FiHeadphones,
  FiSpeaker,
  FiRadio as FiBroadcast,
  FiTv,
  FiFilm,
  FiPlay,
  FiPause,
  FiSkipBack,
  FiSkipForward,
  FiRewind,
  FiFastForward,
  FiRotateCcw,
  FiRotateCw,
  FiRepeat,
  FiShuffle,
  FiVolume as FiSound,
  FiMaximize,
  FiMinimize,
  FiMaximize2,
  FiMinimize2,
  FiZoomIn,
  FiZoomOut,
  FiMove as FiDrag,
  FiCornerUpLeft,
  FiCornerUpRight,
  FiCornerDownLeft,
  FiCornerDownRight,
  FiCornerLeftUp,
  FiCornerLeftDown,
  FiCornerRightUp,
  FiCornerRightDown,
  FiCrop,
  FiScissors,
  FiType,
  FiBold,
  FiItalic,
  FiUnderline,
  FiStrikethrough,
  FiAlignLeft,
  FiAlignCenter,
  FiAlignRight,
  FiAlignJustify,
  FiList,
  FiGrid as FiLayout,
  FiSidebar,
  FiColumns,
  FiRows,
  FiLayers as FiStack,
  FiBox,
  FiPackage as FiCube,
  FiArchive as FiFolderPlus,
  FiFolderMinus,
  FiFolder as FiFolderOpen,
  FiFile,
  FiFileText as FiFilePlus,
  FiFileMinus,
  FiFile as FiFileX,
  FiPaperclip,
  FiLink as FiLink2,
  FiUnlink as FiUnlink2,
  FiLock as FiLock2,
  FiUnlock as FiUnlock2,
  FiShield as FiShield2,
  FiShieldOff,
  FiEye as FiEye2,
  FiEyeOff,
  FiEye as FiVisibility,
  FiEyeOff as FiVisibilityOff,
  FiSun,
  FiMoon,
  FiCloud,
  FiCloudRain,
  FiCloudSnow,
  FiCloudLightning,
  FiWind,
  FiThermometer,
  FiDroplet,
  FiUmbrella,
  FiCloud as FiCloudy,
  FiCloudOff,
  FiCloud as FiCloudDrizzle,
  FiCloud as FiCloudFog,
  FiCloud as FiCloudHail,
  FiCloud as FiCloudSleet,
  FiCloud as FiCloudSmog,
  FiCloud as FiCloudSun,
  FiCloud as FiCloudMoon,
  FiCloud as FiCloudLightning2,
  FiCloud as FiCloudRain2,
  FiCloud as FiCloudSnow2,
  FiCloud as FiCloudWind,
  FiCloud as FiCloudFog2,
  FiCloud as FiCloudHail2,
  FiCloud as FiCloudSleet2,
  FiCloud as FiCloudSmog2,
  FiCloud as FiCloudSun2,
  FiCloud as FiCloudMoon2,
  FiCloud as FiCloudLightning3,
  FiCloud as FiCloudRain3,
  FiCloud as FiCloudSnow3,
  FiCloud as FiCloudWind2,
  FiCloud as FiCloudFog3,
  FiCloud as FiCloudHail3,
  FiCloud as FiCloudSleet3,
  FiCloud as FiCloudSmog3,
  FiCloud as FiCloudSun3,
  FiCloud as FiCloudMoon3,
  FiCloud as FiCloudLightning4,
  FiCloud as FiCloudRain4,
  FiCloud as FiCloudSnow4,
  FiCloud as FiCloudWind3,
  FiCloud as FiCloudFog4,
  FiCloud as FiCloudHail4,
  FiCloud as FiCloudSleet4,
  FiCloud as FiCloudSmog4,
  FiCloud as FiCloudSun4,
  FiCloud as FiCloudMoon4,
  FiCloud as FiCloudLightning5,
  FiCloud as FiCloudRain5,
  FiCloud as FiCloudSnow5,
  FiCloud as FiCloudWind4,
  FiCloud as FiCloudFog5,
  FiCloud as FiCloudHail5,
  FiCloud as FiCloudSleet5,
  FiCloud as FiCloudSmog5,
  FiCloud as FiCloudSun5,
  FiCloud as FiCloudMoon5
} from 'react-icons/fi'

const Sidebar = () => {
  const location = useLocation()
  const { user, logout } = useAuth()

  // Super Admin Navigation - Organized by categories
  const superAdminNavigation = [
    // Dashboard & Overview
    {
      category: 'Dashboard',
      items: [
        { name: 'Super Admin Dashboard', href: '/super-admin/dashboard', icon: FiShield, badge: null }
      ]
    },
    
    // Core Management
    {
      category: 'Core Management',
      items: [
        { name: 'Tenant Management', href: '/super-admin/tenants', icon: FiUsers, badge: null },
        { name: 'User Management', href: '/super-admin/users', icon: FiUserCheck, badge: null },
        { name: 'Subscription Plans', href: '/super-admin/subscription-plans', icon: FiCreditCard, badge: null }
      ]
    },
    
    // Support & Knowledge
    {
      category: 'Support & Knowledge',
      items: [
        { name: 'Knowledgebase Management', href: '/super-admin/knowledgebase', icon: FiBookOpen, badge: null },
        { name: 'Support Tickets', href: '/super-admin/support-tickets', icon: FiMessageSquare, badge: null },
        { name: 'Contact Submissions', href: '/super-admin/contact-submissions', icon: FiMail, badge: null }
      ]
    },
    
    // Analytics & Reports
    {
      category: 'Analytics & Reports',
      items: [
        { name: 'System Analytics', href: '/super-admin/analytics', icon: FiBarChart2, badge: null },
        { name: 'Billing & Payments', href: '/super-admin/billing', icon: FiDollarSign, badge: null },
        { name: 'System Reports', href: '/super-admin/reports', icon: FiFileText, badge: null }
      ]
    },
    
    // System Administration
    {
      category: 'System Administration',
      items: [
        { name: 'API Keys Management', href: '/super-admin/api-keys', icon: FiKey, badge: null },
        { name: 'Security Management', href: '/super-admin/security', icon: FiShield, badge: null },
        { name: 'System Health', href: '/super-admin/system-health', icon: FiActivity, badge: null },
        { name: 'System Logs', href: '/super-admin/system-logs', icon: FiFileText, badge: null },
        { name: 'Database Management', href: '/super-admin/database', icon: FiDatabase, badge: null }
      ]
    },
    
    // Configuration
    {
      category: 'Configuration',
      items: [
        { name: 'Global Settings', href: '/super-admin/settings', icon: FiSettings, badge: null },
        { name: 'System Monitoring', href: '/super-admin/monitoring', icon: FiMonitor, badge: null }
      ]
    }
  ]

  // Regular User Navigation
  const regularUserNavigation = [
    { name: 'Dashboard', href: '/app/dashboard', icon: FiHome },
    { name: 'POS Terminal', href: '/app/pos', icon: FiShoppingCart },
    { name: 'Products', href: '/app/products', icon: FiPackage },
    { name: 'Categories', href: '/app/categories', icon: FiTag },
    { name: 'Locations', href: '/app/locations', icon: FiMapPin },
    { name: 'Sales', href: '/app/sales', icon: FiShoppingCart },
    { name: 'Inventory', href: '/app/inventory', icon: FiClipboard },
    { name: 'Customers', href: '/app/customers', icon: FiUsers },
    { name: 'Reports', href: '/app/reports', icon: FiBarChart2 },
    { name: 'Support', href: '/app/support', icon: FiHelpCircle },
    { name: 'Notifications', href: '/app/notifications', icon: FiBell },
    { name: 'Settings', href: '/app/settings', icon: FiSettings },
  ]

  // Admin Navigation (includes User Management, Discounts, and Coupons)
  const adminNavigation = [
    ...regularUserNavigation.slice(0, -1), // All regular items except Settings
    { name: 'User Management', href: '/app/user-management', icon: FiUserCheck },
    { name: 'Sub-Categories', href: '/app/sub-categories', icon: FiFolder },
    { name: 'Discounts', href: '/app/discounts', icon: FiPercent },
    { name: 'Coupons', href: '/app/coupons', icon: FiGift },
    { name: 'Settings', href: '/app/settings', icon: FiSettings },
  ]

  // Determine which navigation to show based on user role
  let navigation
  let isSuperAdmin = false
  
  if (user?.role === 'super_admin') {
    navigation = superAdminNavigation
    isSuperAdmin = true
  } else if (user?.role === 'admin') {
    navigation = adminNavigation
  } else {
    navigation = regularUserNavigation
  }

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
          {isSuperAdmin && (
            <div className="ml-2 px-2 py-1 bg-purple-100 text-purple-700 text-xs font-medium rounded-full">
              Super Admin
            </div>
          )}
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
        {isSuperAdmin ? (
          // Super Admin Navigation with Categories
          <div className="space-y-6">
            {navigation.map((category) => (
              <div key={category.category}>
                <h3 className="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                  {category.category}
                </h3>
                <div className="space-y-1">
                  {category.items.map((item) => {
                    const isActive = location.pathname === item.href
                    return (
                      <Link
                        key={item.name}
                        to={item.href}
                        className={`group flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 ${
                          isActive
                            ? 'bg-[#e41e5b] text-white shadow-sm'
                            : 'text-[#746354] hover:bg-[#e41e5b]/5 hover:text-[#e41e5b]'
                        }`}
                      >
                        <item.icon
                          className={`mr-3 h-5 w-5 flex-shrink-0 ${
                            isActive ? 'text-white' : 'text-[#746354] group-hover:text-[#e41e5b]'
                          }`}
                        />
                        <span className="truncate flex-1">{item.name}</span>
                        {item.badge && (
                          <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            {item.badge}
                          </span>
                        )}
                      </Link>
                    )
                  })}
                </div>
              </div>
            ))}
          </div>
        ) : (
          // Regular Navigation
          <div className="space-y-1">
            {navigation.map((item) => {
              const isActive = location.pathname === item.href
              return (
                <Link
                  key={item.name}
                  to={item.href}
                  className={`group flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 ${
                    isActive
                      ? 'bg-[#e41e5b] text-white shadow-sm'
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
        )}
      </nav>

      {/* Logout */}
      <div className="px-3 mt-6">
        <button
          onClick={handleLogout}
          className="group flex items-center w-full px-3 py-2.5 text-sm font-medium text-[#746354] rounded-xl hover:bg-red-50 hover:text-red-600 transition-all duration-200"
        >
          <FiLogOut className="mr-3 h-5 w-5 flex-shrink-0 text-[#746354] group-hover:text-red-600" />
          <span>Logout</span>
        </button>
      </div>
    </div>
  )
}

export default Sidebar
