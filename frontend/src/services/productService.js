import apiClient from './api'

/**
 * Product Service
 * Handles all product-related API calls
 */
export const productService = {
  /**
   * Get all products with pagination
   * @param {Object} params - Query parameters (per_page, page)
   * @returns {Promise}
   */
  getProducts(params = {}) {
    return apiClient.get('/inventory/products', { params })
  },

  /**
   * Get a single product by ID
   * @param {number} id - Product ID
   * @returns {Promise}
   */
  getProduct(id) {
    return apiClient.get(`/inventory/products/${id}`)
  },

  /**
   * Create a new product
   * @param {Object} data - Product data
   * @returns {Promise}
   */
  createProduct(data) {
    return apiClient.post('/inventory/products', data)
  },

  /**
   * Update an existing product
   * @param {number} id - Product ID
   * @param {Object} data - Updated product data
   * @returns {Promise}
   */
  updateProduct(id, data) {
    return apiClient.put(`/inventory/products/${id}`, data)
  },

  /**
   * Delete a product
   * @param {number} id - Product ID
   * @returns {Promise}
   */
  deleteProduct(id) {
    return apiClient.delete(`/inventory/products/${id}`)
  },

  /**
   * Search products
   * @param {string} query - Search query
   * @returns {Promise}
   */
  searchProducts(query) {
    return apiClient.get('/inventory/products/search', {
      params: { q: query }
    })
  },

  /**
   * Get products below reorder level
   * @returns {Promise}
   */
  getBelowReorderLevel() {
    return apiClient.get('/inventory/products/below-reorder-level')
  },

  /**
   * Get stock history for a product
   * @param {number} id - Product ID
   * @returns {Promise}
   */
  getStockHistory(id) {
    return apiClient.get(`/inventory/products/${id}/stock-history`)
  }
}
