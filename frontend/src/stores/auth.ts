import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/auth'
import type { User, LoginCredentials, RegisterData } from '@/types/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Computed
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const userRoles = computed(() => user.value?.roles?.map(r => r.name) || [])
  const userPermissions = computed(() => user.value?.permissions || [])

  // Initialize from localStorage
  const init = () => {
    const storedToken = localStorage.getItem('auth_token')
    const storedUser = localStorage.getItem('user')
    
    if (storedToken) {
      token.value = storedToken
    }
    
    if (storedUser) {
      try {
        user.value = JSON.parse(storedUser)
      } catch (e) {
        console.error('Failed to parse stored user', e)
      }
    }
  }

  // Login
  const login = async (credentials: LoginCredentials) => {
    try {
      loading.value = true
      error.value = null
      
      const response = await authService.login(credentials)
      
      if (response.success && response.data) {
        token.value = response.data.token
        user.value = response.data.user
        
        localStorage.setItem('auth_token', response.data.token)
        localStorage.setItem('user', JSON.stringify(response.data.user))
        
        if (credentials.tenant_id) {
          localStorage.setItem('tenant_id', credentials.tenant_id)
        }
      } else {
        throw new Error(response.message || 'Login failed')
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || err.message || 'Login failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Register
  const register = async (data: RegisterData) => {
    try {
      loading.value = true
      error.value = null
      
      const response = await authService.register(data)
      
      if (response.success && response.data) {
        token.value = response.data.token
        user.value = response.data.user
        
        localStorage.setItem('auth_token', response.data.token)
        localStorage.setItem('user', JSON.stringify(response.data.user))
        
        if (data.tenant_id) {
          localStorage.setItem('tenant_id', data.tenant_id)
        }
      } else {
        throw new Error(response.message || 'Registration failed')
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || err.message || 'Registration failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Logout
  const logout = async () => {
    try {
      await authService.logout()
    } catch (err) {
      console.error('Logout error:', err)
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')
      localStorage.removeItem('tenant_id')
    }
  }

  // Fetch current user
  const fetchUser = async () => {
    try {
      const response = await authService.getUser()
      
      if (response.success && response.data) {
        user.value = response.data
        localStorage.setItem('user', JSON.stringify(response.data))
      }
    } catch (err) {
      console.error('Failed to fetch user:', err)
      await logout()
    }
  }

  // Check permission
  const hasPermission = (permission: string): boolean => {
    return userPermissions.value.includes(permission)
  }

  // Check role
  const hasRole = (role: string): boolean => {
    return userRoles.value.includes(role)
  }

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    userRoles,
    userPermissions,
    init,
    login,
    register,
    logout,
    fetchUser,
    hasPermission,
    hasRole,
  }
})
