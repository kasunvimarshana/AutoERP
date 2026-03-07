import apiClient from './axios';
import type { Order, OrderFormData, PaginatedResponse, FilterParams } from '@/types';
import type { SagaExecution } from '@/types/saga';

export const ordersApi = {
  list: async (params?: FilterParams): Promise<PaginatedResponse<Order>> => {
    const { data } = await apiClient.get<PaginatedResponse<Order>>('/orders', { params });
    return data;
  },

  get: async (id: number): Promise<Order> => {
    const { data } = await apiClient.get<{ data: Order }>(`/orders/${id}`);
    return data.data;
  },

  create: async (payload: OrderFormData): Promise<Order> => {
    const { data } = await apiClient.post<{ data: Order }>('/orders', payload);
    return data.data;
  },

  updateStatus: async (id: number, status: string): Promise<Order> => {
    const { data } = await apiClient.patch<{ data: Order }>(`/orders/${id}/status`, { status });
    return data.data;
  },

  cancel: async (id: number, reason?: string): Promise<Order> => {
    const { data } = await apiClient.post<{ data: Order }>(`/orders/${id}/cancel`, { reason });
    return data.data;
  },

  getSaga: async (orderId: number): Promise<SagaExecution> => {
    const { data } = await apiClient.get<{ data: SagaExecution }>(`/orders/${orderId}/saga`);
    return data.data;
  },
};
