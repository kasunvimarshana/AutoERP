import { useState } from 'react';
import { useLocation } from 'react-router-dom';
import { Bell, ChevronDown, User, LogOut } from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';

const routeTitles = {
  '/': 'Dashboard',
  '/products': 'Products',
  '/inventory': 'Inventory',
  '/orders': 'Orders',
  '/users': 'Users',
  '/profile': 'Profile',
};

export default function Header() {
  const { pathname } = useLocation();
  const { username, email, logout } = useAuth();
  const [open, setOpen] = useState(false);

  const title = Object.entries(routeTitles).find(([k]) => pathname === k || pathname.startsWith(k + '/'))?.[1] || 'InvenTrack';

  return (
    <header className="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
      <h1 className="text-xl font-semibold text-gray-800">{title}</h1>
      <div className="flex items-center gap-4">
        <button className="relative text-gray-500 hover:text-gray-700">
          <Bell size={20} />
          <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">3</span>
        </button>
        <div className="relative">
          <button onClick={() => setOpen(!open)} className="flex items-center gap-2 hover:bg-gray-100 rounded-lg px-3 py-2 transition-colors">
            <div className="w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
              {username?.[0]?.toUpperCase() || 'U'}
            </div>
            <div className="text-left hidden sm:block">
              <p className="text-sm font-medium text-gray-700">{username}</p>
              <p className="text-xs text-gray-500">{email}</p>
            </div>
            <ChevronDown size={16} className="text-gray-400" />
          </button>
          {open && (
            <div className="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-100 z-50 py-1">
              <a href="/profile" onClick={() => setOpen(false)} className="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                <User size={15} /> Profile
              </a>
              <button onClick={logout} className="flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-gray-50 w-full">
                <LogOut size={15} /> Logout
              </button>
            </div>
          )}
        </div>
      </div>
    </header>
  );
}
