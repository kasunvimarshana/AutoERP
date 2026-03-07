import apiClient from './apiClient';

export interface OrderItem {
  product_id: string;
  quantity:   number;
  unit_price: number;
}

export interface Order {
  id:             string;
  tenant_id:      string;
  user_id:        string;
  status:         'pending' | 'confirmed' | 'cancelled' | 'failed';
  total_amount:   number;
  currency:       string;
  payment_method: string;
  items:          OrderItem[];
  confirmed_at:   string | null;
  cancelled_at:   string | null;
  saga_logs?:     SagaLogEntry[];
}

export interface SagaLogEntry {
  id:            string;
  saga_id:       string;
  step_name:     string;
  status:        'completed' | 'failed' | 'compensated' | 'compensation_failed';
  error_message: string | null;
  created_at:    string;
}

export interface CreateOrderPayload {
  items:          { product_id: string; quantity: number; unit_price: number }[];
  total_amount:   number;
  currency?:      string;
  payment_method?: string;
  user_email?:    string;
  notes?:         string;
}

export interface PaginatedOrders {
  data:         Order[];
  current_page: number;
  last_page:    number;
  total:        number;
}

/**
 * Order API calls – each createOrder triggers a full Saga transaction.
 */
export const orderService = {
  async listOrders(page = 1): Promise<PaginatedOrders> {
    const r = await apiClient.get('/orders/orders', { params: { page } });
    return r.data;
  },

  async createOrder(payload: CreateOrderPayload): Promise<Order> {
    const r = await apiClient.post('/orders/orders', payload);
    return r.data.data;
  },

  async getOrder(id: string): Promise<Order> {
    const r = await apiClient.get(`/orders/orders/${id}`);
    return r.data.data;
  },

  async cancelOrder(id: string): Promise<Order> {
    const r = await apiClient.post(`/orders/orders/${id}/cancel`);
    return r.data.data;
  },
};
