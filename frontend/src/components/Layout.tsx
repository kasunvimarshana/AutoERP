import React from 'react';
import type { ReactNode } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

interface LayoutProps {
  children: ReactNode;
}

const Layout: React.FC<LayoutProps> = ({ children }) => {
  const { user, logout, isAuthenticated } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <div style={{ display: 'flex', minHeight: '100vh', fontFamily: 'system-ui, sans-serif' }}>
      {isAuthenticated && (
        <nav style={{
          width: '220px',
          background: '#1e293b',
          color: 'white',
          padding: '1.5rem 1rem',
          display: 'flex',
          flexDirection: 'column',
          gap: '0.5rem',
        }}>
          <div style={{ marginBottom: '1.5rem' }}>
            <h2 style={{ fontSize: '1.1rem', fontWeight: 700, color: '#38bdf8', margin: 0 }}>
              SaaS Inventory
            </h2>
            <p style={{ fontSize: '0.75rem', color: '#94a3b8', margin: '0.25rem 0 0' }}>
              {user?.tenant?.name || 'Loading...'}
            </p>
          </div>

          {[
            { path: '/dashboard', label: '📊 Dashboard' },
            { path: '/users', label: '👥 Users' },
            { path: '/products', label: '📦 Products' },
            { path: '/inventory', label: '🏭 Inventory' },
            { path: '/orders', label: '🛒 Orders' },
            { path: '/tenant-config', label: '⚙️ Config' },
          ].map(({ path, label }) => (
            <Link
              key={path}
              to={path}
              style={{
                color: '#cbd5e1',
                textDecoration: 'none',
                padding: '0.5rem 0.75rem',
                borderRadius: '0.375rem',
                fontSize: '0.875rem',
              }}
            >
              {label}
            </Link>
          ))}

          <div style={{ marginTop: 'auto', paddingTop: '1rem', borderTop: '1px solid #334155' }}>
            <p style={{ fontSize: '0.75rem', color: '#94a3b8', marginBottom: '0.5rem' }}>
              {user?.name}
            </p>
            <button
              onClick={handleLogout}
              style={{
                background: '#dc2626',
                color: 'white',
                border: 'none',
                padding: '0.375rem 0.75rem',
                borderRadius: '0.375rem',
                cursor: 'pointer',
                fontSize: '0.875rem',
                width: '100%',
              }}
            >
              Logout
            </button>
          </div>
        </nav>
      )}

      <main style={{ flex: 1, padding: isAuthenticated ? '2rem' : '0', background: '#f8fafc', overflowY: 'auto' }}>
        {children}
      </main>
    </div>
  );
};

export default Layout;
