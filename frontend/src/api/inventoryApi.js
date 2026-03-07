import api from './axios'

export const inventoryApi = {
  /**
   * Fetch a paginated / filtered list of inventory items.
   * @param {Object} params - Query parameters (page, per_page, product_id, location, etc.)
   */
  getInventoryItems: (params = {}) => api.get('/inventory', { params }),

  /**
   * Fetch a single inventory record by ID.
   * @param {number|string} id
   */
  getInventoryById: (id) => api.get(`/inventory/${id}`),

  /**
   * Create a new inventory record.
   * @param {Object} data - Inventory payload (product_id, quantity, location, etc.)
   */
  createInventory: (data) => api.post('/inventory', data),

  /**
   * Update an existing inventory record.
   * @param {number|string} id
   * @param {Object} data - Fields to update
   */
  updateInventory: (id, data) => api.put(`/inventory/${id}`, data),

  /**
   * Delete an inventory record by ID.
   * @param {number|string} id
   */
  deleteInventory: (id) => api.delete(`/inventory/${id}`),

  /**
   * Adjust the quantity of an inventory item (increment / decrement / set).
   * @param {number|string} id
   * @param {Object} data - Adjustment payload (adjustment_type, quantity, reason, etc.)
   */
  adjustInventory: (id, data) => api.post(`/inventory/${id}/adjust`, data),
}
