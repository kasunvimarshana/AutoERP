import React from 'react';
import { NavLink } from 'react-router-dom';
import clsx from 'clsx';
import {
  LayoutDashboard,
  Users,
  Package,
  Warehouse,
  ShoppingCart,
  Webhook,
  Settings,
  Activity,
  X,
  Boxes,
} from 'lucide-react';
import { useAuth } from '../../contexts/AuthContext';

interface NavItem {
  path: string;
  label: string;
  icon: React.ElementType;
  permission?: string;
}

const navItems: NavItem[] = [
  { path: '/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { path: '/users', label: 'Users', icon: Users, permission: 'users.view' },
  { path: '/products', label: 'Products', icon: Package, permission: 'products.view' },
  { path: '/inventory', label: 'Inventory', icon: Warehouse, permission: 'inventory.view' },
  { path: '/orders', label: 'Orders', icon: ShoppingCart, permission: 'orders.view' },
  { path: '/webhooks', label: 'Webhooks', icon: Webhook, permission: 'webhooks.view' },
  { path: '/settings', label: 'Settings', icon: Settings },
  { path: '/health', label: 'Health', icon: Activity },
];

interface SidebarProps {
  isOpen: boolean;
  onClose: () => void;
}

const Sidebar: React.FC<SidebarProps> = ({ isOpen, onClose }) => {
  const { hasPermission, user } = useAuth();

  const isVisible = (item: NavItem) => {
    if (!item.permission) return true;
    if (user?.role === 'admin') return true;
    return hasPermission(item.permission);
  };

  return (
    <aside
      className={clsx(
        'fixed lg:static inset-y-0 left-0 z-30 w-64 bg-gray-900 flex flex-col transition-transform duration-300',
        isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      )}
    >
      <div className="flex items-center justify-between h-16 px-5 border-b border-gray-700/50">
        <div className="flex items-center gap-2.5">
          <div className="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center shadow-lg shadow-blue-600/30">
            <Boxes className="h-5 w-5 text-white" />
          </div>
          <span className="text-white font-semibold text-base">KV Inventory</span>
        </div>
        <button
          onClick={onClose}
          className="lg:hidden text-gray-400 hover:text-white p-1 rounded-md transition-colors"
        >
          <X className="h-5 w-5" />
        </button>
      </div>

      <nav className="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        {navItems.filter(isVisible).map((item) => (
          <NavLink
            key={item.path}
            to={item.path}
            onClick={onClose}
            className={({ isActive }) =>
              clsx(
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150',
                isActive
                  ? 'bg-blue-600 text-white shadow-sm'
                  : 'text-gray-400 hover:bg-gray-800 hover:text-white'
              )
            }
          >
            <item.icon className="h-[18px] w-[18px] shrink-0" />
            {item.label}
          </NavLink>
        ))}
      </nav>

      {user && (
        <div className="px-4 py-3 border-t border-gray-700/50">
          <div className="flex items-center gap-3">
            <div className="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-semibold shrink-0">
              {user.name.charAt(0).toUpperCase()}
            </div>
            <div className="min-w-0">
              <p className="text-sm font-medium text-white truncate">{user.name}</p>
              <p className="text-xs text-gray-400 capitalize">{user.role}</p>
            </div>
          </div>
        </div>
      )}
    </aside>
  );
};

export default Sidebar;
