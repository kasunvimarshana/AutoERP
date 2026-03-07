import { useState } from 'react';
import { NavLink } from 'react-router-dom';
import { LayoutDashboard, Package, Warehouse, ShoppingCart, Users, ChevronLeft, ChevronRight, Box } from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';

const navItems = [
  { to: '/', icon: LayoutDashboard, label: 'Dashboard', end: true },
  { to: '/products', icon: Package, label: 'Products' },
  { to: '/inventory', icon: Warehouse, label: 'Inventory' },
  { to: '/orders', icon: ShoppingCart, label: 'Orders' },
];

export default function Sidebar() {
  const [collapsed, setCollapsed] = useState(false);
  const { isAdmin } = useAuth();

  const links = isAdmin() ? [...navItems, { to: '/users', icon: Users, label: 'Users' }] : navItems;

  return (
    <div className={`bg-gray-900 text-white flex flex-col transition-all duration-300 ${collapsed ? 'w-16' : 'w-64'}`}>
      <div className="flex items-center justify-between p-4 border-b border-gray-700">
        {!collapsed && (
          <div className="flex items-center gap-2">
            <Box className="text-indigo-400" size={22} />
            <span className="font-bold text-lg text-white">InvenTrack</span>
          </div>
        )}
        {collapsed && <Box className="text-indigo-400 mx-auto" size={22} />}
        <button onClick={() => setCollapsed(!collapsed)} className="text-gray-400 hover:text-white ml-auto">
          {collapsed ? <ChevronRight size={18} /> : <ChevronLeft size={18} />}
        </button>
      </div>
      <nav className="flex-1 py-4 space-y-1 px-2">
        {links.map(({ to, icon: Icon, label, end }) => (
          <NavLink
            key={to}
            to={to}
            end={end}
            className={({ isActive }) =>
              `flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors text-sm font-medium ${
                isActive ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'
              }`
            }
          >
            <Icon size={18} className="shrink-0" />
            {!collapsed && <span>{label}</span>}
          </NavLink>
        ))}
      </nav>
    </div>
  );
}
