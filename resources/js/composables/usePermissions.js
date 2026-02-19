import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

export function usePermissions() {
  const authStore = useAuthStore();

  const hasPermission = computed(() => (permission) => {
    return authStore.hasPermission(permission);
  });

  const hasAnyPermission = computed(() => (permissions) => {
    return authStore.hasAnyPermission(permissions);
  });

  const hasAllPermissions = computed(() => (permissions) => {
    return authStore.hasAllPermissions(permissions);
  });

  const hasRole = computed(() => (role) => {
    return authStore.hasRole(role);
  });

  const canView = computed(() => (resource) => {
    return hasPermission.value(`${resource}.view`);
  });

  const canCreate = computed(() => (resource) => {
    return hasPermission.value(`${resource}.create`);
  });

  const canUpdate = computed(() => (resource) => {
    return hasPermission.value(`${resource}.update`);
  });

  const canDelete = computed(() => (resource) => {
    return hasPermission.value(`${resource}.delete`);
  });

  const canManage = computed(() => (resource) => {
    return hasAllPermissions.value([
      `${resource}.view`,
      `${resource}.create`,
      `${resource}.update`,
      `${resource}.delete`,
    ]);
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
    canManage,
  };
}
