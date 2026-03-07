import apiClient from './client';
import type { ApiResponse, Product, PaginatedResponse } from '../types';

export interface ProductFilters {
  search?: string;
  category?: string;
  is_active?: boolean;
  per_page?: number;
  page?: number;
}

export const productsApi = {
  list: (filters?: ProductFilters) =>
    apiClient.get<ApiResponse<PaginatedResponse<Product> | Product[]>>('/products', { params: filters }),

  get: (id: number) =>
    apiClient.get<ApiResponse<Product>>(`/products/${id}`),

  create: (payload: Partial<Product>) =>
    apiClient.post<ApiResponse<Product>>('/products', payload),

  update: (id: number, payload: Partial<Product>) =>
    apiClient.put<ApiResponse<Product>>(`/products/${id}`, payload),

  delete: (id: number) =>
    apiClient.delete(`/products/${id}`),
};
