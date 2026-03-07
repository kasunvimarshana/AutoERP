import apiClient from './client';
import type { ApiResponse, Inventory, PaginatedResponse } from '../types';

export interface InventoryFilters {
  product_id?: number;
  product_name?: string;
  warehouse_location?: string;
  low_stock?: boolean;
  per_page?: number;
  page?: number;
}

export const inventoryApi = {
  list: (filters?: InventoryFilters) =>
    apiClient.get<ApiResponse<PaginatedResponse<Inventory> | Inventory[]>>('/inventory', { params: filters }),

  get: (id: number) =>
    apiClient.get<ApiResponse<Inventory>>(`/inventory/${id}`),

  create: (payload: Partial<Inventory>) =>
    apiClient.post<ApiResponse<Inventory>>('/inventory', payload),

  update: (id: number, payload: Partial<Inventory>) =>
    apiClient.put<ApiResponse<Inventory>>(`/inventory/${id}`, payload),

  delete: (id: number) =>
    apiClient.delete(`/inventory/${id}`),

  adjustStock: (id: number, delta: number, reason?: string) =>
    apiClient.post<ApiResponse<Inventory>>(`/inventory/${id}/adjust-stock`, { delta, reason }),
};
