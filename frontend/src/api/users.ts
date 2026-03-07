import apiClient from './client';
import type { ApiResponse, User, PaginatedResponse } from '../types';

export interface UserFilters {
  search?: string;
  role?: string;
  is_active?: boolean;
  per_page?: number;
  page?: number;
}

export interface CreateUserPayload {
  name: string;
  email: string;
  password: string;
  roles?: string[];
  is_active?: boolean;
  attributes?: Record<string, unknown>;
}

export interface UpdateUserPayload {
  name?: string;
  email?: string;
  password?: string;
  roles?: string[];
  is_active?: boolean;
  attributes?: Record<string, unknown>;
}

export const usersApi = {
  list: (filters?: UserFilters) =>
    apiClient.get<ApiResponse<PaginatedResponse<User> | User[]>>('/users', { params: filters }),

  get: (id: number) =>
    apiClient.get<ApiResponse<User>>(`/users/${id}`),

  create: (payload: CreateUserPayload) =>
    apiClient.post<ApiResponse<User>>('/users', payload),

  update: (id: number, payload: UpdateUserPayload) =>
    apiClient.put<ApiResponse<User>>(`/users/${id}`, payload),

  delete: (id: number) =>
    apiClient.delete(`/users/${id}`),
};
