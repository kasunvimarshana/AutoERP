import { apiClient } from './client'
import type { PaginatedResponse } from '@/types/api'

export interface Supplier {
  id: number
  name: string
  code: string
  email?: string
  phone?: string
  address?: string
  city?: string
  state?: string
  country?: string
  postal_code?: string
  contact_person?: string
  payment_terms?: string
  credit_limit?: number
  is_active: boolean
  notes?: string
  created_at: string
  updated_at: string
}

export interface PurchaseOrder {
  id: number
  po_number: string
  supplier_id: number
  supplier_name?: string
  status: 'draft' | 'sent' | 'confirmed' | 'received' | 'cancelled'
  order_date: string
  expected_delivery_date?: string
  subtotal: number
  tax_amount: number
  discount_amount: number
  total_amount: number
  currency: string
  notes?: string
  items: PurchaseOrderItem[]
  receipts?: GoodsReceipt[]
  created_at: string
  updated_at: string
}

export interface PurchaseOrderItem {
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
  quantity_received: number
}

export interface GoodsReceipt {
  id: number
  receipt_number: string
  purchase_order_id: number
  po_number?: string
  receipt_date: string
  status: 'pending' | 'inspected' | 'accepted' | 'rejected'
  warehouse_id?: number
  warehouse_name?: string
  notes?: string
  items: GoodsReceiptItem[]
  created_at: string
  updated_at: string
}

export interface GoodsReceiptItem {
  id: number
  purchase_order_item_id: number
  product_id: number
  product_name?: string
  quantity_received: number
  quantity_accepted: number
  quantity_rejected: number
  notes?: string
}

export interface SupplierQueryParams {
  page?: number
  per_page?: number
  search?: string
  is_active?: boolean
}

export interface PurchaseOrderQueryParams {
  page?: number
  per_page?: number
  search?: string
  status?: string
  supplier_id?: number
  date_from?: string
  date_to?: string
}

export interface GoodsReceiptQueryParams {
  page?: number
  per_page?: number
  search?: string
  status?: string
  purchase_order_id?: number
  warehouse_id?: number
}

export interface PurchaseOrderFormData {
  supplier_id: number
  order_date: string
  expected_delivery_date?: string
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

export interface GoodsReceiptFormData {
  purchase_order_id: number
  receipt_date: string
  warehouse_id?: number
  notes?: string
  items: Array<{
    purchase_order_item_id: number
    product_id: number
    quantity_received: number
    quantity_accepted: number
    quantity_rejected: number
    notes?: string
  }>
}

export const purchasingApi = {
  // Supplier endpoints
  async getSuppliers(params: SupplierQueryParams = {}): Promise<PaginatedResponse<Supplier>> {
    const response = await apiClient.get('/purchasing/suppliers', { params })
    return response.data
  },

  async getSupplier(id: string | number): Promise<Supplier> {
    const response = await apiClient.get(`/purchasing/suppliers/${id}`)
    return response.data
  },

  async createSupplier(data: Partial<Supplier>): Promise<Supplier> {
    const response = await apiClient.post('/purchasing/suppliers', data)
    return response.data
  },

  async updateSupplier(id: string | number, data: Partial<Supplier>): Promise<Supplier> {
    const response = await apiClient.put(`/purchasing/suppliers/${id}`, data)
    return response.data
  },

  async deleteSupplier(id: string | number): Promise<void> {
    await apiClient.delete(`/purchasing/suppliers/${id}`)
  },

  async activateSupplier(id: string | number): Promise<Supplier> {
    const response = await apiClient.post(`/purchasing/suppliers/${id}/activate`)
    return response.data
  },

  async deactivateSupplier(id: string | number): Promise<Supplier> {
    const response = await apiClient.post(`/purchasing/suppliers/${id}/deactivate`)
    return response.data
  },

  // Purchase Order endpoints
  async getPurchaseOrders(params: PurchaseOrderQueryParams = {}): Promise<PaginatedResponse<PurchaseOrder>> {
    const response = await apiClient.get('/purchasing/purchase-orders', { params })
    return response.data
  },

  async getPurchaseOrder(id: string | number): Promise<PurchaseOrder> {
    const response = await apiClient.get(`/purchasing/purchase-orders/${id}`)
    return response.data
  },

  async createPurchaseOrder(data: PurchaseOrderFormData): Promise<PurchaseOrder> {
    const response = await apiClient.post('/purchasing/purchase-orders', data)
    return response.data
  },

  async updatePurchaseOrder(id: string | number, data: Partial<PurchaseOrderFormData>): Promise<PurchaseOrder> {
    const response = await apiClient.put(`/purchasing/purchase-orders/${id}`, data)
    return response.data
  },

  async deletePurchaseOrder(id: string | number): Promise<void> {
    await apiClient.delete(`/purchasing/purchase-orders/${id}`)
  },

  async sendPurchaseOrder(id: string | number): Promise<PurchaseOrder> {
    const response = await apiClient.post(`/purchasing/purchase-orders/${id}/send`)
    return response.data
  },

  async confirmPurchaseOrder(id: string | number): Promise<PurchaseOrder> {
    const response = await apiClient.post(`/purchasing/purchase-orders/${id}/confirm`)
    return response.data
  },

  async cancelPurchaseOrder(id: string | number): Promise<PurchaseOrder> {
    const response = await apiClient.post(`/purchasing/purchase-orders/${id}/cancel`)
    return response.data
  },

  // Goods Receipt endpoints
  async getGoodsReceipts(params: GoodsReceiptQueryParams = {}): Promise<PaginatedResponse<GoodsReceipt>> {
    const response = await apiClient.get('/purchasing/goods-receipts', { params })
    return response.data
  },

  async getGoodsReceipt(id: string | number): Promise<GoodsReceipt> {
    const response = await apiClient.get(`/purchasing/goods-receipts/${id}`)
    return response.data
  },

  async createGoodsReceipt(data: GoodsReceiptFormData): Promise<GoodsReceipt> {
    const response = await apiClient.post('/purchasing/goods-receipts', data)
    return response.data
  },

  async updateGoodsReceipt(id: string | number, data: Partial<GoodsReceiptFormData>): Promise<GoodsReceipt> {
    const response = await apiClient.put(`/purchasing/goods-receipts/${id}`, data)
    return response.data
  },

  async deleteGoodsReceipt(id: string | number): Promise<void> {
    await apiClient.delete(`/purchasing/goods-receipts/${id}`)
  },

  async inspectGoodsReceipt(id: string | number): Promise<GoodsReceipt> {
    const response = await apiClient.post(`/purchasing/goods-receipts/${id}/inspect`)
    return response.data
  },

  async acceptGoodsReceipt(id: string | number): Promise<GoodsReceipt> {
    const response = await apiClient.post(`/purchasing/goods-receipts/${id}/accept`)
    return response.data
  },

  async rejectGoodsReceipt(id: string | number, reason?: string): Promise<GoodsReceipt> {
    const response = await apiClient.post(`/purchasing/goods-receipts/${id}/reject`, { reason })
    return response.data
  },
}

export default purchasingApi
