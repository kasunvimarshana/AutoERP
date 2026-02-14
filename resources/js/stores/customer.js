import { defineStore } from 'pinia';
import * as customerService from '../services/customer';

export const useCustomerStore = defineStore('customer', {
    state: () => ({
        customers: [],
        currentCustomer: null,
        loading: false,
        error: null,
        pagination: null,
    }),

    getters: {
        getCustomerById: (state) => (id) => {
            return state.customers.find(customer => customer.id === id);
        },
    },

    actions: {
        async fetchCustomers(params = {}) {
            this.loading = true;
            this.error = null;
            try {
                const response = await customerService.getCustomers(params);
                this.customers = response.data;
                this.pagination = response.meta || response.pagination;
                return response;
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to fetch customers';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchCustomer(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await customerService.getCustomer(id);
                this.currentCustomer = response.data;
                return response;
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to fetch customer';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async createCustomer(data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await customerService.createCustomer(data);
                this.customers.unshift(response.data);
                return response;
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to create customer';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateCustomer(id, data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await customerService.updateCustomer(id, data);
                const index = this.customers.findIndex(c => c.id === id);
                if (index !== -1) {
                    this.customers[index] = response.data;
                }
                return response;
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to update customer';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteCustomer(id) {
            this.loading = true;
            this.error = null;
            try {
                await customerService.deleteCustomer(id);
                this.customers = this.customers.filter(c => c.id !== id);
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to delete customer';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async searchCustomers(query) {
            this.loading = true;
            this.error = null;
            try {
                const response = await customerService.searchCustomers(query);
                return response;
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to search customers';
                throw error;
            } finally {
                this.loading = false;
            }
        },
    },
});
