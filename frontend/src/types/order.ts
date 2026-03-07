export type OrderStatus =
  | 'pending'
  | 'processing'
  | 'confirmed'
  | 'shipped'
  | 'delivered'
  | 'cancelled'
  | 'refunded'
  | 'failed';

export type PaymentStatus = 'pending' | 'paid' | 'failed' | 'refunded';

export interface Order {
  id: number;
  tenant_id: number;
  order_number: string;
  customer_name: string;
  customer_email: string;
  status: OrderStatus;
  payment_status: PaymentStatus;
  subtotal: number;
  tax: number;
  shipping: number;
  total: number;
  notes: string | null;
  shipping_address: Address | null;
  billing_address: Address | null;
  items: OrderItem[];
  saga_id: string | null;
  saga_status: string | null;
  metadata: Record<string, unknown> | null;
  created_at: string;
  updated_at: string;
}

export interface OrderItem {
  id: number;
  order_id: number;
  product_id: number;
  product_name: string;
  product_sku: string;
  quantity: number;
  unit_price: number;
  total_price: number;
}

export interface Address {
  street: string;
  city: string;
  state: string;
  zip: string;
  country: string;
}

export interface OrderFormData {
  customer_name: string;
  customer_email: string;
  notes?: string;
  shipping_address?: Address;
  items: OrderItemFormData[];
}

export interface OrderItemFormData {
  product_id: number;
  quantity: number;
  unit_price: number;
}
