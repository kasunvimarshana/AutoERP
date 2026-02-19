import api from './api';

/**
 * Service Record Service
 * 
 * Handles all API calls related to vehicle service records
 */

const serviceRecordService = {
  /**
   * Get all service records with optional filters
   * @param {Object} params - Query parameters (paginate, per_page, etc.)
   * @returns {Promise}
   */
  getServiceRecords(params = {}) {
    return api.get('/service-records', { params });
  },

  /**
   * Get a single service record by ID
   * @param {Number} id - Service record ID
   * @returns {Promise}
   */
  getServiceRecord(id) {
    return api.get(`/service-records/${id}`);
  },

  /**
   * Create a new service record
   * @param {Object} data - Service record data
   * @returns {Promise}
   */
  createServiceRecord(data) {
    return api.post('/service-records', data);
  },

  /**
   * Update an existing service record
   * @param {Number} id - Service record ID
   * @param {Object} data - Updated service record data
   * @returns {Promise}
   */
  updateServiceRecord(id, data) {
    return api.put(`/service-records/${id}`, data);
  },

  /**
   * Delete a service record
   * @param {Number} id - Service record ID
   * @returns {Promise}
   */
  deleteServiceRecord(id) {
    return api.delete(`/service-records/${id}`);
  },

  /**
   * Search service records
   * @param {String} query - Search query
   * @param {Object} params - Additional query parameters
   * @returns {Promise}
   */
  searchServiceRecords(query, params = {}) {
    return api.get('/service-records/search', {
      params: { q: query, ...params }
    });
  },

  /**
   * Get service record with all relations
   * @param {Number} id - Service record ID
   * @returns {Promise}
   */
  getServiceRecordWithRelations(id) {
    return api.get(`/service-records/${id}/with-relations`);
  },

  /**
   * Get service records by vehicle ID
   * @param {Number} vehicleId - Vehicle ID
   * @returns {Promise}
   */
  getServiceRecordsByVehicle(vehicleId) {
    return api.get(`/vehicles/${vehicleId}/service-records`);
  },

  /**
   * Get service records by customer ID
   * @param {Number} customerId - Customer ID
   * @returns {Promise}
   */
  getServiceRecordsByCustomer(customerId) {
    return api.get(`/customers/${customerId}/service-records`);
  },

  /**
   * Get pending service records
   * @param {Object} params - Query parameters
   * @returns {Promise}
   */
  getPendingRecords(params = {}) {
    return api.get('/service-records/pending', { params });
  },

  /**
   * Get in-progress service records
   * @param {Object} params - Query parameters
   * @returns {Promise}
   */
  getInProgressRecords(params = {}) {
    return api.get('/service-records/in-progress', { params });
  },

  /**
   * Get service records by branch
   * @param {String} branch - Branch name
   * @param {Object} params - Query parameters
   * @returns {Promise}
   */
  getRecordsByBranch(branch, params = {}) {
    return api.get('/service-records/by-branch', {
      params: { branch, ...params }
    });
  },

  /**
   * Get service records by service type
   * @param {String} serviceType - Service type
   * @param {Object} params - Query parameters
   * @returns {Promise}
   */
  getRecordsByServiceType(serviceType, params = {}) {
    return api.get('/service-records/by-service-type', {
      params: { service_type: serviceType, ...params }
    });
  },

  /**
   * Get service records by status
   * @param {String} status - Service status
   * @param {Object} params - Query parameters
   * @returns {Promise}
   */
  getRecordsByStatus(status, params = {}) {
    return api.get('/service-records/by-status', {
      params: { status, ...params }
    });
  },

  /**
   * Get service records by date range
   * @param {String} startDate - Start date (YYYY-MM-DD)
   * @param {String} endDate - End date (YYYY-MM-DD)
   * @param {Object} params - Query parameters
   * @returns {Promise}
   */
  getRecordsByDateRange(startDate, endDate, params = {}) {
    return api.get('/service-records/by-date-range', {
      params: { start_date: startDate, end_date: endDate, ...params }
    });
  },

  /**
   * Get cross-branch service history for a vehicle
   * @param {Number} vehicleId - Vehicle ID
   * @returns {Promise}
   */
  getCrossBranchHistory(vehicleId) {
    return api.get(`/vehicles/${vehicleId}/cross-branch-history`);
  },

  /**
   * Get vehicle history summary
   * @param {Number} vehicleId - Vehicle ID
   * @returns {Promise}
   */
  getVehicleHistorySummary(vehicleId) {
    return api.get(`/vehicles/${vehicleId}/history-summary`);
  },

  /**
   * Complete a service record
   * @param {Number} id - Service record ID
   * @returns {Promise}
   */
  completeServiceRecord(id) {
    return api.post(`/service-records/${id}/complete`);
  },

  /**
   * Cancel a service record
   * @param {Number} id - Service record ID
   * @returns {Promise}
   */
  cancelServiceRecord(id) {
    return api.post(`/service-records/${id}/cancel`);
  },

  /**
   * Get vehicle service statistics
   * @param {Number} vehicleId - Vehicle ID
   * @returns {Promise}
   */
  getVehicleStatistics(vehicleId) {
    return api.get(`/vehicles/${vehicleId}/service-statistics`);
  },

  /**
   * Get customer service statistics
   * @param {Number} customerId - Customer ID
   * @returns {Promise}
   */
  getCustomerStatistics(customerId) {
    return api.get(`/customers/${customerId}/service-statistics`);
  },
};

export default serviceRecordService;
