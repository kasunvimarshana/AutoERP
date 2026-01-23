import { useAuthStore } from '@/stores/auth'

/**
 * useAuth Composable
 * Provides easy access to authentication state and methods
 */

export function useAuth() {
  const authStore = useAuthStore()

  return {
    user: authStore.currentUser,
    isAuthenticated: authStore.isAuthenticated,
    isLoading: authStore.isLoading,
    error: authStore.error,
    roles: authStore.userRoles,
    permissions: authStore.userPermissions,
    tenant: authStore.currentTenant,
    
    login: authStore.login,
    register: authStore.register,
    logout: authStore.logout,
    fetchUser: authStore.fetchUser,
    hasRole: authStore.hasRole,
    hasAnyRole: authStore.hasAnyRole,
    hasPermission: authStore.hasPermission,
    hasAnyPermission: authStore.hasAnyPermission,
    hasAllPermissions: authStore.hasAllPermissions,
  }
}
