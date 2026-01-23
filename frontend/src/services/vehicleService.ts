import apiClient from './api'
import type { Vehicle, VehicleFilters } from '@/types/vehicle'

export const vehicleService = {
  /**
   * Get all vehicles with optional filters
   */
  async getVehicles(filters?: VehicleFilters) {
    const response = await apiClient.get('/vehicles', { params: filters })
    return response.data
  },

  /**
   * Get a single vehicle by ID
   */
  async getVehicle(id: number) {
    const response = await apiClient.get(`/vehicles/${id}`)
    return response.data
  },

  /**
   * Create a new vehicle
   */
  async createVehicle(data: Partial<Vehicle>) {
    const response = await apiClient.post('/vehicles', data)
    return response.data
  },

  /**
   * Update an existing vehicle
   */
  async updateVehicle(id: number, data: Partial<Vehicle>) {
    const response = await apiClient.put(`/vehicles/${id}`, data)
    return response.data
  },

  /**
   * Delete a vehicle
   */
  async deleteVehicle(id: number) {
    const response = await apiClient.delete(`/vehicles/${id}`)
    return response.data
  },

  /**
   * Transfer vehicle ownership
   */
  async transferOwnership(
    id: number,
    data: { new_customer_id: number; reason?: string; notes?: string },
  ) {
    const response = await apiClient.post(`/vehicles/${id}/transfer-ownership`, data)
    return response.data
  },

  /**
   * Update vehicle mileage
   */
  async updateMileage(id: number, mileage: number) {
    const response = await apiClient.post(`/vehicles/${id}/update-mileage`, { mileage })
    return response.data
  },
}
