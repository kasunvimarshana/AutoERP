import apiClient from './client';
import type { ApiResponse, User, LoginCredentials } from '../types';

interface LoginResponse {
  success: boolean;
  access_token: string;
  token_type: string;
  user: User;
}

export const authApi = {
  login: (credentials: LoginCredentials) =>
    apiClient.post<LoginResponse>('/auth/login', credentials),

  logout: () =>
    apiClient.post('/auth/logout'),

  me: () =>
    apiClient.get<ApiResponse<User>>('/auth/me'),

  refresh: () =>
    apiClient.post<{ access_token: string; token_type: string }>('/auth/refresh'),
};
