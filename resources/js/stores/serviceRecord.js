import { defineStore } from 'pinia';
import serviceRecordService from '@/services/serviceRecord';

export const useServiceRecordStore = defineStore('serviceRecord', {
  state: () => ({
    serviceRecords: [],
    currentServiceRecord: null,
    loading: false,
    error: null,
    pagination: {
      current_page: 1,
      per_page: 15,
      total: 0,
      last_page: 1,
    },
  }),

  getters: {
    getServiceRecordById: (state) => (id) => {
      return state.serviceRecords.find(record => record.id === id);
    },
    hasServiceRecords: (state) => state.serviceRecords.length > 0,
    isLoading: (state) => state.loading,
    hasError: (state) => !!state.error,
  },

  actions: {
    /**
     * Fetch all service records
     * @param {Object} params - Query parameters
     */
    async fetchServiceRecords(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const response = await serviceRecordService.getServiceRecords(params);
        if (response.data.success) {
          const data = response.data.data;
          if (data.data) {
            this.serviceRecords = data.data;
            this.pagination = {
              current_page: data.current_page,
              per_page: data.per_page,
              total: data.total,
              last_page: data.last_page,
            };
          } else {
            this.serviceRecords = data;
          }
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch service records';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch a single service record by ID
     * @param {Number} id - Service record ID
     */
    async fetchServiceRecord(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await serviceRecordService.getServiceRecord(id);
        if (response.data.success) {
          this.currentServiceRecord = response.data.data;
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch service record';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Create a new service record
     * @param {Object} data - Service record data
     */
    async createServiceRecord(data) {
      this.loading = true;
      this.error = null;
      try {
        const response = await serviceRecordService.createServiceRecord(data);
        if (response.data.success) {
          if (!this.pagination.total || this.serviceRecords.length < this.pagination.per_page) {
            this.serviceRecords.unshift(response.data.data);
          }
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create service record';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Update an existing service record
     * @param {Number} id - Service record ID
     * @param {Object} data - Updated service record data
     */
    async updateServiceRecord(id, data) {
      this.loading = true;
      this.error = null;
      try {
        const response = await serviceRecordService.updateServiceRecord(id, data);
        if (response.data.success) {
          const index = this.serviceRecords.findIndex(r => r.id === id);
          if (index !== -1) {
            this.serviceRecords[index] = response.data.data;
          }
          if (this.currentServiceRecord?.id === id) {
            this.currentServiceRecord = response.data.data;
          }
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update service record';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Delete a service record
     * @param {Number} id - Service record ID
     */
    async deleteServiceRecord(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await serviceRecordService.deleteServiceRecord(id);
        if (response.data.success) {
          this.serviceRecords = this.serviceRecords.filter(r => r.id !== id);
          if (this.currentServiceRecord?.id === id) {
            this.currentServiceRecord = null;
          }
          return true;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete service record';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Search service records
     * @param {String} query - Search query
     * @param {Object} params - Additional query parameters
     */
    async searchServiceRecords(query, params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const response = await serviceRecordService.searchServiceRecords(query, params);
        if (response.data.success) {
          const data = response.data.data;
          if (data.data) {
            this.serviceRecords = data.data;
            this.pagination = {
              current_page: data.current_page,
              per_page: data.per_page,
              total: data.total,
              last_page: data.last_page,
            };
          } else {
            this.serviceRecords = data;
          }
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to search service records';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch service record with relations
     * @param {Number} id - Service record ID
     */
    async fetchServiceRecordWithRelations(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await serviceRecordService.getServiceRecordWithRelations(id);
        if (response.data.success) {
          this.currentServiceRecord = response.data.data;
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch service record with relations';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch service records by vehicle ID
     * @param {Number} vehicleId - Vehicle ID
     */
    async fetchServiceRecordsByVehicle(vehicleId) {
      this.loading = true;
      this.error = null;
      try {
        const response = await serviceRecordService.getServiceRecordsByVehicle(vehicleId);
        if (response.data.success) {
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch service records by vehicle';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch service records by customer ID
     * @param {Number} customerId - Customer ID
     */
    async fetchServiceRecordsByCustomer(customerId) {
      this.loading = true;
      this.error = null;
      try {
        const response = await serviceRecordService.getServiceRecordsByCustomer(customerId);
        if (response.data.success) {
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch service records by customer';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch pending service records
     * @param {Object} params - Query parameters
     */
    async fetchPendingRecords(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const response = await serviceRecordService.getPendingRecords(params);
        if (response.data.success) {
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch pending service records';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch in-progress service records
     * @param {Object} params - Query parameters
     */
    async fetchInProgressRecords(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const response = await serviceRecordService.getInProgressRecords(params);
        if (response.data.success) {
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch in-progress service records';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Complete a service record
     * @param {Number} id - Service record ID
     */
    async completeServiceRecord(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await serviceRecordService.completeServiceRecord(id);
        if (response.data.success) {
          const index = this.serviceRecords.findIndex(r => r.id === id);
          if (index !== -1) {
            this.serviceRecords[index] = response.data.data;
          }
          if (this.currentServiceRecord?.id === id) {
            this.currentServiceRecord = response.data.data;
          }
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to complete service record';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Cancel a service record
     * @param {Number} id - Service record ID
     */
    async cancelServiceRecord(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await serviceRecordService.cancelServiceRecord(id);
        if (response.data.success) {
          const index = this.serviceRecords.findIndex(r => r.id === id);
          if (index !== -1) {
            this.serviceRecords[index] = response.data.data;
          }
          if (this.currentServiceRecord?.id === id) {
            this.currentServiceRecord = response.data.data;
          }
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to cancel service record';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Clear current service record
     */
    clearCurrentServiceRecord() {
      this.currentServiceRecord = null;
    },

    /**
     * Clear error
     */
    clearError() {
      this.error = null;
    },
  },
});
