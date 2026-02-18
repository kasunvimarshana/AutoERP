import { computed } from 'vue';
import { useAuthStore } from '../stores/authStore';

/**
 * Composable for checking user permissions and roles
 */
export function usePermissions() {
  const authStore = useAuthStore();

  /**
   * Check if user has a specific permission
   */
  const hasPermission = computed(() => (permission) => {
    if (!authStore.user) return false;
    
    // Super admin has all permissions
    if (authStore.user.roles?.some(role => role.slug === 'super-admin')) {
      return true;
    }

    // Check if user has the permission through any role
    return authStore.hasPermission(permission);
  });

  /**
   * Check if user has any of the specified permissions
   */
  const hasAnyPermission = computed(() => (permissions) => {
    if (!Array.isArray(permissions)) return false;
    return permissions.some(permission => hasPermission.value(permission));
  });

  /**
   * Check if user has all specified permissions
   */
  const hasAllPermissions = computed(() => (permissions) => {
    if (!Array.isArray(permissions)) return false;
    return permissions.every(permission => hasPermission.value(permission));
  });

  /**
   * Check if user has a specific role
   */
  const hasRole = computed(() => (role) => {
    if (!authStore.user) return false;
    return authStore.hasRole(role);
  });

  /**
   * Check if user has any of the specified roles
   */
  const hasAnyRole = computed(() => (roles) => {
    if (!Array.isArray(roles)) return false;
    return roles.some(role => hasRole.value(role));
  });

  /**
   * Check if user has all specified roles
   */
  const hasAllRoles = computed(() => (roles) => {
    if (!Array.isArray(roles)) return false;
    return roles.every(role => hasRole.value(role));
  });

  /**
   * Check if user is super admin
   */
  const isSuperAdmin = computed(() => {
    return hasRole.value('super-admin');
  });

  /**
   * Get all user permissions
   */
  const userPermissions = computed(() => {
    if (!authStore.user || !authStore.user.roles) return [];
    
    const permissions = new Set();
    authStore.user.roles.forEach(role => {
      if (role.permissions) {
        role.permissions.forEach(permission => {
          permissions.add(permission.slug);
        });
      }
    });
    
    return Array.from(permissions);
  });

  /**
   * Get all user roles
   */
  const userRoles = computed(() => {
    if (!authStore.user || !authStore.user.roles) return [];
    return authStore.user.roles.map(role => role.slug);
  });

  /**
   * Check if user can perform action on entity
   * @param {string} entity - Entity name (e.g., 'products')
   * @param {string} action - Action name (e.g., 'view', 'create', 'edit', 'delete')
   */
  const can = computed(() => (entity, action) => {
    const permission = `${entity}.${action}`;
    return hasPermission.value(permission);
  });

  return {
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
    hasAllRoles,
    isSuperAdmin,
    userPermissions,
    userRoles,
    can,
  };
}
