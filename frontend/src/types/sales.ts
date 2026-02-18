// Sales Module Types

export interface Customer {
  id: number
  name: string
  code: string
  email?: string
  phone?: string
  mobile?: string
  address?: Address
  contact_person?: string
  payment_terms?: string
  credit_limit?: number
  current_balance?: number
  is_active: boolean
  tax_number?: string
  notes?: string
  created_at: string
  updated_at: string
}

export interface SalesOrder {
  id: number
  order_number: string
  customer_id: number
  customer?: Customer
  customer_name?: string
  status: SalesOrderStatus
  subtotal: number
  tax_amount: number
  discount_amount: number
  shipping_amount?: number
  total_amount: number
  currency: string
  order_date: string
  delivery_date?: string
  shipping_address?: Address
  billing_address?: Address
  payment_terms?: string
  notes?: string
  items: SalesOrderItem[]
  created_at: string
  updated_at: string
  created_by?: number
  updated_by?: number
}

export type SalesOrderStatus =
  | 'draft'
  | 'confirmed'
  | 'processing'
  | 'shipped'
  | 'delivered'
  | 'completed'
  | 'cancelled'

export interface SalesOrderItem {
  id: number
  sales_order_id: number
  product_id: number
  product_name?: string
  product_sku?: string
  description?: string
  quantity: number
  unit_price: number
  discount_percent: number
  discount_amount: number
  tax_percent: number
  tax_amount: number
  line_total: number
  notes?: string
}

export interface Quotation {
  id: number
  quotation_number: string
  customer_id: number
  customer?: Customer
  customer_name?: string
  status: QuotationStatus
  valid_until: string
  subtotal: number
  tax_amount: number
  discount_amount: number
  total_amount: number
  currency: string
  quotation_date: string
  notes?: string
  items: QuotationItem[]
  created_at: string
  updated_at: string
  converted_to_order_id?: number
}

export type QuotationStatus =
  | 'draft'
  | 'sent'
  | 'accepted'
  | 'rejected'
  | 'expired'
  | 'converted'

export interface QuotationItem {
  id: number
  quotation_id: number
  product_id: number
  product_name?: string
  product_sku?: string
  description?: string
  quantity: number
  unit_price: number
  discount_percent: number
  discount_amount: number
  tax_percent: number
  tax_amount: number
  line_total: number
}

export interface Address {
  street?: string
  city?: string
  state?: string
  postal_code?: string
  country?: string
}

// Query Parameters
export interface SalesOrderQueryParams {
  page?: number
  per_page?: number
  search?: string
  status?: SalesOrderStatus
  customer_id?: number
  order_by?: string
  order_direction?: 'asc' | 'desc'
  date_from?: string
  date_to?: string
}

export interface CustomerQueryParams {
  page?: number
  per_page?: number
  search?: string
  is_active?: boolean
}

export interface QuotationQueryParams {
  page?: number
  per_page?: number
  search?: string
  status?: QuotationStatus
  customer_id?: number
  date_from?: string
  date_to?: string
}

// Form Data
export interface SalesOrderFormData {
  customer_id: number
  order_date: string
  delivery_date?: string
  currency?: string
  shipping_address?: Address
  billing_address?: Address
  payment_terms?: string
  notes?: string
  items: Array<{
    product_id: number
    quantity: number
    unit_price: number
    discount_percent?: number
    tax_percent?: number
    notes?: string
  }>
}

export interface CustomerFormData {
  name: string
  code?: string
  email?: string
  phone?: string
  mobile?: string
  address?: Address
  contact_person?: string
  payment_terms?: string
  credit_limit?: number
  is_active?: boolean
  tax_number?: string
  notes?: string
}

export interface QuotationFormData {
  customer_id: number
  quotation_date: string
  valid_until: string
  currency?: string
  notes?: string
  items: Array<{
    product_id: number
    quantity: number
    unit_price: number
    discount_percent?: number
    tax_percent?: number
    description?: string
  }>
}
