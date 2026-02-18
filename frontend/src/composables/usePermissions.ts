import { ref, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

export function usePermissions() {
  const authStore = useAuthStore();

  const hasPermission = (permission: string): boolean => {
    return authStore.hasPermission(permission);
  };

  const hasAnyPermission = (permissions: string[]): boolean => {
    return authStore.hasAnyPermission(permissions);
  };

  const hasAllPermissions = (permissions: string[]): boolean => {
    return authStore.hasAllPermissions(permissions);
  };

  const hasRole = (role: string): boolean => {
    return authStore.hasRole(role);
  };

  const canView = computed(() => (module: string) => {
    return hasPermission(`${module}.view`) || hasPermission('*');
  });

  const canCreate = computed(() => (module: string) => {
    return hasPermission(`${module}.create`) || hasPermission('*');
  });

  const canUpdate = computed(() => (module: string) => {
    return hasPermission(`${module}.update`) || hasPermission('*');
  });

  const canDelete = computed(() => (module: string) => {
    return hasPermission(`${module}.delete`) || hasPermission('*');
  });

  return {
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    canView,
    canCreate,
    canUpdate,
    canDelete,
  };
}
