import httpClient from '@/api/client'
import type { ApiResponse, ListParams, PaginatedResponse } from '@/types/api'

export interface PurchaseOrder {
  id: number
  tenant_id: number
  order_number: string
  vendor_id: number
  status: 'draft' | 'submitted' | 'approved' | 'received' | 'billed' | 'cancelled'
  subtotal: string
  tax_amount: string
  total_amount: string
  expected_delivery_date: string | null
  notes: string | null
  created_at: string
  updated_at: string
}

export interface PurchaseOrderLine {
  product_id: number
  quantity: string
  unit_cost: string
}

export interface CreatePurchaseOrderPayload {
  vendor_id: number
  lines: PurchaseOrderLine[]
  expected_delivery_date?: string
  notes?: string
}

/** Partial update for purchase order metadata only. Lines cannot be updated in-place — cancel and recreate the order to change line items. */
export type UpdatePurchaseOrderPayload = Partial<Omit<CreatePurchaseOrderPayload, 'lines'>>

export interface Vendor {
  id: number
  tenant_id: number
  name: string
  email: string | null
  phone: string | null
  address: string | null
  vendor_code: string | null
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface CreateVendorPayload {
  name: string
  email?: string
  phone?: string
  address?: string
  vendor_code?: string
  is_active?: boolean
}

export type UpdateVendorPayload = Partial<CreateVendorPayload>

export interface VendorBill {
  id: number
  tenant_id: number
  vendor_id: number
  purchase_order_id: number | null
  bill_number: string
  total_amount: string
  status: 'draft' | 'approved' | 'paid'
  due_date: string | null
  notes: string | null
  created_at: string
  updated_at: string
}

export interface CreateVendorBillPayload {
  vendor_id: number
  total_amount: string
  purchase_order_id?: number
  due_date?: string
  notes?: string
}

export interface GoodsReceiptLine {
  purchase_order_line_id: number
  quantity_received: string
}

const procurementApi = {
  // Purchase Orders
  listOrders: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<PurchaseOrder>>('/procurement/orders', { params }),

  getOrder: (id: number) =>
    httpClient.get<ApiResponse<PurchaseOrder>>(`/procurement/orders/${id}`),

  createOrder: (payload: CreatePurchaseOrderPayload) =>
    httpClient.post<ApiResponse<PurchaseOrder>>('/procurement/orders', payload),

  updateOrder: (id: number, payload: UpdatePurchaseOrderPayload) =>
    httpClient.put<ApiResponse<PurchaseOrder>>(`/procurement/orders/${id}`, payload),

  receiveGoods: (id: number, lines: GoodsReceiptLine[]) =>
    httpClient.post<ApiResponse<PurchaseOrder>>(`/procurement/orders/${id}/receive`, { lines }),

  threeWayMatch: (id: number) =>
    httpClient.get<ApiResponse<Record<string, unknown>>>(`/procurement/orders/${id}/three-way-match`),

  // Vendors
  listVendors: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<Vendor>>('/procurement/vendors', { params }),

  createVendor: (payload: CreateVendorPayload) =>
    httpClient.post<ApiResponse<Vendor>>('/procurement/vendors', payload),

  getVendor: (id: number) =>
    httpClient.get<ApiResponse<Vendor>>(`/procurement/vendors/${id}`),

  updateVendor: (id: number, payload: UpdateVendorPayload) =>
    httpClient.put<ApiResponse<Vendor>>(`/procurement/vendors/${id}`, payload),

  // Vendor Bills
  listVendorBills: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<VendorBill>>('/procurement/vendor-bills', { params }),

  getVendorBill: (id: number) =>
    httpClient.get<ApiResponse<VendorBill>>(`/procurement/vendor-bills/${id}`),

  createVendorBill: (payload: CreateVendorBillPayload) =>
    httpClient.post<ApiResponse<VendorBill>>('/procurement/vendor-bills', payload),
}

export default procurementApi
