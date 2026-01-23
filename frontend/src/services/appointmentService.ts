import apiClient from './api'
import type { Appointment, ServiceBay } from '@/types/appointment'

export const appointmentService = {
  /**
   * Get all appointments with optional filters
   */
  async getAppointments(filters?: Record<string, any>) {
    const response = await apiClient.get('/appointments', { params: filters })
    return response.data
  },

  /**
   * Get a single appointment by ID
   */
  async getAppointment(id: number) {
    const response = await apiClient.get(`/appointments/${id}`)
    return response.data
  },

  /**
   * Create a new appointment
   */
  async createAppointment(data: Partial<Appointment>) {
    const response = await apiClient.post('/appointments', data)
    return response.data
  },

  /**
   * Update an existing appointment
   */
  async updateAppointment(id: number, data: Partial<Appointment>) {
    const response = await apiClient.put(`/appointments/${id}`, data)
    return response.data
  },

  /**
   * Delete an appointment
   */
  async deleteAppointment(id: number) {
    const response = await apiClient.delete(`/appointments/${id}`)
    return response.data
  },

  /**
   * Confirm an appointment
   */
  async confirmAppointment(id: number) {
    const response = await apiClient.post(`/appointments/${id}/confirm`)
    return response.data
  },

  /**
   * Cancel an appointment
   */
  async cancelAppointment(id: number, reason?: string) {
    const response = await apiClient.post(`/appointments/${id}/cancel`, { reason })
    return response.data
  },

  /**
   * Complete an appointment
   */
  async completeAppointment(id: number) {
    const response = await apiClient.post(`/appointments/${id}/complete`)
    return response.data
  },
}

export const serviceBayService = {
  /**
   * Get all service bays
   */
  async getServiceBays(filters?: Record<string, any>) {
    const response = await apiClient.get('/service-bays', { params: filters })
    return response.data
  },

  /**
   * Get a single service bay by ID
   */
  async getServiceBay(id: number) {
    const response = await apiClient.get(`/service-bays/${id}`)
    return response.data
  },

  /**
   * Create a new service bay
   */
  async createServiceBay(data: Partial<ServiceBay>) {
    const response = await apiClient.post('/service-bays', data)
    return response.data
  },

  /**
   * Update an existing service bay
   */
  async updateServiceBay(id: number, data: Partial<ServiceBay>) {
    const response = await apiClient.put(`/service-bays/${id}`, data)
    return response.data
  },

  /**
   * Delete a service bay
   */
  async deleteServiceBay(id: number) {
    const response = await apiClient.delete(`/service-bays/${id}`)
    return response.data
  },

  /**
   * Check availability of service bays
   */
  async checkAvailability(date: string, time: string) {
    const response = await apiClient.get('/service-bays/availability/check', {
      params: { date, time },
    })
    return response.data
  },
}
