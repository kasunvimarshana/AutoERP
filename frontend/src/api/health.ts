import apiClient from './client';
import { HealthStatus } from '../types';

export const healthApi = {
  check: async (): Promise<HealthStatus> => {
    const { data } = await apiClient.get('/health');
    return data;
  },
  detailed: async (): Promise<HealthStatus> => {
    const { data } = await apiClient.get('/health/detailed');
    return data;
  },
};
