import { defineStore } from 'pinia'
import authService from '../services/authService'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: authService.getUser(),
    token: localStorage.getItem('auth_token'),
    isAuthenticated: authService.isAuthenticated()
  }),

  getters: {
    currentUser: (state) => state.user,
    userRoles: (state) => state.user?.roles || [],
    userPermissions: (state) => {
      if (!state.user?.roles) return []
      return state.user.roles.flatMap(role => role.permissions || [])
    },
    hasPermission: (state) => (permission) => {
      const permissions = state.user?.roles?.flatMap(role => role.permissions || []) || []
      return permissions.some(p => p.slug === permission)
    },
    hasRole: (state) => (role) => {
      return state.user?.roles?.some(r => r.slug === role) || false
    }
  },

  actions: {
    async login(email, password, tenantSlug = 'demo-company') {
      try {
        const response = await authService.login(email, password, tenantSlug)
        if (response.success) {
          this.user = response.data.user
          this.token = response.data.token
          this.isAuthenticated = true
          return { success: true }
        }
        return { success: false, message: response.message }
      } catch (error) {
        return { 
          success: false, 
          message: error.response?.data?.message || 'Login failed'
        }
      }
    },

    async register(name, email, password, passwordConfirmation, tenantSlug = 'demo-company') {
      try {
        const response = await authService.register(name, email, password, passwordConfirmation, tenantSlug)
        if (response.success) {
          this.user = response.data.user
          this.token = response.data.token
          this.isAuthenticated = true
          return { success: true }
        }
        return { success: false, message: response.message }
      } catch (error) {
        return { 
          success: false, 
          message: error.response?.data?.message || 'Registration failed'
        }
      }
    },

    async logout() {
      await authService.logout()
      this.user = null
      this.token = null
      this.isAuthenticated = false
    },

    async fetchUser() {
      try {
        const user = await authService.getCurrentUser()
        this.user = user
        return user
      } catch (error) {
        this.logout()
        return null
      }
    },

    async refreshToken() {
      try {
        const response = await authService.refreshToken()
        if (response.success) {
          this.token = response.data.token
          return true
        }
        return false
      } catch (error) {
        this.logout()
        return false
      }
    }
  }
})
