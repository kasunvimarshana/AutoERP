import apiClient from '@/api/client'

export default {
  login(credentials) {
    return apiClient.post('/auth/login', credentials)
  },

  register(userData) {
    return apiClient.post('/auth/register', userData)
  },

  logout() {
    return apiClient.post('/auth/logout')
  },

  me() {
    return apiClient.get('/auth/me')
  },

  forgotPassword(email) {
    return apiClient.post('/auth/forgot-password', { email })
  },

  resetPassword(data) {
    return apiClient.post('/auth/reset-password', data)
  }
}
