import api from './axios'

export const ordersApi = {
  /**
   * Fetch a paginated / filtered list of orders.
   * @param {Object} params - Query parameters (page, per_page, status, customer_id, etc.)
   */
  getOrders: (params = {}) => api.get('/orders', { params }),

  /**
   * Fetch a single order by ID.
   * @param {number|string} id
   */
  getOrderById: (id) => api.get(`/orders/${id}`),

  /**
   * Create a new order.
   * @param {Object} data - Order payload (customer_id, items, shipping_address, etc.)
   */
  createOrder: (data) => api.post('/orders', data),

  /**
   * Update an existing order.
   * @param {number|string} id
   * @param {Object} data - Fields to update (status, shipping_address, items, etc.)
   */
  updateOrder: (id, data) => api.put(`/orders/${id}`, data),

  /**
   * Cancel an order by ID.
   * @param {number|string} id
   * @param {Object} data - Optional cancellation payload (reason, etc.)
   */
  cancelOrder: (id, data = {}) => api.post(`/orders/${id}/cancel`, data),
}
