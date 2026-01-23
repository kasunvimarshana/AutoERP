import apiClient from './api'
import type { Customer, CustomerFilters } from '@/types/customer'

export const customerService = {
  /**
   * Get all customers with optional filters
   */
  async getCustomers(filters?: CustomerFilters) {
    const response = await apiClient.get('/customers', { params: filters })
    return response.data
  },

  /**
   * Get a single customer by ID
   */
  async getCustomer(id: number) {
    const response = await apiClient.get(`/customers/${id}`)
    return response.data
  },

  /**
   * Create a new customer
   */
  async createCustomer(data: Partial<Customer>) {
    const response = await apiClient.post('/customers', data)
    return response.data
  },

  /**
   * Update an existing customer
   */
  async updateCustomer(id: number, data: Partial<Customer>) {
    const response = await apiClient.put(`/customers/${id}`, data)
    return response.data
  },

  /**
   * Delete a customer
   */
  async deleteCustomer(id: number) {
    const response = await apiClient.delete(`/customers/${id}`)
    return response.data
  },

  /**
   * Get customers with upcoming services
   */
  async getUpcomingServices() {
    const response = await apiClient.get('/customers/upcoming-services')
    return response.data
  },
}
