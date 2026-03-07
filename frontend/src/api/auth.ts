import apiClient from './axios';
import type { User, AuthTokens, LoginCredentials, RegisterData } from '@/types';

export interface LoginResponse {
  user: User;
  token: AuthTokens;
}

export const authApi = {
  login: async (credentials: LoginCredentials): Promise<LoginResponse> => {
    const { data } = await apiClient.post<LoginResponse>('/auth/login', credentials);
    return data;
  },

  register: async (payload: RegisterData): Promise<LoginResponse> => {
    const { data } = await apiClient.post<LoginResponse>('/auth/register', payload);
    return data;
  },

  logout: async (): Promise<void> => {
    await apiClient.post('/auth/logout');
  },

  me: async (): Promise<User> => {
    const { data } = await apiClient.get<{ data: User }>('/auth/me');
    return data.data;
  },

  refresh: async (): Promise<AuthTokens> => {
    const { data } = await apiClient.post<AuthTokens>('/auth/refresh');
    return data;
  },
};
