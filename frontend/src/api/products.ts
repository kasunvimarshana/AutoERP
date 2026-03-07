import apiClient from './axios';
import type { Product, ProductFormData, StockAdjustment, PaginatedResponse, FilterParams } from '@/types';

export const productsApi = {
  list: async (params?: FilterParams): Promise<PaginatedResponse<Product>> => {
    const { data } = await apiClient.get<PaginatedResponse<Product>>('/products', { params });
    return data;
  },

  get: async (id: number): Promise<Product> => {
    const { data } = await apiClient.get<{ data: Product }>(`/products/${id}`);
    return data.data;
  },

  create: async (payload: ProductFormData): Promise<Product> => {
    const { data } = await apiClient.post<{ data: Product }>('/products', payload);
    return data.data;
  },

  update: async (id: number, payload: Partial<ProductFormData>): Promise<Product> => {
    const { data } = await apiClient.put<{ data: Product }>(`/products/${id}`, payload);
    return data.data;
  },

  delete: async (id: number): Promise<void> => {
    await apiClient.delete(`/products/${id}`);
  },

  adjustStock: async (adjustment: StockAdjustment): Promise<Product> => {
    const { data } = await apiClient.post<{ data: Product }>(
      `/products/${adjustment.product_id}/stock`,
      adjustment,
    );
    return data.data;
  },

  getLowStock: async (): Promise<Product[]> => {
    const { data } = await apiClient.get<{ data: Product[] }>('/products/low-stock');
    return data.data;
  },
};
