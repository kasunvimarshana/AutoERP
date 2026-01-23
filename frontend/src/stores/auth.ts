import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User, Tenant, LoginCredentials, RegisterData } from '@/types/auth'
import { authService } from '@/services/authService'
import { appConfig } from '@/config/app'

/**
 * Authentication Store
 * Manages user authentication state and operations
 */

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const isAuthenticated = ref(false)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Computed
  const currentUser = computed(() => user.value)
  const userRoles = computed(() => user.value?.roles?.map((r) => r.name) || [])
  const userPermissions = computed(() => user.value?.permissions || [])
  const currentTenant = computed(() => user.value?.tenant)

  // Actions
  async function login(credentials: LoginCredentials) {
    isLoading.value = true
    error.value = null

    try {
      const response = await authService.login(credentials)
      
      if (response.success && response.data) {
        token.value = response.data.token
        user.value = response.data.user
        isAuthenticated.value = true

        // Store token and user
        localStorage.setItem(appConfig.auth.tokenKey, response.data.token)
        localStorage.setItem(appConfig.auth.userKey, JSON.stringify(response.data.user))
        
        // Store tenant ID if available
        if (response.data.user.tenant_id) {
          localStorage.setItem(
            appConfig.tenant.storageKey,
            response.data.user.tenant_id.toString(),
          )
        }

        return response
      }
      throw new Error(response.message || 'Login failed')
    } catch (err: any) {
      error.value = err.message || 'Login failed'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function register(data: RegisterData) {
    isLoading.value = true
    error.value = null

    try {
      const response = await authService.register(data)
      
      if (response.success && response.data) {
        token.value = response.data.token
        user.value = response.data.user
        isAuthenticated.value = true

        // Store token and user
        localStorage.setItem(appConfig.auth.tokenKey, response.data.token)
        localStorage.setItem(appConfig.auth.userKey, JSON.stringify(response.data.user))
        
        return response
      }
      throw new Error(response.message || 'Registration failed')
    } catch (err: any) {
      error.value = err.message || 'Registration failed'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function logout() {
    isLoading.value = true
    error.value = null

    try {
      await authService.logout()
    } catch (err: any) {
      console.error('Logout error:', err)
    } finally {
      // Clear state regardless of API call result
      user.value = null
      token.value = null
      isAuthenticated.value = false
      
      localStorage.removeItem(appConfig.auth.tokenKey)
      localStorage.removeItem(appConfig.auth.userKey)
      localStorage.removeItem(appConfig.tenant.storageKey)
      
      isLoading.value = false
    }
  }

  async function fetchUser() {
    isLoading.value = true
    error.value = null

    try {
      const userData = await authService.me()
      user.value = userData
      isAuthenticated.value = true
      
      // Update stored user data
      localStorage.setItem(appConfig.auth.userKey, JSON.stringify(userData))
      
      return userData
    } catch (err: any) {
      error.value = err.message || 'Failed to fetch user'
      // If fetching user fails, likely token is invalid
      await logout()
      throw err
    } finally {
      isLoading.value = false
    }
  }

  function initializeAuth() {
    // Check for stored token and user
    const storedToken = localStorage.getItem(appConfig.auth.tokenKey)
    const storedUser = localStorage.getItem(appConfig.auth.userKey)

    if (storedToken && storedUser) {
      try {
        token.value = storedToken
        user.value = JSON.parse(storedUser)
        isAuthenticated.value = true
        
        // Optionally fetch fresh user data
        fetchUser().catch(() => {
          // If fetch fails, token might be expired
          console.warn('Failed to refresh user data')
        })
      } catch (err) {
        console.error('Failed to parse stored user data', err)
        logout()
      }
    }
  }

  function hasRole(role: string): boolean {
    return userRoles.value.includes(role)
  }

  function hasAnyRole(roles: string[]): boolean {
    return roles.some((role) => hasRole(role))
  }

  function hasPermission(permission: string): boolean {
    return userPermissions.value.includes(permission)
  }

  function hasAnyPermission(permissions: string[]): boolean {
    return permissions.some((permission) => hasPermission(permission))
  }

  function hasAllPermissions(permissions: string[]): boolean {
    return permissions.every((permission) => hasPermission(permission))
  }

  return {
    // State
    user,
    token,
    isAuthenticated,
    isLoading,
    error,

    // Computed
    currentUser,
    userRoles,
    userPermissions,
    currentTenant,

    // Actions
    login,
    register,
    logout,
    fetchUser,
    initializeAuth,
    hasRole,
    hasAnyRole,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
  }
})
