import apiClient from './axios';

export interface HealthStatus {
  status: 'healthy' | 'degraded' | 'down';
  timestamp: string;
  services: ServiceHealth[];
}

export interface ServiceHealth {
  name: string;
  status: 'up' | 'down' | 'degraded';
  response_time_ms: number | null;
  message: string | null;
}

export const healthApi = {
  check: async (): Promise<HealthStatus> => {
    const { data } = await apiClient.get<HealthStatus>('/health');
    return data;
  },

  detailed: async (): Promise<HealthStatus> => {
    const { data } = await apiClient.get<HealthStatus>('/health/detailed');
    return data;
  },
};
