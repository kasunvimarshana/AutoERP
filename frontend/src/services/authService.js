import apiClient from './api'

export const authService = {
  /**
   * Login user
   */
  async login(email, password, tenantSlug) {
    const response = await apiClient.post('/auth/login', {
      email,
      password,
      tenant_slug: tenantSlug
    })
    
    if (response.success && response.data.token) {
      localStorage.setItem('auth_token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.user))
    }
    
    return response
  },

  /**
   * Register new user
   */
  async register(name, email, password, passwordConfirmation, tenantSlug) {
    const response = await apiClient.post('/auth/register', {
      name,
      email,
      password,
      password_confirmation: passwordConfirmation,
      tenant_slug: tenantSlug
    })
    
    if (response.success && response.data.token) {
      localStorage.setItem('auth_token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.user))
    }
    
    return response
  },

  /**
   * Logout user
   */
  async logout() {
    try {
      await apiClient.post('/auth/logout')
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')
    }
  },

  /**
   * Get current user
   */
  async getCurrentUser() {
    const response = await apiClient.get('/auth/user')
    if (response.success) {
      localStorage.setItem('user', JSON.stringify(response.data))
      return response.data
    }
    return null
  },

  /**
   * Refresh token
   */
  async refreshToken() {
    const response = await apiClient.post('/auth/refresh')
    if (response.success && response.data.token) {
      localStorage.setItem('auth_token', response.data.token)
    }
    return response
  },

  /**
   * Check if user is authenticated
   */
  isAuthenticated() {
    return !!localStorage.getItem('auth_token')
  },

  /**
   * Get stored user
   */
  getUser() {
    const user = localStorage.getItem('user')
    return user ? JSON.parse(user) : null
  }
}

export default authService
