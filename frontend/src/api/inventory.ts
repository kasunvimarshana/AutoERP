import apiClient from './client';
import { Inventory, InventoryTransaction, PaginatedResponse } from '../types';

export interface InventoryFilters {
  page?: number;
  per_page?: number;
  search?: string;
  low_stock?: boolean;
}

export interface AdjustmentData {
  type: 'in' | 'out' | 'adjustment';
  quantity: number;
  reference?: string;
  notes?: string;
}

export const inventoryApi = {
  list: async (filters: InventoryFilters = {}): Promise<PaginatedResponse<Inventory>> => {
    const { data } = await apiClient.get('/api/inventory', { params: filters });
    return data.data || data;
  },
  get: async (id: number): Promise<Inventory> => {
    const { data } = await apiClient.get(`/api/inventory/${id}`);
    return data.data || data;
  },
  adjust: async (id: number, adjustment: AdjustmentData): Promise<Inventory> => {
    const { data } = await apiClient.post(`/api/inventory/${id}/adjust`, adjustment);
    return data.data || data;
  },
  transactions: async (id: number): Promise<InventoryTransaction[]> => {
    const { data } = await apiClient.get(`/api/inventory/${id}/transactions`);
    return data.data || data;
  },
  create: async (inventoryData: Partial<Inventory>): Promise<Inventory> => {
    const { data } = await apiClient.post('/api/inventory', inventoryData);
    return data.data || data;
  },
  update: async (id: number, inventoryData: Partial<Inventory>): Promise<Inventory> => {
    const { data } = await apiClient.put(`/api/inventory/${id}`, inventoryData);
    return data.data || data;
  },
};
