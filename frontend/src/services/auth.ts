import apiClient from './api'
import { API_ENDPOINTS } from '@/config'
import type { LoginCredentials, RegisterData, User, ApiResponse } from '@/types/auth'

export const authService = {
  async login(credentials: LoginCredentials): Promise<ApiResponse<{ user: User; token: string }>> {
    const response = await apiClient.post(API_ENDPOINTS.auth.login, credentials)
    return response.data
  },

  async register(data: RegisterData): Promise<ApiResponse<{ user: User; token: string }>> {
    const response = await apiClient.post(API_ENDPOINTS.auth.register, data)
    return response.data
  },

  async logout(): Promise<ApiResponse> {
    const response = await apiClient.post(API_ENDPOINTS.auth.logout)
    return response.data
  },

  async getUser(): Promise<ApiResponse<User>> {
    const response = await apiClient.get(API_ENDPOINTS.auth.user)
    return response.data
  },

  async refreshToken(): Promise<ApiResponse<{ token: string }>> {
    const response = await apiClient.post(API_ENDPOINTS.auth.refresh)
    return response.data
  },
}
