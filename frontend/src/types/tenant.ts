export interface Tenant {
  id: number;
  name: string;
  slug: string;
  domain: string | null;
  plan: TenantPlan;
  status: TenantStatus;
  settings: TenantSettings | null;
  users_count?: number;
  products_count?: number;
  orders_count?: number;
  created_at: string;
  updated_at: string;
}

export type TenantPlan = 'free' | 'starter' | 'professional' | 'enterprise';
export type TenantStatus = 'active' | 'inactive' | 'suspended' | 'trial';

export interface TenantSettings {
  currency: string;
  timezone: string;
  date_format: string;
  tax_rate: number;
  low_stock_threshold: number;
}

export interface TenantFormData {
  name: string;
  slug: string;
  domain?: string;
  plan: TenantPlan;
  status: TenantStatus;
  settings?: Partial<TenantSettings>;
}
