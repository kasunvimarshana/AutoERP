export interface Tenant {
  id: number;
  name: string;
  slug: string;
  domain: string;
  plan: string;
  settings: Record<string, any>;
}

export interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  permissions: string[];
  tenant_id: number;
}

export interface Product {
  id: number;
  name: string;
  sku: string;
  description: string;
  price: number;
  category_id: number;
  category?: ProductCategory;
  tenant_id: number;
  status: 'active' | 'inactive';
}

export interface ProductCategory {
  id: number;
  name: string;
  description: string;
}

export interface Inventory {
  id: number;
  product_id: number;
  product?: Product;
  warehouse_location: string;
  quantity: number;
  reserved_quantity: number;
  reorder_level: number;
  tenant_id: number;
}

export interface InventoryTransaction {
  id: number;
  inventory_id: number;
  type: 'in' | 'out' | 'adjustment' | 'reserve' | 'release';
  quantity: number;
  reference: string;
  notes: string;
  created_at: string;
}

export interface Order {
  id: number;
  order_number: string;
  status: 'pending' | 'confirmed' | 'processing' | 'shipped' | 'delivered' | 'cancelled';
  customer_name: string;
  customer_email: string;
  items: OrderItem[];
  total_amount: number;
  tenant_id: number;
  created_at: string;
  updated_at?: string;
}

export interface OrderItem {
  id: number;
  product_id: number;
  product?: Product;
  quantity: number;
  unit_price: number;
  total_price: number;
}

export interface Webhook {
  id: number;
  name: string;
  url: string;
  events: string[];
  is_active: boolean;
  secret?: string;
  created_at?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message: string;
}

export interface DashboardStats {
  total_products: number;
  low_stock_items: number;
  pending_orders: number;
  total_revenue: number;
  recent_orders: Order[];
  inventory_alerts: Inventory[];
}

export interface HealthStatus {
  status: string;
  timestamp: string;
  services: Record<string, { status: string; latency?: number; message?: string }>;
}

export interface LoginCredentials {
  email: string;
  password: string;
  tenant_id: number;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  tenant_id?: number;
  tenant_name?: string;
  tenant_slug?: string;
}

export interface AuthTokens {
  access_token: string;
  refresh_token?: string;
  token_type: string;
  expires_in: number;
}
