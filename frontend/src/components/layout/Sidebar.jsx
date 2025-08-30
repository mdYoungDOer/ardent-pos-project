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
  FiShield,
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
  FiAward,
  FiTrendingUp,
  FiTrendingDown,
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
  FiRadio,
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

  // Regular client navigation
  const navigation = [
    { name: 'Dashboard', href: '/dashboard', icon: FiHome },
    { name: 'Products', href: '/products', icon: FiPackage },
    { name: 'Sales', href: '/sales', icon: FiShoppingCart },
    { name: 'Customers', href: '/customers', icon: FiUsers },
    { name: 'Analytics', href: '/analytics', icon: FiBarChart2 },
    { name: 'Reports', href: '/reports', icon: FiClipboard },
    { name: 'Settings', href: '/settings', icon: FiSettings },
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
