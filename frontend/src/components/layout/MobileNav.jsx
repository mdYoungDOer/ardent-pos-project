import { Link, useLocation } from 'react-router-dom'
import { useAuthStore } from '../../stores/authStore'
import { clsx } from 'clsx'
import {
  HiHome,
  HiCube,
  HiClipboardList,
  HiCreditCard,
  HiUsers
} from 'react-icons/hi'

const MobileNav = () => {
  const location = useLocation()
  const { canAccess } = useAuthStore()

  const navigation = [
    { name: 'Dashboard', href: '/app/dashboard', icon: HiHome, access: 'dashboard' },
    { name: 'Products', href: '/app/products', icon: HiCube, access: 'products' },
    { name: 'Sales', href: '/app/sales', icon: HiCreditCard, access: 'sales' },
    { name: 'Inventory', href: '/app/inventory', icon: HiClipboardList, access: 'inventory' },
    { name: 'Customers', href: '/app/customers', icon: HiUsers, access: 'customers' },
  ]

  return (
    <div className="mobile-nav safe-area-bottom">
      <div className="flex justify-around">
        {navigation.map((item) => {
          if (!canAccess(item.access)) return null
          
          const isActive = location.pathname === item.href
          const Icon = item.icon

          return (
            <Link
              key={item.name}
              to={item.href}
              className={clsx(
                'mobile-nav-item',
                isActive && 'active'
              )}
            >
              <Icon className="h-6 w-6 mb-1" />
              <span>{item.name}</span>
            </Link>
          )
        })}
      </div>
    </div>
  )
}

export default MobileNav
