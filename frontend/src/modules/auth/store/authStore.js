import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import authApi from '../api/authApi'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token') || null)
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value)

  const login = async (credentials) => {
    loading.value = true
    try {
      const response = await authApi.login(credentials)
      token.value = response.data.token
      user.value = response.data.user
      localStorage.setItem('token', token.value)
      return response.data
    } catch (error) {
      throw error
    } finally {
      loading.value = false
    }
  }

  const register = async (userData) => {
    loading.value = true
    try {
      const response = await authApi.register(userData)
      token.value = response.data.token
      user.value = response.data.user
      localStorage.setItem('token', token.value)
      return response.data
    } catch (error) {
      throw error
    } finally {
      loading.value = false
    }
  }

  const logout = () => {
    user.value = null
    token.value = null
    localStorage.removeItem('token')
  }

  const checkAuth = async () => {
    if (!token.value) return
    
    try {
      const response = await authApi.me()
      user.value = response.data
    } catch (error) {
      logout()
    }
  }

  return {
    user,
    token,
    loading,
    isAuthenticated,
    login,
    register,
    logout,
    checkAuth
  }
})
