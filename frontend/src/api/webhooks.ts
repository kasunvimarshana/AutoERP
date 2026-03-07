import apiClient from './client';
import { Webhook } from '../types';

export const webhooksApi = {
  list: async (): Promise<Webhook[]> => {
    const { data } = await apiClient.get('/api/webhooks');
    return data.data || data;
  },
  get: async (id: number): Promise<Webhook> => {
    const { data } = await apiClient.get(`/api/webhooks/${id}`);
    return data.data || data;
  },
  create: async (webhookData: Partial<Webhook>): Promise<Webhook> => {
    const { data } = await apiClient.post('/api/webhooks', webhookData);
    return data.data || data;
  },
  update: async (id: number, webhookData: Partial<Webhook>): Promise<Webhook> => {
    const { data } = await apiClient.put(`/api/webhooks/${id}`, webhookData);
    return data.data || data;
  },
  delete: async (id: number): Promise<void> => {
    await apiClient.delete(`/api/webhooks/${id}`);
  },
  test: async (id: number): Promise<{ success: boolean; message: string }> => {
    const { data } = await apiClient.post(`/api/webhooks/${id}/test`);
    return data.data || data;
  },
};
