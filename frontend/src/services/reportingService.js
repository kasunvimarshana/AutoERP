import apiClient from './api'

export default {
  // Dashboards
  getDashboards(params = {}) {
    return apiClient.get('/reporting/dashboards', { params })
  },
  getDashboard(id) {
    return apiClient.get(`/reporting/dashboards/${id}`)
  },
  getDashboardData(id, params = {}) {
    return apiClient.get(`/reporting/dashboards/${id}/data`, { params })
  },
  
  // Analytics
  getAnalytics(params = {}) {
    return apiClient.get('/reporting/analytics', { params })
  },
  getSalesAnalytics(params = {}) {
    return apiClient.get('/reporting/analytics/sales', { params })
  },
  getInventoryAnalytics(params = {}) {
    return apiClient.get('/reporting/analytics/inventory', { params })
  }
}
