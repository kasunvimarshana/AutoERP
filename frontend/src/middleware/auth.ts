import type { NavigationGuardNext, RouteLocationNormalized } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

/**
 * Authentication Guard
 * Protects routes that require authentication
 */

export function authGuard(
  to: RouteLocationNormalized,
  from: RouteLocationNormalized,
  next: NavigationGuardNext,
) {
  const authStore = useAuthStore()

  if (!authStore.isAuthenticated) {
    // Redirect to login with return URL
    next({
      name: 'login',
      query: { redirect: to.fullPath },
    })
  } else {
    next()
  }
}

/**
 * Guest Guard
 * Redirects authenticated users away from auth pages
 */

export function guestGuard(
  to: RouteLocationNormalized,
  from: RouteLocationNormalized,
  next: NavigationGuardNext,
) {
  const authStore = useAuthStore()

  if (authStore.isAuthenticated) {
    // Redirect authenticated users to dashboard
    next({ name: 'dashboard' })
  } else {
    next()
  }
}

/**
 * Role Guard Factory
 * Creates a guard that checks for specific roles
 */

export function roleGuard(allowedRoles: string[]) {
  return (
    to: RouteLocationNormalized,
    from: RouteLocationNormalized,
    next: NavigationGuardNext,
  ) => {
    const authStore = useAuthStore()

    if (!authStore.isAuthenticated) {
      next({
        name: 'login',
        query: { redirect: to.fullPath },
      })
      return
    }

    const hasRole = authStore.hasAnyRole(allowedRoles)
    
    if (hasRole) {
      next()
    } else {
      // Redirect to unauthorized page
      next({
        name: 'unauthorized',
        query: { from: to.fullPath },
      })
    }
  }
}

/**
 * Permission Guard Factory
 * Creates a guard that checks for specific permissions
 */

export function permissionGuard(requiredPermissions: string[], requireAll = false) {
  return (
    to: RouteLocationNormalized,
    from: RouteLocationNormalized,
    next: NavigationGuardNext,
  ) => {
    const authStore = useAuthStore()

    if (!authStore.isAuthenticated) {
      next({
        name: 'login',
        query: { redirect: to.fullPath },
      })
      return
    }

    const hasPermission = requireAll
      ? authStore.hasAllPermissions(requiredPermissions)
      : authStore.hasAnyPermission(requiredPermissions)
    
    if (hasPermission) {
      next()
    } else {
      // Redirect to unauthorized page
      next({
        name: 'unauthorized',
        query: { from: to.fullPath },
      })
    }
  }
}
