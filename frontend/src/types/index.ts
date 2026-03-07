export interface Tenant {
  id: number;
  name: string;
  domain: string;
  is_active: boolean;
  settings?: Record<string, unknown>;
}

export interface User {
  id: number;
  name: string;
  email: string;
  tenant_id: number;
  is_active: boolean;
  attributes?: Record<string, unknown>;
  roles?: Role[];
  permissions?: Permission[];
  tenant?: Tenant;
}

export interface Role {
  id: number;
  name: string;
  guard_name: string;
}

export interface Permission {
  id: number;
  name: string;
  guard_name: string;
}

export interface Product {
  id: number;
  tenant_id: number;
  name: string;
  description?: string;
  sku: string;
  price: number;
  category?: string;
  attributes?: Record<string, unknown>;
  is_active: boolean;
}

export interface Inventory {
  id: number;
  tenant_id: number;
  product_id: number;
  warehouse_location: string;
  quantity: number;
  reserved_quantity: number;
  reorder_level: number;
  unit_cost?: number;
  product?: Product;
  available_quantity?: number;
}

export interface OrderItem {
  id: number;
  order_id: number;
  product_id: number;
  inventory_id?: number;
  quantity: number;
  unit_price: number;
  subtotal: number;
  product?: Product;
}

export interface Order {
  id: number;
  tenant_id: number;
  user_id: number;
  order_number: string;
  status: 'pending' | 'confirmed' | 'processing' | 'shipped' | 'delivered' | 'cancelled' | 'refunded';
  total_amount: number;
  shipping_address?: Record<string, string>;
  notes?: string;
  items?: OrderItem[];
  user?: User;
}

export interface TenantConfig {
  id: number;
  tenant_id: number;
  key: string;
  value: string;
  group: string;
  type: string;
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
  message?: string;
}

export interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
}

export interface LoginCredentials {
  email: string;
  password: string;
}
