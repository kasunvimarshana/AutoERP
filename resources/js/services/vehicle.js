import api from './api';

/**
 * Vehicle Service
 * 
 * Handles all API calls related to vehicles
 */

const vehicleService = {
  /**
   * Get all vehicles with optional filters
   * @param {Object} params - Query parameters (paginate, per_page, etc.)
   * @returns {Promise}
   */
  getVehicles(params = {}) {
    return api.get('/vehicles', { params });
  },

  /**
   * Get a single vehicle by ID
   * @param {Number} id - Vehicle ID
   * @returns {Promise}
   */
  getVehicle(id) {
    return api.get(`/vehicles/${id}`);
  },

  /**
   * Create a new vehicle
   * @param {Object} data - Vehicle data
   * @returns {Promise}
   */
  createVehicle(data) {
    return api.post('/vehicles', data);
  },

  /**
   * Update an existing vehicle
   * @param {Number} id - Vehicle ID
   * @param {Object} data - Updated vehicle data
   * @returns {Promise}
   */
  updateVehicle(id, data) {
    return api.put(`/vehicles/${id}`, data);
  },

  /**
   * Delete a vehicle
   * @param {Number} id - Vehicle ID
   * @returns {Promise}
   */
  deleteVehicle(id) {
    return api.delete(`/vehicles/${id}`);
  },

  /**
   * Search vehicles
   * @param {String} query - Search query
   * @param {Object} params - Additional query parameters
   * @returns {Promise}
   */
  searchVehicles(query, params = {}) {
    return api.get('/vehicles/search', {
      params: { q: query, ...params }
    });
  },

  /**
   * Get vehicles by customer ID
   * @param {Number} customerId - Customer ID
   * @returns {Promise}
   */
  getVehiclesByCustomer(customerId) {
    return api.get(`/customers/${customerId}/vehicles`);
  },

  /**
   * Get vehicle with all relations
   * @param {Number} id - Vehicle ID
   * @returns {Promise}
   */
  getVehicleWithRelations(id) {
    return api.get(`/vehicles/${id}/with-relations`);
  },

  /**
   * Get vehicles due for service
   * @param {Object} params - Query parameters
   * @returns {Promise}
   */
  getDueForService(params = {}) {
    return api.get('/vehicles/due-for-service', { params });
  },

  /**
   * Get vehicles with expiring insurance
   * @param {Object} params - Query parameters
   * @returns {Promise}
   */
  getExpiringInsurance(params = {}) {
    return api.get('/vehicles/expiring-insurance', { params });
  },

  /**
   * Update vehicle mileage
   * @param {Number} id - Vehicle ID
   * @param {Number} mileage - New mileage value
   * @returns {Promise}
   */
  updateMileage(id, mileage) {
    return api.patch(`/vehicles/${id}/mileage`, { mileage });
  },

  /**
   * Transfer vehicle ownership
   * @param {Number} id - Vehicle ID
   * @param {Number} newCustomerId - New customer ID
   * @returns {Promise}
   */
  transferOwnership(id, newCustomerId) {
    return api.post(`/vehicles/${id}/transfer-ownership`, {
      new_customer_id: newCustomerId
    });
  },

  /**
   * Get vehicle service statistics
   * @param {Number} id - Vehicle ID
   * @returns {Promise}
   */
  getVehicleStatistics(id) {
    return api.get(`/vehicles/${id}/statistics`);
  },
};

export default vehicleService;
