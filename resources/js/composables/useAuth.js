import { ref, computed } from 'vue'

/**
 * Authentication Composable
 * 
 * Provides stateless JWT authentication functionality for Vue components.
 * Uses localStorage for token persistence and axios for API calls.
 */

const TOKEN_KEY = 'auth_token'
const REFRESH_TOKEN_KEY = 'refresh_token'
const USER_KEY = 'auth_user'
const TENANT_KEY = 'tenant_id'

// Reactive state
const user = ref(null)
const token = ref(null)
const refreshToken = ref(null)
const tenantId = ref(null)
const isLoading = ref(false)
const error = ref(null)

// Computed properties
const isAuthenticated = computed(() => !!token.value && !!user.value)

/**
 * Initialize auth state from localStorage
 */
const initAuth = () => {
  const storedToken = localStorage.getItem(TOKEN_KEY)
  const storedRefreshToken = localStorage.getItem(REFRESH_TOKEN_KEY)
  const storedUser = localStorage.getItem(USER_KEY)
  const storedTenantId = localStorage.getItem(TENANT_KEY)
  
  if (storedToken && storedUser) {
    token.value = storedToken
    refreshToken.value = storedRefreshToken
    user.value = JSON.parse(storedUser)
    tenantId.value = storedTenantId ? parseInt(storedTenantId) : null
  }
}

/**
 * Store auth data in localStorage
 */
const storeAuthData = (authData) => {
  token.value = authData.access_token
  refreshToken.value = authData.refresh_token
  user.value = authData.user
  tenantId.value = authData.tenant_id || null
  
  localStorage.setItem(TOKEN_KEY, authData.access_token)
  localStorage.setItem(REFRESH_TOKEN_KEY, authData.refresh_token)
  localStorage.setItem(USER_KEY, JSON.stringify(authData.user))
  if (authData.tenant_id) {
    localStorage.setItem(TENANT_KEY, authData.tenant_id.toString())
  }
}

/**
 * Clear auth data from localStorage
 */
const clearAuthData = () => {
  token.value = null
  refreshToken.value = null
  user.value = null
  tenantId.value = null
  
  localStorage.removeItem(TOKEN_KEY)
  localStorage.removeItem(REFRESH_TOKEN_KEY)
  localStorage.removeItem(USER_KEY)
  localStorage.removeItem(TENANT_KEY)
}

/**
 * Login with email and password
 */
const login = async (credentials) => {
  isLoading.value = true
  error.value = null
  
  try {
    const response = await fetch('/api/v1/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(credentials),
    })
    
    const data = await response.json()
    
    if (!response.ok) {
      throw new Error(data.message || 'Login failed')
    }
    
    storeAuthData(data)
    return data
  } catch (e) {
    error.value = e.message
    throw e
  } finally {
    isLoading.value = false
  }
}

/**
 * Register new user
 */
const register = async (userData) => {
  isLoading.value = true
  error.value = null
  
  try {
    const response = await fetch('/api/v1/auth/register', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(userData),
    })
    
    const data = await response.json()
    
    if (!response.ok) {
      throw new Error(data.message || 'Registration failed')
    }
    
    storeAuthData(data)
    return data
  } catch (e) {
    error.value = e.message
    throw e
  } finally {
    isLoading.value = false
  }
}

/**
 * Logout user
 */
const logout = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    if (token.value) {
      await fetch('/api/v1/auth/logout', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token.value}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      })
    }
  } catch (e) {
    console.error('Logout error:', e)
  } finally {
    clearAuthData()
    isLoading.value = false
  }
}

/**
 * Logout from all devices
 */
const logoutAll = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    if (token.value) {
      await fetch('/api/v1/auth/logout-all', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token.value}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      })
    }
  } catch (e) {
    console.error('Logout all error:', e)
  } finally {
    clearAuthData()
    isLoading.value = false
  }
}

/**
 * Refresh access token
 */
const refresh = async () => {
  if (!refreshToken.value) {
    return false
  }
  
  try {
    const response = await fetch('/api/v1/auth/refresh', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ refresh_token: refreshToken.value }),
    })
    
    const data = await response.json()
    
    if (!response.ok) {
      clearAuthData()
      return false
    }
    
    token.value = data.access_token
    refreshToken.value = data.refresh_token
    localStorage.setItem(TOKEN_KEY, data.access_token)
    localStorage.setItem(REFRESH_TOKEN_KEY, data.refresh_token)
    
    return true
  } catch (e) {
    clearAuthData()
    return false
  }
}

/**
 * Get current user profile
 */
const fetchUser = async () => {
  if (!token.value) {
    return null
  }
  
  try {
    const response = await fetch('/api/v1/auth/me', {
      headers: {
        'Authorization': `Bearer ${token.value}`,
        'Accept': 'application/json',
      },
    })
    
    const data = await response.json()
    
    if (!response.ok) {
      if (response.status === 401) {
        // Try to refresh token
        const refreshed = await refresh()
        if (refreshed) {
          return fetchUser()
        }
        clearAuthData()
      }
      return null
    }
    
    user.value = data.user
    localStorage.setItem(USER_KEY, JSON.stringify(data.user))
    return data.user
  } catch (e) {
    console.error('Fetch user error:', e)
    return null
  }
}

/**
 * Check if user has permission
 */
const hasPermission = (permission) => {
  if (!user.value) return false
  // Assuming user has permissions array
  return user.value.permissions?.includes(permission) ?? false
}

/**
 * Check if user has any of the permissions
 */
const hasAnyPermission = (permissions) => {
  if (!user.value) return false
  return permissions.some(p => hasPermission(p))
}

/**
 * Check if user has all permissions
 */
const hasAllPermissions = (permissions) => {
  if (!user.value) return false
  return permissions.every(p => hasPermission(p))
}

/**
 * Initiate password reset
 */
const initiatePasswordReset = async (email) => {
  isLoading.value = true
  error.value = null
  
  try {
    const response = await fetch('/api/v1/auth/password/reset', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ email }),
    })
    
    const data = await response.json()
    
    if (!response.ok) {
      throw new Error(data.message || 'Failed to initiate password reset')
    }
    
    return data
  } catch (e) {
    error.value = e.message
    throw e
  } finally {
    isLoading.value = false
  }
}

/**
 * Reset password with token
 */
const resetPassword = async (resetData) => {
  isLoading.value = true
  error.value = null
  
  try {
    const response = await fetch('/api/v1/auth/password/reset/confirm', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(resetData),
    })
    
    const data = await response.json()
    
    if (!response.ok) {
      throw new Error(data.message || 'Failed to reset password')
    }
    
    return data
  } catch (e) {
    error.value = e.message
    throw e
  } finally {
    isLoading.value = false
  }
}

// Initialize auth on module load
initAuth()

export function useAuth() {
  return {
    // State
    user,
    token,
    refreshToken,
    tenantId,
    isLoading,
    error,
    
    // Computed
    isAuthenticated,
    
    // Methods
    login,
    register,
    logout,
    logoutAll,
    refresh,
    fetchUser,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    initiatePasswordReset,
    resetPassword,
  }
}
