import { useAuth } from './useAuth';

export const usePermissions = () => {
  const { hasRole, hasPermission, hasAnyRole, hasAnyPermission, user } = useAuth();

  return {
    user,
    hasRole,
    hasPermission,
    hasAnyRole,
    hasAnyPermission,
    isSuperAdmin: hasRole('super-admin'),
    isTenantAdmin: hasRole('tenant-admin'),
    isManager: hasAnyRole(['super-admin', 'tenant-admin', 'manager']),
    canManageProducts:
      hasAnyPermission(['products.create', 'products.update', 'products.delete']) ||
      hasRole('super-admin'),
    canViewProducts:
      hasPermission('products.view') || hasRole('super-admin'),
    canManageOrders:
      hasAnyPermission(['orders.create', 'orders.update', 'orders.delete']) ||
      hasRole('super-admin'),
    canViewOrders:
      hasPermission('orders.view') || hasRole('super-admin'),
    canManageTenants:
      hasPermission('tenants.manage') || hasRole('super-admin'),
    canViewTenants:
      hasAnyPermission(['tenants.view', 'tenants.manage']) || hasRole('super-admin'),
  };
};
