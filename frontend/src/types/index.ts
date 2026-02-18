// Core Types
export interface User {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  role: string;
  permissions: string[];
  tenant_id: number;
  created_at: string;
  updated_at: string;
}

export interface Tenant {
  id: number;
  name: string;
  domain: string;
  slug: string;
  logo?: string;
  settings: TenantSettings;
  subscription: Subscription;
  created_at: string;
  updated_at: string;
}

export interface TenantSettings {
  timezone: string;
  date_format: string;
  currency: string;
  language: string;
  features: string[];
}

export interface Subscription {
  plan: string;
  status: 'active' | 'trial' | 'expired' | 'cancelled';
  trial_ends_at?: string;
  expires_at?: string;
}

// Authentication
export interface LoginCredentials {
  email: string;
  password: string;
  tenant_slug?: string;
}

export interface AuthResponse {
  access_token: string;
  token_type: string;
  expires_in: number;
  user: User;
  tenant: Tenant;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  tenant_name: string;
  tenant_slug: string;
}

// API
export interface ApiResponse<T = any> {
  data: T;
  message?: string;
  meta?: PaginationMeta;
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
  status: number;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: PaginationMeta;
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
}

// Metadata-driven UI
export interface ModuleMetadata {
  name: string;
  label: string;
  icon: string;
  route: string;
  permissions: string[];
  fields: FieldMetadata[];
  actions: ActionMetadata[];
  views: ViewMetadata[];
}

export interface FieldMetadata {
  name: string;
  label: string;
  type: FieldType;
  required?: boolean;
  readonly?: boolean;
  default?: any;
  options?: SelectOption[];
  validation?: ValidationRule[];
  depends_on?: string[];
  visible_if?: VisibilityCondition;
  metadata?: Record<string, any>;
}

export type FieldType =
  | 'text'
  | 'email'
  | 'password'
  | 'number'
  | 'decimal'
  | 'date'
  | 'datetime'
  | 'time'
  | 'boolean'
  | 'select'
  | 'multiselect'
  | 'radio'
  | 'checkbox'
  | 'textarea'
  | 'wysiwyg'
  | 'file'
  | 'image'
  | 'relation'
  | 'json';

export interface SelectOption {
  value: any;
  label: string;
  disabled?: boolean;
  metadata?: Record<string, any>;
}

export interface ValidationRule {
  rule: string;
  params?: any[];
  message?: string;
}

export interface VisibilityCondition {
  field: string;
  operator: 'equals' | 'not_equals' | 'in' | 'not_in' | 'greater_than' | 'less_than';
  value: any;
}

export interface ActionMetadata {
  name: string;
  label: string;
  icon?: string;
  type: 'button' | 'link' | 'dropdown';
  variant?: 'primary' | 'secondary' | 'danger' | 'success';
  permissions?: string[];
  condition?: ActionCondition;
  endpoint?: string;
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
  confirm?: ConfirmDialog;
}

export interface ActionCondition {
  field: string;
  operator: string;
  value: any;
}

export interface ConfirmDialog {
  title: string;
  message: string;
  confirm_text?: string;
  cancel_text?: string;
}

export interface ViewMetadata {
  name: string;
  type: 'list' | 'form' | 'detail' | 'dashboard';
  layout: 'table' | 'grid' | 'kanban' | 'calendar';
  columns?: ColumnMetadata[];
  filters?: FilterMetadata[];
  sorts?: SortMetadata[];
  groups?: GroupMetadata[];
}

export interface ColumnMetadata {
  field: string;
  label: string;
  width?: number | string;
  sortable?: boolean;
  filterable?: boolean;
  align?: 'left' | 'center' | 'right';
  format?: ColumnFormat;
}

export interface ColumnFormat {
  type: 'date' | 'number' | 'currency' | 'percentage' | 'boolean' | 'badge';
  options?: Record<string, any>;
}

export interface FilterMetadata {
  field: string;
  label: string;
  type: 'text' | 'select' | 'date' | 'daterange' | 'number';
  options?: SelectOption[];
}

export interface SortMetadata {
  field: string;
  label: string;
  default?: 'asc' | 'desc';
}

export interface GroupMetadata {
  field: string;
  label: string;
}

// Navigation
export interface NavigationItem {
  name: string;
  label: string;
  icon?: string;
  route?: string;
  children?: NavigationItem[];
  permissions?: string[];
  badge?: {
    text: string;
    variant: 'primary' | 'success' | 'warning' | 'danger';
  };
}

// Notifications
export interface Notification {
  id: string;
  type: 'success' | 'error' | 'warning' | 'info';
  title: string;
  message: string;
  duration?: number;
  action?: {
    label: string;
    callback: () => void;
  };
}

// Form
export interface FormField {
  name: string;
  value: any;
  error?: string;
  touched?: boolean;
  dirty?: boolean;
}

export interface FormState {
  fields: Record<string, FormField>;
  isValid: boolean;
  isSubmitting: boolean;
  errors: Record<string, string>;
}
