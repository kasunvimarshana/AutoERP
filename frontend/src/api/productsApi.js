import api from './axios'

export const productsApi = {
  /**
   * Fetch a paginated / filtered list of products.
   * @param {Object} params - Query parameters (page, per_page, search, category, etc.)
   */
  getProducts: (params = {}) => api.get('/products', { params }),

  /**
   * Fetch a single product by ID.
   * @param {number|string} id
   */
  getProductById: (id) => api.get(`/products/${id}`),

  /**
   * Create a new product.
   * @param {Object} data - Product payload (name, sku, description, price, category_id, etc.)
   */
  createProduct: (data) => api.post('/products', data),

  /**
   * Update an existing product.
   * @param {number|string} id
   * @param {Object} data - Fields to update
   */
  updateProduct: (id, data) => api.put(`/products/${id}`, data),

  /**
   * Delete a product by ID.
   * @param {number|string} id
   */
  deleteProduct: (id) => api.delete(`/products/${id}`),
}
