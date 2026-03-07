import apiClient from './axios';
import type { Tenant, TenantFormData, PaginatedResponse, FilterParams } from '@/types';

export const tenantsApi = {
  list: async (params?: FilterParams): Promise<PaginatedResponse<Tenant>> => {
    const { data } = await apiClient.get<PaginatedResponse<Tenant>>('/tenants', { params });
    return data;
  },

  get: async (id: number): Promise<Tenant> => {
    const { data } = await apiClient.get<{ data: Tenant }>(`/tenants/${id}`);
    return data.data;
  },

  create: async (payload: TenantFormData): Promise<Tenant> => {
    const { data } = await apiClient.post<{ data: Tenant }>('/tenants', payload);
    return data.data;
  },

  update: async (id: number, payload: Partial<TenantFormData>): Promise<Tenant> => {
    const { data } = await apiClient.put<{ data: Tenant }>(`/tenants/${id}`, payload);
    return data.data;
  },

  delete: async (id: number): Promise<void> => {
    await apiClient.delete(`/tenants/${id}`);
  },

  getStats: async (id: number): Promise<Record<string, number>> => {
    const { data } = await apiClient.get<{ data: Record<string, number> }>(`/tenants/${id}/stats`);
    return data.data;
  },
};
