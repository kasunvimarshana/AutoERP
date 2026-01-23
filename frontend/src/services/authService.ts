import apiClient from './api'
import type {
  User,
  LoginCredentials,
  RegisterData,
  AuthResponse,
  PasswordResetRequest,
  PasswordResetConfirm,
  PasswordChangeData,
} from '@/types/auth'
import type { ApiResponse } from '@/types/api'
import { appConfig } from '@/config/app'

/**
 * Authentication Service
 * Handles all authentication-related API calls
 */

export const authService = {
  /**
   * Login with email and password
   */
  async login(credentials: LoginCredentials): Promise<AuthResponse> {
    const response = await apiClient.post<ApiResponse<AuthResponse['data']>>(
      '/auth/login',
      credentials,
    )
    return {
      success: response.data.success,
      message: response.data.message,
      data: response.data.data,
    }
  },

  /**
   * Register a new user
   */
  async register(data: RegisterData): Promise<AuthResponse> {
    const response = await apiClient.post<ApiResponse<AuthResponse['data']>>(
      '/auth/register',
      data,
    )
    return {
      success: response.data.success,
      message: response.data.message,
      data: response.data.data,
    }
  },

  /**
   * Logout the current user
   */
  async logout(): Promise<void> {
    try {
      await apiClient.post('/auth/logout')
    } finally {
      // Clear local storage even if API call fails
      localStorage.removeItem(appConfig.auth.tokenKey)
      localStorage.removeItem(appConfig.auth.userKey)
      localStorage.removeItem(appConfig.tenant.storageKey)
    }
  },

  /**
   * Get current authenticated user
   */
  async me(): Promise<User> {
    const response = await apiClient.get<ApiResponse<User>>('/auth/me')
    return response.data.data
  },

  /**
   * Request password reset
   */
  async forgotPassword(data: PasswordResetRequest): Promise<ApiResponse> {
    const response = await apiClient.post<ApiResponse>('/auth/forgot-password', data)
    return response.data
  },

  /**
   * Reset password with token
   */
  async resetPassword(data: PasswordResetConfirm): Promise<ApiResponse> {
    const response = await apiClient.post<ApiResponse>('/auth/reset-password', data)
    return response.data
  },

  /**
   * Change password for authenticated user
   */
  async changePassword(data: PasswordChangeData): Promise<ApiResponse> {
    const response = await apiClient.post<ApiResponse>('/auth/change-password', data)
    return response.data
  },

  /**
   * Refresh authentication token
   */
  async refreshToken(): Promise<{ token: string }> {
    const response = await apiClient.post<ApiResponse<{ token: string }>>('/auth/refresh')
    return response.data.data
  },

  /**
   * Verify email address
   */
  async verifyEmail(token: string): Promise<ApiResponse> {
    const response = await apiClient.post<ApiResponse>('/auth/verify-email', { token })
    return response.data
  },

  /**
   * Resend email verification
   */
  async resendVerification(): Promise<ApiResponse> {
    const response = await apiClient.post<ApiResponse>('/auth/resend-verification')
    return response.data
  },
}

export default authService
