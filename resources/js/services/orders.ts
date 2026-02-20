import http from '@/services/http';
import type { Order, PaginatedResponse } from '@/types/index';

export interface OrderLinePayload {
  product_id: number;
  quantity: number;
  unit_price: string;
}

export interface OrderPayload {
  order_type: 'sale' | 'purchase' | 'return';
  lines: OrderLinePayload[];
  notes?: string | null;
}

export const orderService = {
  list(params?: Record<string, unknown>) {
    return http.get<PaginatedResponse<Order> | Order[]>('/orders', { params });
  },
  create(payload: OrderPayload) {
    return http.post<Order>('/orders', payload);
  },
  confirm(id: number) {
    return http.patch<Order>(`/orders/${id}/confirm`);
  },
  cancel(id: number) {
    return http.patch<Order>(`/orders/${id}/cancel`);
  },
};
