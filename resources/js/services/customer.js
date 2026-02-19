import api from './api';

/**
 * Customer Service
 * 
 * Handles all API calls related to customers
 */

const customerService = {
  /**
   * Get all customers with optional filters
   * @param {Object} params - Query parameters (paginate, per_page, etc.)
   * @returns {Promise}
   */
  getCustomers(params = {}) {
    return api.get('/customers', { params });
  },

  /**
   * Get a single customer by ID
   * @param {Number} id - Customer ID
   * @returns {Promise}
   */
  getCustomer(id) {
    return api.get(`/customers/${id}`);
  },

  /**
   * Create a new customer
   * @param {Object} data - Customer data
   * @returns {Promise}
   */
  createCustomer(data) {
    return api.post('/customers', data);
  },

  /**
   * Update an existing customer
   * @param {Number} id - Customer ID
   * @param {Object} data - Updated customer data
   * @returns {Promise}
   */
  updateCustomer(id, data) {
    return api.put(`/customers/${id}`, data);
  },

  /**
   * Delete a customer
   * @param {Number} id - Customer ID
   * @returns {Promise}
   */
  deleteCustomer(id) {
    return api.delete(`/customers/${id}`);
  },

  /**
   * Search customers
   * @param {String} query - Search query
   * @param {Object} params - Additional query parameters
   * @returns {Promise}
   */
  searchCustomers(query, params = {}) {
    return api.get('/customers/search', {
      params: { q: query, ...params }
    });
  },

  /**
   * Get customer with their vehicles
   * @param {Number} id - Customer ID
   * @returns {Promise}
   */
  getCustomerWithVehicles(id) {
    return api.get(`/customers/${id}/vehicles`);
  },

  /**
   * Get customer statistics
   * @param {Number} id - Customer ID
   * @returns {Promise}
   */
  getCustomerStatistics(id) {
    return api.get(`/customers/${id}/statistics`);
  },
};

export default customerService;
