import { apiClient } from './client'
import type { PaginatedResponse } from '@/types/api'

// Customer interfaces
export interface Customer {
  id: number
  customer_code: string
  customer_name: string
  email: string
  phone?: string
  mobile?: string
  fax?: string
  website?: string
  tax_id?: string
  customer_tier: 'standard' | 'premium' | 'vip'
  payment_terms: string
  payment_term_days: number
  credit_limit: number
  outstanding_balance: number
  preferred_currency: string
  billing_address_line1?: string
  billing_address_line2?: string
  billing_city?: string
  billing_state?: string
  billing_country?: string
  billing_postal_code?: string
  shipping_address_line1?: string
  shipping_address_line2?: string
  shipping_city?: string
  shipping_state?: string
  shipping_country?: string
  shipping_postal_code?: string
  is_active: boolean
  notes?: string
  custom_fields?: Record<string, any>
  created_at: string
  updated_at: string
}

export interface CustomerFormData {
  customer_name: string
  email: string
  phone?: string
  mobile?: string
  fax?: string
  website?: string
  tax_id?: string
  customer_tier?: 'standard' | 'premium' | 'vip'
  payment_terms?: string
  payment_term_days?: number
  credit_limit?: number
  preferred_currency?: string
  billing_address_line1?: string
  billing_address_line2?: string
  billing_city?: string
  billing_state?: string
  billing_country?: string
  billing_postal_code?: string
  shipping_address_line1?: string
  shipping_address_line2?: string
  shipping_city?: string
  shipping_state?: string
  shipping_country?: string
  shipping_postal_code?: string
  notes?: string
}

export interface CustomerQueryParams {
  page?: number
  per_page?: number
  search?: string
  status?: 'active' | 'inactive'
  tier?: 'standard' | 'premium' | 'vip'
  sort_by?: string
  sort_order?: 'asc' | 'desc'
}

export interface CustomerStatistics {
  total_orders: number
  total_revenue: number
  average_order_value: number
  outstanding_balance: number
  available_credit: number
  credit_utilization: number
}

// Quotation interfaces
export interface Quotation {
  id: number
  quote_number: string
  customer_id: number
  customer_name?: string
  quote_date: string
  valid_until?: string
  status: 'draft' | 'sent' | 'accepted' | 'rejected' | 'expired' | 'converted'
  currency: string
  exchange_rate: number
  subtotal: number
  discount_amount: number
  tax_amount: number
  total_amount: number
  terms_and_conditions?: string
  notes?: string
  custom_fields?: Record<string, any>
  converted_to_order_id?: number
  converted_at?: string
  items: QuotationItem[]
  created_at: string
  updated_at: string
}

export interface QuotationItem {
  id: number
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

export interface QuotationFormData {
  customer_id: number
  quote_date: string
  valid_until?: string
  currency?: string
  terms_and_conditions?: string
  notes?: string
  items: Array<{
    product_id: number
    description?: string
    quantity: number
    unit_price: number
    discount_percent?: number
    tax_percent?: number
  }>
}

export interface QuotationQueryParams {
  page?: number
  per_page?: number
  search?: string
  status?: 'draft' | 'sent' | 'accepted' | 'rejected' | 'expired' | 'converted'
  customer_id?: number
  from_date?: string
  to_date?: string
  sort_by?: string
  sort_order?: 'asc' | 'desc'
}

export interface SalesOrder {
  id: number
  order_number: string
  customer_id: number
  customer_name?: string
  status: 'draft' | 'confirmed' | 'processing' | 'completed' | 'cancelled'
  subtotal: number
  tax_amount: number
  discount_amount: number
  total_amount: number
  currency: string
  order_date: string
  delivery_date?: string
  notes?: string
  items: SalesOrderItem[]
  created_at: string
  updated_at: string
}

export interface SalesOrderItem {
  id: number
  product_id: number
  product_name?: string
  product_sku?: string
  quantity: number
  unit_price: number
  discount_percent: number
  discount_amount: number
  tax_percent: number
  tax_amount: number
  line_total: number
}

export interface SalesOrderQueryParams {
  page?: number
  per_page?: number
  search?: string
  status?: string
  customer_id?: number
  order_by?: string
  order_direction?: 'asc' | 'desc'
  date_from?: string
  date_to?: string
}

export interface SalesOrderFormData {
  customer_id: number
  order_date: string
  delivery_date?: string
  currency?: string
  notes?: string
  items: Array<{
    product_id: number
    quantity: number
    unit_price: number
    discount_percent?: number
    tax_percent?: number
  }>
}

export const salesApi = {
  // Customer endpoints
  async getCustomers(params: CustomerQueryParams = {}): Promise<PaginatedResponse<Customer>> {
    const response = await apiClient.get('/sales/customers', { params })
    return response.data
  },

  async getCustomer(id: string | number): Promise<Customer> {
    const response = await apiClient.get(`/sales/customers/${id}`)
    return response.data.data
  },

  async createCustomer(data: CustomerFormData): Promise<Customer> {
    const response = await apiClient.post('/sales/customers', data)
    return response.data.data
  },

  async updateCustomer(id: string | number, data: Partial<CustomerFormData>): Promise<Customer> {
    const response = await apiClient.put(`/sales/customers/${id}`, data)
    return response.data.data
  },

  async deleteCustomer(id: string | number): Promise<void> {
    await apiClient.delete(`/sales/customers/${id}`)
  },

  async activateCustomer(id: string | number): Promise<Customer> {
    const response = await apiClient.post(`/sales/customers/${id}/activate`)
    return response.data.data
  },

  async deactivateCustomer(id: string | number): Promise<Customer> {
    const response = await apiClient.post(`/sales/customers/${id}/deactivate`)
    return response.data.data
  },

  async getCustomerStatistics(id: string | number): Promise<CustomerStatistics> {
    const response = await apiClient.get(`/sales/customers/${id}/statistics`)
    return response.data.data
  },

  // Quotation endpoints
  async getQuotations(params: QuotationQueryParams = {}): Promise<PaginatedResponse<Quotation>> {
    const response = await apiClient.get('/sales/quotations', { params })
    return response.data
  },

  async getQuotation(id: string | number): Promise<Quotation> {
    const response = await apiClient.get(`/sales/quotations/${id}`)
    return response.data.data
  },

  async createQuotation(data: QuotationFormData): Promise<Quotation> {
    const response = await apiClient.post('/sales/quotations', data)
    return response.data.data
  },

  async updateQuotation(id: string | number, data: Partial<QuotationFormData>): Promise<Quotation> {
    const response = await apiClient.put(`/sales/quotations/${id}`, data)
    return response.data.data
  },

  async deleteQuotation(id: string | number): Promise<void> {
    await apiClient.delete(`/sales/quotations/${id}`)
  },

  async sendQuotation(id: string | number): Promise<Quotation> {
    const response = await apiClient.post(`/sales/quotations/${id}/send`)
    return response.data.data
  },

  async acceptQuotation(id: string | number): Promise<Quotation> {
    const response = await apiClient.post(`/sales/quotations/${id}/accept`)
    return response.data.data
  },

  async rejectQuotation(id: string | number): Promise<Quotation> {
    const response = await apiClient.post(`/sales/quotations/${id}/reject`)
    return response.data.data
  },

  async convertQuotation(id: string | number): Promise<SalesOrder> {
    const response = await apiClient.post(`/sales/quotations/${id}/convert`)
    return response.data.data
  },

  // Sales Order endpoints
  async getOrders(params: SalesOrderQueryParams = {}): Promise<PaginatedResponse<SalesOrder>> {
    const response = await apiClient.get('/sales/orders', { params })
    return response.data
  },

  async getOrder(id: string | number): Promise<SalesOrder> {
    const response = await apiClient.get(`/sales/orders/${id}`)
    return response.data
  },

  async createOrder(data: SalesOrderFormData): Promise<SalesOrder> {
    const response = await apiClient.post('/sales/orders', data)
    return response.data
  },

  async updateOrder(id: string | number, data: Partial<SalesOrderFormData>): Promise<SalesOrder> {
    const response = await apiClient.put(`/sales/orders/${id}`, data)
    return response.data
  },

  async deleteOrder(id: string | number): Promise<void> {
    await apiClient.delete(`/sales/orders/${id}`)
  },

  async confirmOrder(id: string | number): Promise<SalesOrder> {
    const response = await apiClient.post(`/sales/orders/${id}/confirm`)
    return response.data
  },

  async cancelOrder(id: string | number): Promise<SalesOrder> {
    const response = await apiClient.post(`/sales/orders/${id}/cancel`)
    return response.data
  },
}

export default salesApi
