import React from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import {
  LayoutDashboard,
  Package,
  ShoppingCart,
  Building2,
  Activity,
  X,
  BoxSelect,
} from 'lucide-react';
import clsx from 'clsx';
import { usePermissions } from '@/hooks/usePermissions';

interface SidebarProps {
  open: boolean;
  onClose: () => void;
}

interface NavItem {
  to: string;
  label: string;
  icon: React.ReactNode;
  permission?: () => boolean;
}

const Sidebar: React.FC<SidebarProps> = ({ open, onClose }) => {
  const location = useLocation();
  const perms = usePermissions();

  const navItems: NavItem[] = [
    { to: '/dashboard', label: 'Dashboard', icon: <LayoutDashboard size={18} /> },
    {
      to: '/inventory',
      label: 'Inventory',
      icon: <Package size={18} />,
      permission: () => perms.canViewProducts,
    },
    {
      to: '/orders',
      label: 'Orders',
      icon: <ShoppingCart size={18} />,
      permission: () => perms.canViewOrders,
    },
    {
      to: '/tenants',
      label: 'Tenants',
      icon: <Building2 size={18} />,
      permission: () => perms.canViewTenants,
    },
    { to: '/health', label: 'System Health', icon: <Activity size={18} /> },
  ];

  const visibleItems = navItems.filter((item) => !item.permission || item.permission());

  return (
    <>
      {/* Mobile overlay */}
      {open && (
        <div
          className="fixed inset-0 bg-black/50 z-20 lg:hidden"
          onClick={onClose}
        />
      )}

      <aside
        className={clsx(
          'fixed lg:relative inset-y-0 left-0 z-30 w-64 bg-gray-900 text-white flex flex-col transition-transform duration-300 ease-in-out',
          open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0 lg:w-0 lg:overflow-hidden',
        )}
      >
        {/* Logo */}
        <div className="flex items-center justify-between p-4 border-b border-gray-700">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 bg-primary-500 rounded-lg flex items-center justify-center">
              <BoxSelect size={18} className="text-white" />
            </div>
            <span className="font-bold text-lg tracking-tight">InvenSys</span>
          </div>
          <button
            onClick={onClose}
            className="p-1 rounded-lg hover:bg-gray-700 transition-colors lg:hidden"
          >
            <X size={18} />
          </button>
        </div>

        {/* Navigation */}
        <nav className="flex-1 p-3 space-y-0.5 overflow-y-auto">
          {visibleItems.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              className={({ isActive }) =>
                clsx(
                  'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                  isActive || location.pathname.startsWith(item.to)
                    ? 'bg-primary-600 text-white'
                    : 'text-gray-400 hover:bg-gray-800 hover:text-white',
                )
              }
            >
              {item.icon}
              {item.label}
            </NavLink>
          ))}
        </nav>

        {/* Footer */}
        <div className="p-4 border-t border-gray-700 text-xs text-gray-500">
          <p>© 2024 InvenSys</p>
          <p className="mt-0.5">v1.0.0</p>
        </div>
      </aside>
    </>
  );
};

export default Sidebar;
