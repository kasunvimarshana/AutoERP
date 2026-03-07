import apiClient from './client';
import { DashboardStats } from '../types';

export const dashboardApi = {
  stats: async (): Promise<DashboardStats> => {
    const { data } = await apiClient.get('/api/dashboard/stats');
    return data.data || data;
  },
};
