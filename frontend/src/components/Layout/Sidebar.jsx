import { NavLink } from 'react-router-dom'
import {
  LayoutDashboard,
  Users,
  Package,
  Warehouse,
  ShoppingCart,
  Activity,
  BoxesIcon,
  X,
} from 'lucide-react'

const navItems = [
  { to: '/dashboard', label: 'Dashboard', Icon: LayoutDashboard },
  { to: '/users',     label: 'Users',      Icon: Users },
  { to: '/products',  label: 'Products',   Icon: Package },
  { to: '/inventory', label: 'Inventory',  Icon: Warehouse },
  { to: '/orders',    label: 'Orders',     Icon: ShoppingCart },
  { to: '/health',    label: 'Health',     Icon: Activity },
]

export default function Sidebar({ mobileOpen, onClose }) {
  const linkBase =
    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150'
  const activeClass = 'bg-blue-600 text-white'
  const inactiveClass = 'text-gray-300 hover:bg-gray-800 hover:text-white'

  return (
    <>
      {/* Mobile overlay backdrop */}
      {mobileOpen && (
        <div
          className="fixed inset-0 z-30 bg-black/50 lg:hidden"
          onClick={onClose}
          aria-hidden="true"
        />
      )}

      {/* Sidebar panel */}
      <aside
        className={[
          'fixed top-0 left-0 z-40 h-full w-64 bg-gray-900 flex flex-col',
          'transform transition-transform duration-200 ease-in-out',
          'lg:translate-x-0 lg:static lg:z-auto',
          mobileOpen ? 'translate-x-0' : '-translate-x-full',
        ].join(' ')}
      >
        {/* Logo / App name */}
        <div className="flex items-center justify-between h-16 px-4 border-b border-gray-700 shrink-0">
          <div className="flex items-center gap-2">
            <div className="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-600">
              <BoxesIcon className="w-5 h-5 text-white" />
            </div>
            <span className="text-white font-semibold text-base tracking-tight">
              SaaS Inventory
            </span>
          </div>
          {/* Mobile close button */}
          <button
            onClick={onClose}
            aria-label="Close sidebar"
            className="lg:hidden p-1 rounded text-gray-400 hover:text-white hover:bg-gray-800 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Navigation */}
        <nav className="flex-1 overflow-y-auto px-3 py-4 space-y-1">
          {navItems.map(({ to, label, Icon }) => (
            <NavLink
              key={to}
              to={to}
              onClick={onClose}
              className={({ isActive }) =>
                [linkBase, isActive ? activeClass : inactiveClass].join(' ')
              }
            >
              <Icon className="w-5 h-5 shrink-0" />
              <span>{label}</span>
            </NavLink>
          ))}
        </nav>

        {/* Bottom version tag */}
        <div className="px-4 py-3 border-t border-gray-700 shrink-0">
          <p className="text-xs text-gray-500">Multi-Tenant SaaS v1.0</p>
        </div>
      </aside>
    </>
  )
}
