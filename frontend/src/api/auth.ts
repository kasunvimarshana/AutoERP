import apiClient from './client';
import { User, AuthTokens, LoginCredentials, RegisterData } from '../types';

interface AuthResponse {
  user: User;
  tokens: AuthTokens;
}

export const authApi = {
  login: async (credentials: LoginCredentials): Promise<AuthResponse> => {
    const { data } = await apiClient.post('/api/auth/login', credentials);
    return data.data || data;
  },
  register: async (registerData: RegisterData): Promise<AuthResponse> => {
    const { data } = await apiClient.post('/api/auth/register', registerData);
    return data.data || data;
  },
  logout: async (): Promise<void> => {
    await apiClient.post('/api/auth/logout');
  },
  refresh: async (refreshToken: string): Promise<AuthResponse> => {
    const { data } = await apiClient.post('/api/auth/refresh', { refresh_token: refreshToken });
    return data.data || data;
  },
  introspect: async (): Promise<User> => {
    const { data } = await apiClient.get('/api/auth/me');
    return data.data || data;
  },
};
