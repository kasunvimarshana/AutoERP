import apiClient from './client';
import type { ApiResponse, Tenant, TenantConfig, PaginatedResponse } from '../types';

export const tenantsApi = {
  list: () =>
    apiClient.get<ApiResponse<PaginatedResponse<Tenant>>>('/admin/tenants'),

  get: (id: number) =>
    apiClient.get<ApiResponse<Tenant>>(`/admin/tenants/${id}`),

  create: (payload: Partial<Tenant>) =>
    apiClient.post<ApiResponse<Tenant>>('/admin/tenants', payload),

  update: (id: number, payload: Partial<Tenant>) =>
    apiClient.put<ApiResponse<Tenant>>(`/admin/tenants/${id}`, payload),

  delete: (id: number) =>
    apiClient.delete(`/admin/tenants/${id}`),

  getConfig: (id: number) =>
    apiClient.get<ApiResponse<TenantConfig[]>>(`/admin/tenants/${id}/config`),

  setConfig: (id: number, key: string, value: string, group?: string, type?: string) =>
    apiClient.post(`/admin/tenants/${id}/config`, { key, value, group, type }),
};
