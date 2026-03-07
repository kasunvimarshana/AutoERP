import apiClient from './client';
import { Order, PaginatedResponse } from '../types';

export interface OrderFilters {
  page?: number;
  per_page?: number;
  search?: string;
  status?: string;
}

export interface CreateOrderData {
  customer_name: string;
  customer_email: string;
  items: Array<{ product_id: number; quantity: number; unit_price: number }>;
}

export const ordersApi = {
  list: async (filters: OrderFilters = {}): Promise<PaginatedResponse<Order>> => {
    const { data } = await apiClient.get('/api/orders', { params: filters });
    return data.data || data;
  },
  get: async (id: number): Promise<Order> => {
    const { data } = await apiClient.get(`/api/orders/${id}`);
    return data.data || data;
  },
  create: async (orderData: CreateOrderData): Promise<Order> => {
    const { data } = await apiClient.post('/api/orders', orderData);
    return data.data || data;
  },
  updateStatus: async (id: number, status: string): Promise<Order> => {
    const { data } = await apiClient.patch(`/api/orders/${id}/status`, { status });
    return data.data || data;
  },
  cancel: async (id: number): Promise<Order> => {
    const { data } = await apiClient.post(`/api/orders/${id}/cancel`);
    return data.data || data;
  },
};
