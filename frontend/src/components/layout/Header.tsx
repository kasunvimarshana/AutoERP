import React from 'react';
import { Menu, Bell, LogOut } from 'lucide-react';
import { useAuth } from '../../contexts/AuthContext';
import { useTenant } from '../../contexts/TenantContext';

interface HeaderProps {
  onMenuClick: () => void;
}

const Header: React.FC<HeaderProps> = ({ onMenuClick }) => {
  const { user, logout } = useAuth();
  const { currentTenant } = useTenant();

  return (
    <header className="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 lg:px-6 shrink-0">
      <div className="flex items-center gap-3">
        <button
          onClick={onMenuClick}
          className="lg:hidden p-2 rounded-md text-gray-500 hover:bg-gray-100 transition-colors"
        >
          <Menu className="h-5 w-5" />
        </button>
        {currentTenant && (
          <div className="hidden lg:flex items-center gap-2">
            <span className="text-xs text-gray-400">Tenant:</span>
            <span className="text-sm font-medium text-gray-700 bg-gray-100 px-2.5 py-1 rounded-md">
              {currentTenant.name}
            </span>
            <span className="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full capitalize">
              {currentTenant.plan}
            </span>
          </div>
        )}
      </div>

      <div className="flex items-center gap-2">
        <button className="relative p-2 rounded-md text-gray-500 hover:bg-gray-100 transition-colors">
          <Bell className="h-5 w-5" />
          <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full" />
        </button>

        <div className="flex items-center gap-2 pl-2 border-l border-gray-200">
          <div className="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-semibold shrink-0">
            {user?.name?.charAt(0).toUpperCase() || 'U'}
          </div>
          <div className="hidden md:block">
            <p className="text-sm font-medium text-gray-700 leading-tight">{user?.name}</p>
            <p className="text-xs text-gray-400 capitalize leading-tight">{user?.role}</p>
          </div>
          <button
            onClick={logout}
            className="ml-1 p-2 rounded-md text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors"
            title="Logout"
          >
            <LogOut className="h-4 w-4" />
          </button>
        </div>
      </div>
    </header>
  );
};

export default Header;
