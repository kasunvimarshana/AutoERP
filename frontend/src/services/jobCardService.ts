import apiClient from './api'
import type { JobCard } from '@/types/jobCard'

export const jobCardService = {
  /**
   * Get all job cards with optional filters
   */
  async getJobCards(filters?: Record<string, any>) {
    const response = await apiClient.get('/job-cards', { params: filters })
    return response.data
  },

  /**
   * Get a single job card by ID
   */
  async getJobCard(id: number) {
    const response = await apiClient.get(`/job-cards/${id}`)
    return response.data
  },

  /**
   * Create a new job card
   */
  async createJobCard(data: Partial<JobCard>) {
    const response = await apiClient.post('/job-cards', data)
    return response.data
  },

  /**
   * Update an existing job card
   */
  async updateJobCard(id: number, data: Partial<JobCard>) {
    const response = await apiClient.put(`/job-cards/${id}`, data)
    return response.data
  },

  /**
   * Delete a job card
   */
  async deleteJobCard(id: number) {
    const response = await apiClient.delete(`/job-cards/${id}`)
    return response.data
  },

  /**
   * Open a job card
   */
  async openJobCard(id: number) {
    const response = await apiClient.post(`/job-cards/${id}/open`)
    return response.data
  },

  /**
   * Close a job card
   */
  async closeJobCard(id: number) {
    const response = await apiClient.post(`/job-cards/${id}/close`)
    return response.data
  },

  /**
   * Assign a job card to a user
   */
  async assignJobCard(id: number, userId: number) {
    const response = await apiClient.post(`/job-cards/${id}/assign`, { user_id: userId })
    return response.data
  },
}
