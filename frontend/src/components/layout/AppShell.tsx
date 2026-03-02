import type { ReactNode } from 'react'
import { useAuthStore } from '@/store/authStore'
import authApi from '@/api/auth'

interface AppShellProps {
  children: ReactNode
}

const NAV_ITEMS = [
  { label: 'Dashboard', href: '/' },
  { label: 'Product', href: '/product' },
  { label: 'Organisation', href: '/organisation' },
  { label: 'Metadata', href: '/metadata' },
  { label: 'Workflow', href: '/workflow' },
  { label: 'Inventory', href: '/inventory' },
  { label: 'Sales', href: '/sales' },
  { label: 'POS', href: '/pos' },
  { label: 'Procurement', href: '/procurement' },
  { label: 'CRM', href: '/crm' },
  { label: 'Warehouse', href: '/warehouse' },
  { label: 'Accounting', href: '/accounting' },
  { label: 'Reporting', href: '/reporting' },
  { label: 'Notification', href: '/notification' },
  { label: 'Integration', href: '/integration' },
  { label: 'Plugin', href: '/plugin' },
  { label: 'Tenancy', href: '/tenancy' },
  { label: 'Pricing', href: '/pricing' },
]

/**
 * AppShell — persistent application frame: top nav + sidebar + main content area.
 *
 * Renders navigation items derived from the module list.
 * Logout clears the JWT and redirects to /login.
 */
export default function AppShell({ children }: AppShellProps) {
  const { user, logout } = useAuthStore()

  const handleLogout = async () => {
    try {
      await authApi.logout()
    } finally {
      logout()
      window.location.href = '/login'
    }
  }

  return (
    <div className="app-shell">
      <header className="app-header">
        <span className="app-logo">KV Enterprise ERP/CRM</span>
        <div className="app-header-actions">
          {user && (
            <span className="app-user">{user.name} ({user.email})</span>
          )}
          <button onClick={handleLogout} type="button">Logout</button>
        </div>
      </header>

      <div className="app-body">
        <nav className="app-sidebar" aria-label="Main navigation">
          <ul>
            {NAV_ITEMS.map((item) => (
              <li key={item.href}>
                <a href={item.href}>{item.label}</a>
              </li>
            ))}
          </ul>
        </nav>

        <main className="app-main" id="main-content">
          {children}
        </main>
      </div>
    </div>
  )
}
