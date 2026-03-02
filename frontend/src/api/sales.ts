import httpClient from '@/api/client'
import type { ApiResponse, ListParams, PaginatedResponse } from '@/types/api'

export interface SalesOrder {
  id: number
  tenant_id: number
  order_number: string
  customer_id: number
  status: 'draft' | 'confirmed' | 'delivered' | 'invoiced' | 'paid' | 'cancelled'
  subtotal: string
  tax_amount: string
  discount_amount: string
  total_amount: string
  notes: string | null
  created_at: string
  updated_at: string
}

export interface SalesOrderLine {
  product_id: number
  quantity: string
  unit_price: string
  discount_amount: string
  tax_rate: string
}

export interface SalesDelivery {
  id: number
  tenant_id: number
  sales_order_id: number
  delivery_number: string
  status: 'pending' | 'shipped' | 'delivered'
  shipped_at: string | null
  delivered_at: string | null
  created_at: string
  updated_at: string
}

export interface SalesInvoice {
  id: number
  tenant_id: number
  sales_order_id: number
  invoice_number: string
  total_amount: string
  status: 'draft' | 'sent' | 'paid' | 'overdue'
  due_date: string | null
  paid_at: string | null
  created_at: string
  updated_at: string
}

export interface Customer {
  id: number
  tenant_id: number
  name: string
  email: string | null
  phone: string | null
  created_at: string
  updated_at: string
}

export interface CreateSalesOrderPayload {
  customer_id: number
  lines: SalesOrderLine[]
  notes?: string
}

export interface CreateDeliveryPayload {
  notes?: string
}

export interface CreateInvoicePayload {
  due_date?: string
  notes?: string
}

export interface SalesReturnLine {
  product_id: number
  warehouse_id: number
  uom_id: number
  quantity: string
  unit_cost: string
  batch_number?: string
  lot_number?: string
  notes?: string
}

export interface CreateReturnPayload {
  lines: SalesReturnLine[]
}

export interface SalesReturnResult {
  batch_number: string | null
  quantity_returned: string
  transaction_id: number
}

const salesApi = {
  // Sales Orders
  listOrders: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<SalesOrder>>('/sales/orders', { params }),

  getOrder: (id: number) =>
    httpClient.get<ApiResponse<SalesOrder>>(`/sales/orders/${id}`),

  createOrder: (payload: CreateSalesOrderPayload) =>
    httpClient.post<ApiResponse<SalesOrder>>('/sales/orders', payload),

  confirmOrder: (id: number) =>
    httpClient.post<ApiResponse<SalesOrder>>(`/sales/orders/${id}/confirm`),

  cancelOrder: (id: number) =>
    httpClient.post<ApiResponse<SalesOrder>>(`/sales/orders/${id}/cancel`),

  // Customers
  listCustomers: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<Customer>>('/sales/customers', { params }),

  // Deliveries
  createDelivery: (orderId: number, payload: CreateDeliveryPayload) =>
    httpClient.post<ApiResponse<SalesDelivery>>(`/sales/orders/${orderId}/deliveries`, payload),

  listDeliveries: (orderId: number, params?: ListParams) =>
    httpClient.get<PaginatedResponse<SalesDelivery>>(`/sales/orders/${orderId}/deliveries`, { params }),

  // Invoices
  createInvoice: (orderId: number, payload: CreateInvoicePayload) =>
    httpClient.post<ApiResponse<SalesInvoice>>(`/sales/orders/${orderId}/invoices`, payload),

  listInvoices: (orderId: number, params?: ListParams) =>
    httpClient.get<PaginatedResponse<SalesInvoice>>(`/sales/orders/${orderId}/invoices`, { params }),

  getInvoice: (id: number) =>
    httpClient.get<ApiResponse<SalesInvoice>>(`/sales/invoices/${id}`),

  // Returns
  createReturn: (orderId: number, payload: CreateReturnPayload) =>
    httpClient.post<ApiResponse<SalesReturnResult[]>>(`/sales/orders/${orderId}/returns`, payload),
}

export default salesApi
