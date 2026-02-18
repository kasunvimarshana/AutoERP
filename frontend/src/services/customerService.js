import apiClient from './api'

export const customerService = {
  /**
   * Get all customers
   */
  async getAll(page = 1, perPage = 15) {
    return await apiClient.get('/crm/customers', {
      params: { page, per_page: perPage }
    })
  },

  /**
   * Get customer by ID
   */
  async getById(id) {
    return await apiClient.get(`/crm/customers/${id}`)
  },

  /**
   * Create new customer
   */
  async create(customerData) {
    return await apiClient.post('/crm/customers', customerData)
  },

  /**
   * Update customer
   */
  async update(id, customerData) {
    return await apiClient.put(`/crm/customers/${id}`, customerData)
  },

  /**
   * Delete customer
   */
  async delete(id) {
    return await apiClient.delete(`/crm/customers/${id}`)
  },

  /**
   * Search customers
   */
  async search(query, page = 1, perPage = 15) {
    return await apiClient.get('/crm/customers/search', {
      params: { q: query, page, per_page: perPage }
    })
  }
}

export default customerService
