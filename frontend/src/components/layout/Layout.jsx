import React from 'react';
import { Outlet, NavLink, useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { useTenant } from '../../contexts/TenantContext';

const navItems = [
  { path: '/', label: '📊 Dashboard', exact: true },
  { path: '/products', label: '📦 Products' },
  { path: '/inventory', label: '🏭 Inventory' },
  { path: '/orders', label: '🛒 Orders' },
  { path: '/users', label: '👥 Users' },
];

export default function Layout() {
  const { user, logout, hasAnyRole } = useAuth();
  const { tenantName } = useTenant();

  return (
    <div style={styles.container}>
      <aside style={styles.sidebar}>
        <div style={styles.logo}>
          <h2 style={styles.logoText}>🏢 Inventory SaaS</h2>
          {tenantName && <p style={styles.tenantBadge}>Tenant: {tenantName}</p>}
        </div>
        <nav>
          {navItems.map(({ path, label, exact }) => (
            <NavLink
              key={path}
              to={path}
              end={exact}
              style={({ isActive }) => ({ ...styles.navLink, ...(isActive ? styles.navLinkActive : {}) })}
            >
              {label}
            </NavLink>
          ))}
        </nav>
      </aside>
      <div style={styles.main}>
        <header style={styles.header}>
          <div style={styles.headerLeft} />
          <div style={styles.headerRight}>
            <span style={styles.userInfo}>
              {user?.firstName} {user?.lastName} ({user?.roles?.join(', ')})
            </span>
            <button onClick={logout} style={styles.logoutBtn}>Logout</button>
          </div>
        </header>
        <main style={styles.content}>
          <Outlet />
        </main>
      </div>
    </div>
  );
}

const styles = {
  container: { display: 'flex', minHeight: '100vh', fontFamily: 'system-ui, sans-serif' },
  sidebar: { width: 240, background: '#1e293b', color: '#fff', display: 'flex', flexDirection: 'column' },
  logo: { padding: '20px 16px', borderBottom: '1px solid #334155' },
  logoText: { margin: 0, fontSize: 18, color: '#f8fafc' },
  tenantBadge: { margin: '4px 0 0', fontSize: 12, color: '#94a3b8', background: '#334155', borderRadius: 4, padding: '2px 8px', display: 'inline-block' },
  navLink: { display: 'block', padding: '12px 20px', color: '#94a3b8', textDecoration: 'none', fontSize: 14, borderLeft: '3px solid transparent', transition: 'all 0.2s' },
  navLinkActive: { color: '#f8fafc', background: '#334155', borderLeftColor: '#6366f1' },
  main: { flex: 1, display: 'flex', flexDirection: 'column', background: '#f1f5f9' },
  header: { background: '#fff', padding: '0 24px', height: 60, display: 'flex', alignItems: 'center', justifyContent: 'space-between', boxShadow: '0 1px 3px rgba(0,0,0,0.1)' },
  headerLeft: {},
  headerRight: { display: 'flex', alignItems: 'center', gap: 16 },
  userInfo: { fontSize: 14, color: '#64748b' },
  logoutBtn: { background: '#6366f1', color: '#fff', border: 'none', padding: '8px 16px', borderRadius: 6, cursor: 'pointer', fontSize: 14 },
  content: { flex: 1, padding: 24, overflow: 'auto' },
};
