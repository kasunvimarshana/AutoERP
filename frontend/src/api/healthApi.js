import api from './axios'

export const healthApi = {
  getHealthStatus: () => api.get('/health'),
  ping: () => api.get('/ping'),
}
