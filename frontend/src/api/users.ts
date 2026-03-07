import apiClient from './client';
import { User, PaginatedResponse } from '../types';

export interface UserFilters {
  page?: number;
  per_page?: number;
  search?: string;
  role?: string;
}

export const usersApi = {
  list: async (filters: UserFilters = {}): Promise<PaginatedResponse<User>> => {
    const { data } = await apiClient.get('/api/users', { params: filters });
    return data.data || data;
  },
  get: async (id: number): Promise<User> => {
    const { data } = await apiClient.get(`/api/users/${id}`);
    return data.data || data;
  },
  create: async (userData: Partial<User> & { password: string }): Promise<User> => {
    const { data } = await apiClient.post('/api/users', userData);
    return data.data || data;
  },
  update: async (id: number, userData: Partial<User>): Promise<User> => {
    const { data } = await apiClient.put(`/api/users/${id}`, userData);
    return data.data || data;
  },
  delete: async (id: number): Promise<void> => {
    await apiClient.delete(`/api/users/${id}`);
  },
};
