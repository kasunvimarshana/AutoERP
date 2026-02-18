import { apiClient } from './client'

export interface DashboardStats {
  stats: Array<{
    name: string
    value: string
    change: string
    changeType: 'increase' | 'decrease'
  }>
}

export interface Activity {
  id: number
  description: string
  time: string
  created_at: string
}

export interface ChartData {
  labels: string[]
  values: number[]
}

export const dashboardApi = {
  /**
   * Get dashboard statistics
   */
  async getStats(): Promise<DashboardStats> {
    const response = await apiClient.get('/dashboard')
    return response.data
  },

  /**
   * Get recent activity feed
   */
  async getActivity(limit: number = 10): Promise<Activity[]> {
    const response = await apiClient.get('/dashboard/activity', { params: { limit } })
    return response.data
  },

  /**
   * Get revenue overview data for charts
   */
  async getRevenueOverview(period: 'day' | 'week' | 'month' | 'year' = 'month'): Promise<ChartData> {
    const response = await apiClient.get('/dashboard/revenue-overview', { params: { period } })
    return response.data
  },

  /**
   * Get sales by category data for pie/donut charts
   */
  async getSalesByCategory(limit: number = 10): Promise<ChartData> {
    const response = await apiClient.get('/dashboard/sales-by-category', { params: { limit } })
    return response.data
  },
}

export default dashboardApi
