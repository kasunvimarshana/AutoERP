import apiClient from './client';
import type { Tenant } from '@/types';

export const tenantApi = {
  async getCurrent(): Promise<Tenant> {
    const response = await apiClient.get<Tenant>('/tenant/current');
    return response.data;
  },

  async update(data: Partial<Tenant>): Promise<Tenant> {
    const response = await apiClient.put<Tenant>('/tenant/current', data);
    return response.data;
  },

  async updateSettings(settings: Record<string, any>): Promise<Tenant> {
    const response = await apiClient.put<Tenant>('/tenant/settings', settings);
    return response.data;
  },

  async uploadLogo(file: File): Promise<{ url: string }> {
    const response = await apiClient.uploadFile<{ url: string }>('/tenant/logo', file);
    return response.data;
  },

  async getSubscription(): Promise<any> {
    const response = await apiClient.get('/tenant/subscription');
    return response.data;
  },
};

export default tenantApi;
