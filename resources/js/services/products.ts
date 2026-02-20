import http from '@/services/http';
import type { Product, PaginatedResponse } from '@/types/index';

export interface ProductPayload {
  name: string;
  type: 'goods' | 'service' | 'digital' | 'bundle' | 'composite';
  sku?: string | null;
  base_price: string;
  is_active?: boolean;
  lock_version?: number;
}

export const productService = {
  list(params?: Record<string, unknown>) {
    return http.get<PaginatedResponse<Product> | Product[]>('/products', { params });
  },
  create(payload: ProductPayload) {
    return http.post<Product>('/products', payload);
  },
  update(id: number, payload: Partial<ProductPayload>) {
    return http.put<Product>(`/products/${id}`, payload);
  },
  remove(id: number) {
    return http.delete(`/products/${id}`);
  },
};
