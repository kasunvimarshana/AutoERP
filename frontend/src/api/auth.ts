import httpClient from '@/api/client'
import type { ApiResponse } from '@/types/api'
import type { AuthTokens, LoginCredentials, User } from '@/types/auth'

export interface RegisterPayload {
  name: string
  email: string
  password: string
  password_confirmation: string
  device_name?: string
}

export interface UpdateProfilePayload {
  name?: string
  email?: string
}

export interface ChangePasswordPayload {
  current_password: string
  new_password: string
}

/** Authentication API — maps to backend /api/v1/auth/* endpoints. */
const authApi = {
  /**
   * Register a new user account.
   * POST /api/v1/register
   */
  register: (payload: RegisterPayload) =>
    httpClient.post<ApiResponse<User>>('/register', payload),

  /**
   * Exchange credentials for a JWT access token.
   * POST /api/v1/auth/login
   */
  login: (credentials: LoginCredentials) =>
    httpClient.post<ApiResponse<AuthTokens>>('/auth/login', credentials),

  /**
   * Invalidate the current token.
   * POST /api/v1/auth/logout
   */
  logout: () => httpClient.post<ApiResponse<null>>('/auth/logout'),

  /**
   * Rotate the current access token before it expires.
   * POST /api/v1/auth/refresh
   */
  refresh: () => httpClient.post<ApiResponse<AuthTokens>>('/auth/refresh'),

  /**
   * Retrieve the authenticated user's profile.
   * GET /api/v1/auth/me
   */
  me: () => httpClient.get<ApiResponse<User>>('/auth/me'),

  /**
   * Update the authenticated user's profile.
   * PUT /api/v1/profile
   */
  updateProfile: (payload: UpdateProfilePayload) =>
    httpClient.put<ApiResponse<User>>('/profile', payload),

  /**
   * Change the authenticated user's password.
   * POST /api/v1/auth/password/change
   */
  changePassword: (payload: ChangePasswordPayload) =>
    httpClient.post<ApiResponse<null>>('/auth/password/change', payload),
}

export default authApi
