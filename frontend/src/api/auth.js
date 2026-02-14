import apiClient from './client'

export default {
  login(credentials) {
    return apiClient.post('/auth/login', credentials)
  },
  
  register(data) {
    return apiClient.post('/auth/register', data)
  },
  
  logout() {
    return apiClient.post('/auth/logout')
  },
  
  me() {
    return apiClient.get('/auth/me')
  },
  
  refreshToken() {
    return apiClient.post('/auth/refresh')
  },
  
  forgotPassword(email) {
    return apiClient.post('/auth/forgot-password', { email })
  },
  
  resetPassword(data) {
    return apiClient.post('/auth/reset-password', data)
  }
}
