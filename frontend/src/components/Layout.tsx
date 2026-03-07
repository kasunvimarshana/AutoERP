import { Outlet, Link, useLocation } from 'react-router-dom';
import { useAuthStore } from '../store/authStore';
import { authService } from '../services/authService';
import { LayoutDashboard, Package, Boxes, ShoppingCart, LogOut } from 'lucide-react';

const navItems = [
  { path: '/',          label: 'Dashboard', icon: LayoutDashboard },
  { path: '/products',  label: 'Products',  icon: Package },
  { path: '/inventory', label: 'Inventory', icon: Boxes },
  { path: '/orders',    label: 'Orders',    icon: ShoppingCart },
];

/**
 * Main application shell with sidebar navigation.
 */
export default function Layout() {
  const location = useLocation();
  const { user, clearAuth } = useAuthStore();

  const handleLogout = async () => {
    try { await authService.logout(); } catch { /* ignore */ }
    clearAuth();
  };

  return (
    <div style={{ display: 'flex', minHeight: '100vh' }}>
      {/* Sidebar */}
      <aside style={{
        width: 240,
        background: '#1e293b',
        color: '#f8fafc',
        display: 'flex',
        flexDirection: 'column',
        padding: '24px 0',
      }}>
        <div style={{ padding: '0 20px 24px', borderBottom: '1px solid #334155' }}>
          <h1 style={{ fontSize: 18, fontWeight: 700, color: '#60a5fa' }}>
            SaaS Inventory
          </h1>
          <p style={{ fontSize: 12, color: '#94a3b8', marginTop: 4 }}>
            {user?.name ?? 'User'}
          </p>
        </div>

        <nav style={{ flex: 1, padding: '16px 0' }}>
          {navItems.map(({ path, label, icon: Icon }) => {
            const isActive = location.pathname === path;
            return (
              <Link
                key={path}
                to={path}
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: 12,
                  padding: '10px 20px',
                  color: isActive ? '#60a5fa' : '#94a3b8',
                  background: isActive ? 'rgba(96,165,250,0.1)' : 'transparent',
                  borderLeft: isActive ? '3px solid #60a5fa' : '3px solid transparent',
                  textDecoration: 'none',
                  fontSize: 14,
                }}
              >
                <Icon size={18} />
                {label}
              </Link>
            );
          })}
        </nav>

        <button
          onClick={handleLogout}
          style={{
            display: 'flex', alignItems: 'center', gap: 12,
            padding: '10px 20px', background: 'none', border: 'none',
            color: '#94a3b8', cursor: 'pointer', fontSize: 14,
          }}
        >
          <LogOut size={18} />
          Logout
        </button>
      </aside>

      {/* Main content */}
      <main style={{ flex: 1, padding: 24, overflowY: 'auto' }}>
        <Outlet />
      </main>
    </div>
  );
}
