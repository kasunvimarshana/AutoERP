import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token') || null)
  const loading = ref(false)
  const error = ref(null)

  const isAuthenticated = computed(() => !!token.value)
  const userPermissions = computed(() => user.value?.permissions || [])
  const userRoles = computed(() => user.value?.roles || [])

  function hasPermission(permission) {
    return userPermissions.value.includes(permission)
  }

  function hasRole(role) {
    return userRoles.value.includes(role)
  }

  async function login(credentials) {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.auth.login(credentials)
      token.value = response.data.token
      user.value = response.data.user
      
      localStorage.setItem('auth_token', token.value)
      
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Login failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function register(userData) {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.auth.register(userData)
      token.value = response.data.token
      user.value = response.data.user
      
      localStorage.setItem('auth_token', token.value)
      
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Registration failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await api.auth.logout()
    } catch (err) {
      console.error('Logout error:', err)
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
      router.push({ name: 'login' })
    }
  }

  async function fetchUser() {
    if (!token.value) return
    
    loading.value = true
    
    try {
      const response = await api.auth.me()
      user.value = response.data
    } catch (err) {
      console.error('Fetch user error:', err)
      logout()
    } finally {
      loading.value = false
    }
  }

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    userPermissions,
    userRoles,
    hasPermission,
    hasRole,
    login,
    register,
    logout,
    fetchUser
  }
})
