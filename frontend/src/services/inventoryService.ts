import apiClient from './api'
import type { InventoryItem, PurchaseOrder } from '@/types/inventory'

export const inventoryService = {
  /**
   * Get all inventory items with optional filters
   */
  async getInventoryItems(filters?: Record<string, any>) {
    const response = await apiClient.get('/inventory-items', { params: filters })
    return response.data
  },

  /**
   * Get a single inventory item by ID
   */
  async getInventoryItem(id: number) {
    const response = await apiClient.get(`/inventory-items/${id}`)
    return response.data
  },

  /**
   * Create a new inventory item
   */
  async createInventoryItem(data: Partial<InventoryItem>) {
    const response = await apiClient.post('/inventory-items', data)
    return response.data
  },

  /**
   * Update an existing inventory item
   */
  async updateInventoryItem(id: number, data: Partial<InventoryItem>) {
    const response = await apiClient.put(`/inventory-items/${id}`, data)
    return response.data
  },

  /**
   * Delete an inventory item
   */
  async deleteInventoryItem(id: number) {
    const response = await apiClient.delete(`/inventory-items/${id}`)
    return response.data
  },

  /**
   * Adjust stock for an inventory item
   */
  async adjustStock(id: number, quantity: number, reason: string) {
    const response = await apiClient.post(`/inventory-items/${id}/adjust-stock`, {
      quantity,
      reason,
    })
    return response.data
  },
}

export const purchaseOrderService = {
  /**
   * Get all purchase orders with optional filters
   */
  async getPurchaseOrders(filters?: Record<string, any>) {
    const response = await apiClient.get('/purchase-orders', { params: filters })
    return response.data
  },

  /**
   * Get a single purchase order by ID
   */
  async getPurchaseOrder(id: number) {
    const response = await apiClient.get(`/purchase-orders/${id}`)
    return response.data
  },

  /**
   * Create a new purchase order
   */
  async createPurchaseOrder(data: Partial<PurchaseOrder>) {
    const response = await apiClient.post('/purchase-orders', data)
    return response.data
  },

  /**
   * Update an existing purchase order
   */
  async updatePurchaseOrder(id: number, data: Partial<PurchaseOrder>) {
    const response = await apiClient.put(`/purchase-orders/${id}`, data)
    return response.data
  },

  /**
   * Delete a purchase order
   */
  async deletePurchaseOrder(id: number) {
    const response = await apiClient.delete(`/purchase-orders/${id}`)
    return response.data
  },

  /**
   * Approve a purchase order
   */
  async approvePurchaseOrder(id: number) {
    const response = await apiClient.post(`/purchase-orders/${id}/approve`)
    return response.data
  },

  /**
   * Receive items from a purchase order
   */
  async receivePurchaseOrder(id: number, items: Array<{ id: number; quantity: number }>) {
    const response = await apiClient.post(`/purchase-orders/${id}/receive`, { items })
    return response.data
  },
}
