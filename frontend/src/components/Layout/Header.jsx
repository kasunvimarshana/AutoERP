import { useNavigate, useLocation } from 'react-router-dom'
import { Menu, LogOut, Building2 } from 'lucide-react'
import useAuthStore from '../../store/authStore'

const routeLabels = {
  '/dashboard': 'Dashboard',
  '/users':     'Users',
  '/products':  'Products',
  '/inventory': 'Inventory',
  '/orders':    'Orders',
  '/health':    'Health',
}

function getPageTitle(pathname) {
  const exact = routeLabels[pathname]
  if (exact) return exact
  const match = Object.entries(routeLabels).find(([key]) =>
    pathname.startsWith(key)
  )
  return match ? match[1] : 'Dashboard'
}

function UserAvatar({ user }) {
  const initials = user
    ? [user.firstName, user.lastName, user.name, user.email]
        .filter(Boolean)
        .map((s) => s[0].toUpperCase())
        .slice(0, 2)
        .join('')
    : '??'

  return (
    <div className="flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-sm font-semibold select-none">
      {initials}
    </div>
  )
}

export default function Header({ onMenuToggle }) {
  const navigate = useNavigate()
  const { pathname } = useLocation()
  const { user, tenantId, logout } = useAuthStore()

  function handleLogout() {
    logout()
    navigate('/login', { replace: true })
  }

  const pageTitle = getPageTitle(pathname)
  const displayName =
    user?.firstName
      ? `${user.firstName} ${user.lastName ?? ''}`.trim()
      : user?.name ?? user?.email ?? 'User'

  return (
    <header className="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 gap-4 shrink-0 shadow-sm">
      {/* Left: hamburger + page title */}
      <div className="flex items-center gap-3 min-w-0">
        <button
          onClick={onMenuToggle}
          aria-label="Toggle sidebar"
          className="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors"
        >
          <Menu className="w-5 h-5" />
        </button>
        <h1 className="text-lg font-semibold text-gray-900 truncate">{pageTitle}</h1>
      </div>

      {/* Right: tenant badge + user + logout */}
      <div className="flex items-center gap-3 shrink-0">
        {/* Tenant badge */}
        {tenantId && (
          <div className="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-indigo-50 border border-indigo-200 text-xs font-medium text-indigo-700 max-w-[160px] truncate">
            <Building2 className="w-3.5 h-3.5 shrink-0" />
            <span className="truncate">{tenantId}</span>
          </div>
        )}

        {/* User info */}
        <div className="flex items-center gap-2">
          <UserAvatar user={user} />
          <span className="hidden md:block text-sm font-medium text-gray-700 max-w-[120px] truncate">
            {displayName}
          </span>
        </div>

        {/* Logout */}
        <button
          onClick={handleLogout}
          aria-label="Logout"
          title="Logout"
          className="flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-500 rounded-lg hover:bg-red-50 hover:text-red-600 transition-colors"
        >
          <LogOut className="w-4 h-4" />
          <span className="hidden sm:inline">Logout</span>
        </button>
      </div>
    </header>
  )
}
