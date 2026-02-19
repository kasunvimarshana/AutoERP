import { defineStore } from 'pinia';
import vehicleService from '@/services/vehicle';

export const useVehicleStore = defineStore('vehicle', {
  state: () => ({
    vehicles: [],
    currentVehicle: null,
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
    getVehicleById: (state) => (id) => {
      return state.vehicles.find(vehicle => vehicle.id === id);
    },
    hasVehicles: (state) => state.vehicles.length > 0,
    isLoading: (state) => state.loading,
    hasError: (state) => !!state.error,
  },

  actions: {
    /**
     * Fetch all vehicles
     * @param {Object} params - Query parameters
     */
    async fetchVehicles(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const response = await vehicleService.getVehicles(params);
        if (response.data.success) {
          const data = response.data.data;
          if (data.data) {
            this.vehicles = data.data;
            this.pagination = {
              current_page: data.current_page,
              per_page: data.per_page,
              total: data.total,
              last_page: data.last_page,
            };
          } else {
            this.vehicles = data;
          }
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch vehicles';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch a single vehicle by ID
     * @param {Number} id - Vehicle ID
     */
    async fetchVehicle(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await vehicleService.getVehicle(id);
        if (response.data.success) {
          this.currentVehicle = response.data.data;
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch vehicle';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Create a new vehicle
     * @param {Object} data - Vehicle data
     */
    async createVehicle(data) {
      this.loading = true;
      this.error = null;
      try {
        const response = await vehicleService.createVehicle(data);
        if (response.data.success) {
          if (!this.pagination.total || this.vehicles.length < this.pagination.per_page) {
            this.vehicles.unshift(response.data.data);
          }
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create vehicle';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Update an existing vehicle
     * @param {Number} id - Vehicle ID
     * @param {Object} data - Updated vehicle data
     */
    async updateVehicle(id, data) {
      this.loading = true;
      this.error = null;
      try {
        const response = await vehicleService.updateVehicle(id, data);
        if (response.data.success) {
          const index = this.vehicles.findIndex(v => v.id === id);
          if (index !== -1) {
            this.vehicles[index] = response.data.data;
          }
          if (this.currentVehicle?.id === id) {
            this.currentVehicle = response.data.data;
          }
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update vehicle';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Delete a vehicle
     * @param {Number} id - Vehicle ID
     */
    async deleteVehicle(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await vehicleService.deleteVehicle(id);
        if (response.data.success) {
          this.vehicles = this.vehicles.filter(v => v.id !== id);
          if (this.currentVehicle?.id === id) {
            this.currentVehicle = null;
          }
          return true;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete vehicle';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Search vehicles
     * @param {String} query - Search query
     * @param {Object} params - Additional query parameters
     */
    async searchVehicles(query, params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const response = await vehicleService.searchVehicles(query, params);
        if (response.data.success) {
          const data = response.data.data;
          if (data.data) {
            this.vehicles = data.data;
            this.pagination = {
              current_page: data.current_page,
              per_page: data.per_page,
              total: data.total,
              last_page: data.last_page,
            };
          } else {
            this.vehicles = data;
          }
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to search vehicles';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch vehicles by customer ID
     * @param {Number} customerId - Customer ID
     */
    async fetchVehiclesByCustomer(customerId) {
      this.loading = true;
      this.error = null;
      try {
        const response = await vehicleService.getVehiclesByCustomer(customerId);
        if (response.data.success) {
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch vehicles by customer';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch vehicle with relations
     * @param {Number} id - Vehicle ID
     */
    async fetchVehicleWithRelations(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await vehicleService.getVehicleWithRelations(id);
        if (response.data.success) {
          this.currentVehicle = response.data.data;
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch vehicle with relations';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch vehicles due for service
     * @param {Object} params - Query parameters
     */
    async fetchDueForService(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const response = await vehicleService.getDueForService(params);
        if (response.data.success) {
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch vehicles due for service';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch vehicles with expiring insurance
     * @param {Object} params - Query parameters
     */
    async fetchExpiringInsurance(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const response = await vehicleService.getExpiringInsurance(params);
        if (response.data.success) {
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch vehicles with expiring insurance';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Update vehicle mileage
     * @param {Number} id - Vehicle ID
     * @param {Number} mileage - New mileage value
     */
    async updateMileage(id, mileage) {
      this.loading = true;
      this.error = null;
      try {
        const response = await vehicleService.updateMileage(id, mileage);
        if (response.data.success) {
          const index = this.vehicles.findIndex(v => v.id === id);
          if (index !== -1) {
            this.vehicles[index] = response.data.data;
          }
          if (this.currentVehicle?.id === id) {
            this.currentVehicle = response.data.data;
          }
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update mileage';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Transfer vehicle ownership
     * @param {Number} id - Vehicle ID
     * @param {Number} newCustomerId - New customer ID
     */
    async transferOwnership(id, newCustomerId) {
      this.loading = true;
      this.error = null;
      try {
        const response = await vehicleService.transferOwnership(id, newCustomerId);
        if (response.data.success) {
          const index = this.vehicles.findIndex(v => v.id === id);
          if (index !== -1) {
            this.vehicles[index] = response.data.data;
          }
          if (this.currentVehicle?.id === id) {
            this.currentVehicle = response.data.data;
          }
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to transfer ownership';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch vehicle statistics
     * @param {Number} id - Vehicle ID
     */
    async fetchVehicleStatistics(id) {
      this.error = null;
      try {
        const response = await vehicleService.getVehicleStatistics(id);
        if (response.data.success) {
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch vehicle statistics';
        throw error;
      }
    },

    /**
     * Clear current vehicle
     */
    clearCurrentVehicle() {
      this.currentVehicle = null;
    },

    /**
     * Clear error
     */
    clearError() {
      this.error = null;
    },
  },
});
