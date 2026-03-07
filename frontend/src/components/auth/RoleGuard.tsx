import React from 'react';
import { useAuth } from '@/hooks/useAuth';
import { ShieldOff } from 'lucide-react';

interface RoleGuardProps {
  children: React.ReactNode;
  roles?: string[];
  permissions?: string[];
  requireAll?: boolean;
  fallback?: React.ReactNode;
}

const DefaultFallback = () => (
  <div className="flex flex-col items-center justify-center py-16 text-center">
    <ShieldOff className="w-12 h-12 text-gray-300 mb-4" />
    <h3 className="text-base font-semibold text-gray-600 mb-1">Access Restricted</h3>
    <p className="text-sm text-gray-400">You don't have permission to view this content.</p>
  </div>
);

const RoleGuard: React.FC<RoleGuardProps> = ({
  children,
  roles = [],
  permissions = [],
  requireAll = false,
  fallback = <DefaultFallback />,
}) => {
  const { hasAnyRole, hasAnyPermission, hasRole, hasPermission } = useAuth();

  const checkRoles =
    roles.length === 0 ||
    (requireAll ? roles.every((r) => hasRole(r)) : hasAnyRole(roles));

  const checkPermissions =
    permissions.length === 0 ||
    (requireAll ? permissions.every((p) => hasPermission(p)) : hasAnyPermission(permissions));

  const allowed = checkRoles && checkPermissions;

  return <>{allowed ? children : fallback}</>;
};

export default RoleGuard;
