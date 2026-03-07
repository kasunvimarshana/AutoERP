import apiClient from './client';
import { Product, ProductCategory, PaginatedResponse } from '../types';

export interface ProductFilters {
  page?: number;
  per_page?: number;
  search?: string;
  category_id?: number;
  status?: string;
}

export const productsApi = {
  list: async (filters: ProductFilters = {}): Promise<PaginatedResponse<Product>> => {
    const { data } = await apiClient.get('/api/products', { params: filters });
    return data.data || data;
  },
  get: async (id: number): Promise<Product> => {
    const { data } = await apiClient.get(`/api/products/${id}`);
    return data.data || data;
  },
  create: async (productData: Partial<Product>): Promise<Product> => {
    const { data } = await apiClient.post('/api/products', productData);
    return data.data || data;
  },
  update: async (id: number, productData: Partial<Product>): Promise<Product> => {
    const { data } = await apiClient.put(`/api/products/${id}`, productData);
    return data.data || data;
  },
  delete: async (id: number): Promise<void> => {
    await apiClient.delete(`/api/products/${id}`);
  },
  categories: async (): Promise<ProductCategory[]> => {
    const { data } = await apiClient.get('/api/products/categories');
    return data.data || data;
  },
};
