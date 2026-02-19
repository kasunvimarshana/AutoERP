import { defineStore } from 'pinia';
import customerService from '@/services/customer';

export const useCustomerStore = defineStore('customer', {
  state: () => ({
    customers: [],
    currentCustomer: null,
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
    getCustomerById: (state) => (id) => {
      return state.customers.find(customer => customer.id === id);
    },
    hasCustomers: (state) => state.customers.length > 0,
    isLoading: (state) => state.loading,
    hasError: (state) => !!state.error,
  },

  actions: {
    /**
     * Fetch all customers
     * @param {Object} params - Query parameters
     */
    async fetchCustomers(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const response = await customerService.getCustomers(params);
        if (response.data.success) {
          const data = response.data.data;
          // Handle both paginated and non-paginated responses
          if (data.data) {
            // Paginated response
            this.customers = data.data;
            this.pagination = {
              current_page: data.current_page,
              per_page: data.per_page,
              total: data.total,
              last_page: data.last_page,
            };
          } else {
            // Non-paginated response
            this.customers = data;
          }
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch customers';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch a single customer by ID
     * @param {Number} id - Customer ID
     */
    async fetchCustomer(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await customerService.getCustomer(id);
        if (response.data.success) {
          this.currentCustomer = response.data.data;
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch customer';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Create a new customer
     * @param {Object} data - Customer data
     */
    async createCustomer(data) {
      this.loading = true;
      this.error = null;
      try {
        const response = await customerService.createCustomer(data);
        if (response.data.success) {
          // Add to customers array if not paginating
          if (!this.pagination.total || this.customers.length < this.pagination.per_page) {
            this.customers.unshift(response.data.data);
          }
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create customer';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Update an existing customer
     * @param {Number} id - Customer ID
     * @param {Object} data - Updated customer data
     */
    async updateCustomer(id, data) {
      this.loading = true;
      this.error = null;
      try {
        const response = await customerService.updateCustomer(id, data);
        if (response.data.success) {
          // Update customer in array
          const index = this.customers.findIndex(c => c.id === id);
          if (index !== -1) {
            this.customers[index] = response.data.data;
          }
          // Update current customer if it's the same
          if (this.currentCustomer?.id === id) {
            this.currentCustomer = response.data.data;
          }
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update customer';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Delete a customer
     * @param {Number} id - Customer ID
     */
    async deleteCustomer(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await customerService.deleteCustomer(id);
        if (response.data.success) {
          // Remove from customers array
          this.customers = this.customers.filter(c => c.id !== id);
          // Clear current customer if it's the same
          if (this.currentCustomer?.id === id) {
            this.currentCustomer = null;
          }
          return true;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete customer';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Search customers
     * @param {String} query - Search query
     * @param {Object} params - Additional query parameters
     */
    async searchCustomers(query, params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const response = await customerService.searchCustomers(query, params);
        if (response.data.success) {
          const data = response.data.data;
          if (data.data) {
            this.customers = data.data;
            this.pagination = {
              current_page: data.current_page,
              per_page: data.per_page,
              total: data.total,
              last_page: data.last_page,
            };
          } else {
            this.customers = data;
          }
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to search customers';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch customer with vehicles
     * @param {Number} id - Customer ID
     */
    async fetchCustomerWithVehicles(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await customerService.getCustomerWithVehicles(id);
        if (response.data.success) {
          this.currentCustomer = response.data.data;
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch customer with vehicles';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch customer statistics
     * @param {Number} id - Customer ID
     */
    async fetchCustomerStatistics(id) {
      this.error = null;
      try {
        const response = await customerService.getCustomerStatistics(id);
        if (response.data.success) {
          return response.data.data;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch customer statistics';
        throw error;
      }
    },

    /**
     * Clear current customer
     */
    clearCurrentCustomer() {
      this.currentCustomer = null;
    },

    /**
     * Clear error
     */
    clearError() {
      this.error = null;
    },
  },
});
