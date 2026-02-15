import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token') || null)

  const isAuthenticated = computed(() => !!token.value)

  async function login(credentials) {
    try {
      const response = await api.post('/auth/login', credentials)
      token.value = response.data.data.access_token
      user.value = response.data.data.user
      localStorage.setItem('token', token.value)
      return true
    } catch (error) {
      console.error('Login failed:', error)
      return false
    }
  }

  async function register(userData) {
    try {
      const response = await api.post('/auth/register', userData)
      token.value = response.data.data.access_token
      user.value = response.data.data.user
      localStorage.setItem('token', token.value)
      return true
    } catch (error) {
      console.error('Registration failed:', error)
      return false
    }
  }

  async function logout() {
    try {
      await api.post('/auth/logout')
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
    }
  }

  async function fetchUser() {
    try {
      const response = await api.get('/auth/me')
      user.value = response.data.data.user
    } catch (error) {
      console.error('Failed to fetch user:', error)
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    login,
    register,
    logout,
    fetchUser
  }
})
