import apiClient from './client';
import type { ApiResponse, Order, PaginatedResponse } from '../types';

export interface OrderFilters {
  status?: string;
  per_page?: number;
  page?: number;
}

export interface CreateOrderPayload {
  items: Array<{ product_id: number; quantity: number }>;
  shipping_address?: Record<string, string>;
  notes?: string;
  payment_method?: string;
}

export const ordersApi = {
  list: (filters?: OrderFilters) =>
    apiClient.get<ApiResponse<PaginatedResponse<Order> | Order[]>>('/orders', { params: filters }),

  get: (id: number) =>
    apiClient.get<ApiResponse<Order>>(`/orders/${id}`),

  create: (payload: CreateOrderPayload) =>
    apiClient.post<ApiResponse<Order>>('/orders', payload),

  updateStatus: (id: number, status: string) =>
    apiClient.patch<ApiResponse<Order>>(`/orders/${id}/status`, { status }),

  cancel: (id: number, reason?: string) =>
    apiClient.post<ApiResponse<Order>>(`/orders/${id}/cancel`, { reason }),
};
