// ─── Auth ────────────────────────────────────────────────────────────────────

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  status: string;
  roles: Role[];
  permissions: string[];
}

export interface LoginResponse {
  access_token: string;
  token_type: string;
  expires_in: number;
  user?: AuthUser;
}

// ─── RBAC ────────────────────────────────────────────────────────────────────

export interface Role {
  id: number;
  name: string;
  guard_name: string;
  permissions?: Permission[];
}

export interface Permission {
  id: number;
  name: string;
  guard_name: string;
}

// ─── API ─────────────────────────────────────────────────────────────────────

export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}

// ─── Product ─────────────────────────────────────────────────────────────────

export interface Product {
  id: number;
  name: string;
  type: 'goods' | 'service' | 'digital' | 'bundle' | 'composite';
  sku: string | null;
  base_price: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

// ─── Inventory ───────────────────────────────────────────────────────────────

export interface StockItem {
  id: number;
  product_id: number;
  warehouse_id: number;
  quantity_on_hand: number;
  reorder_point: number | null;
  product?: { name: string };
  warehouse?: { name: string };
}

// ─── Order ───────────────────────────────────────────────────────────────────

export type OrderStatus = 'draft' | 'confirmed' | 'cancelled' | 'completed';
export type OrderType = 'sale' | 'purchase' | 'return';

export interface Order {
  id: number;
  reference_number: string;
  order_type: OrderType;
  status: OrderStatus;
  total_amount: string;
  created_at: string;
}

// ─── Invoice ─────────────────────────────────────────────────────────────────

export type InvoiceStatus = 'draft' | 'sent' | 'paid' | 'void' | 'overdue';

export interface Invoice {
  id: number;
  invoice_number: string;
  status: InvoiceStatus;
  total_amount: string;
  amount_due: string;
  due_date: string | null;
  created_at: string;
}

// ─── Purchase ────────────────────────────────────────────────────────────────

export type PurchaseStatus = 'draft' | 'ordered' | 'received' | 'cancelled';

export interface Purchase {
  id: number;
  reference_number: string;
  status: PurchaseStatus;
  total_amount: string;
  created_at: string;
  supplier?: { name: string };
}

// ─── CRM ─────────────────────────────────────────────────────────────────────

export interface Contact {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  type: 'person' | 'company';
  created_at: string;
}

export interface Lead {
  id: number;
  title: string;
  status: string;
  contact?: Contact;
  estimated_value: string | null;
  created_at: string;
}

export interface Opportunity {
  id: number;
  title: string;
  stage: string;
  amount: string | null;
  contact?: Contact;
  created_at: string;
}

// ─── Accounting ──────────────────────────────────────────────────────────────

export type AccountType = 'asset' | 'liability' | 'equity' | 'revenue' | 'expense';

export interface ChartOfAccount {
  id: number;
  code: string;
  name: string;
  type: AccountType;
  is_active: boolean;
  parent_id: number | null;
}

export interface JournalEntry {
  id: number;
  reference: string;
  description: string | null;
  status: 'draft' | 'posted';
  total_debit: string;
  total_credit: string;
  created_at: string;
}

// ─── Reporting ───────────────────────────────────────────────────────────────

export interface SalesSummary {
  period: string;
  total_orders: number;
  total_revenue: string;
  total_discount: string;
  net_revenue: string;
}

export interface InventorySummary {
  product_id: number;
  product_name: string;
  total_quantity: number;
  total_value: string;
}

// ─── User ────────────────────────────────────────────────────────────────────

export interface User {
  id: number;
  name: string;
  email: string;
  status: 'active' | 'suspended';
  roles: Role[];
}

// ─── POS ─────────────────────────────────────────────────────────────────────

export interface PosTransaction {
  id: number;
  reference_number: string;
  status: string;
  total_amount: string;
  payment_method: string | null;
  created_at: string;
  location?: { name: string };
}

// ─── Warehouse ───────────────────────────────────────────────────────────────

export interface Warehouse {
  id: number;
  name: string;
  location: string | null;
  is_active: boolean;
}

// ─── Accounting Period ───────────────────────────────────────────────────────

export interface AccountingPeriod {
  id: number;
  name: string;
  start_date: string;
  end_date: string;
  status: 'open' | 'closed' | 'locked';
}

// ─── Payment ─────────────────────────────────────────────────────────────────

export interface Payment {
  id: number;
  invoice_id: number;
  amount: string;
  method: string;
  reference: string | null;
  created_at: string;
}

// ─── Navigation ──────────────────────────────────────────────────────────────

export interface NavItem {
  to: string;
  label: string;
  icon: string;
  permission?: string;
}

// ─── Route Meta ──────────────────────────────────────────────────────────────

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean;
    permission?: string;
    /** Module ID this route belongs to */
    module?: string;
  }
}
