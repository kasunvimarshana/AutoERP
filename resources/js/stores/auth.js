import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token') || null)

  const isAuthenticated = computed(() => !!token.value)

  const setAuth = (userData, authToken) => {
    user.value = userData
    token.value = authToken
    localStorage.setItem('token', authToken)
    api.defaults.headers.common['Authorization'] = `Bearer ${authToken}`
  }

  const clearAuth = () => {
    user.value = null
    token.value = null
    localStorage.removeItem('token')
    delete api.defaults.headers.common['Authorization']
  }

  const login = async (credentials) => {
    try {
      const response = await api.post('/api/v1/auth/login', credentials)
      setAuth(response.data.user, response.data.token)
      return response.data
    } catch (error) {
      throw error
    }
  }

  const register = async (userData) => {
    try {
      const response = await api.post('/api/v1/auth/register', userData)
      return response.data
    } catch (error) {
      throw error
    }
  }

  const logout = async () => {
    try {
      await api.post('/api/v1/auth/logout')
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      clearAuth()
    }
  }

  const fetchUser = async () => {
    try {
      const response = await api.get('/api/v1/auth/me')
      user.value = response.data.user
      return response.data.user
    } catch (error) {
      clearAuth()
      throw error
    }
  }

  // Initialize auth from token
  if (token.value) {
    api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
    fetchUser().catch(() => clearAuth())
  }

  return {
    user,
    token,
    isAuthenticated,
    login,
    register,
    logout,
    fetchUser,
    setAuth,
    clearAuth
  }
})
