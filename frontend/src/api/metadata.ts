import apiClient from './client';
import type { ModuleMetadata, PaginatedResponse } from '@/types';

export const metadataApi = {
  async getModuleMetadata(moduleName: string): Promise<ModuleMetadata> {
    const response = await apiClient.get<ModuleMetadata>(`/metadata/modules/${moduleName}`);
    return response.data;
  },

  async getAllModules(): Promise<ModuleMetadata[]> {
    const response = await apiClient.get<ModuleMetadata[]>('/metadata/modules');
    return response.data;
  },

  async getNavigation(): Promise<any[]> {
    const response = await apiClient.get<any[]>('/metadata/navigation');
    return response.data;
  },

  async getPermissions(): Promise<string[]> {
    const response = await apiClient.get<string[]>('/metadata/permissions');
    return response.data;
  },

  async getFormMetadata(formId: string): Promise<any> {
    const response = await apiClient.get(`/metadata/forms/${formId}`);
    return response.data;
  },

  async getTableMetadata(tableId: string): Promise<any> {
    const response = await apiClient.get(`/metadata/tables/${tableId}`);
    return response.data;
  },

  async getModuleData(moduleName: string, params?: Record<string, any>): Promise<PaginatedResponse<any>> {
    const response = await apiClient.get<PaginatedResponse<any>>(`/modules/${moduleName}`, { params });
    return response.data;
  },

  async getModuleRecord(moduleName: string, id: number): Promise<any> {
    const response = await apiClient.get<any>(`/modules/${moduleName}/${id}`);
    return response.data;
  },

  async createModuleRecord(moduleName: string, data: any): Promise<any> {
    const response = await apiClient.post<any>(`/modules/${moduleName}`, data);
    return response.data;
  },

  async updateModuleRecord(moduleName: string, id: number, data: any): Promise<any> {
    const response = await apiClient.put<any>(`/modules/${moduleName}/${id}`, data);
    return response.data;
  },

  async deleteModuleRecord(moduleName: string, id: number): Promise<void> {
    await apiClient.delete(`/modules/${moduleName}/${id}`);
  },

  async executeAction(moduleName: string, id: number, action: string, data?: any): Promise<any> {
    const response = await apiClient.post<any>(`/modules/${moduleName}/${id}/actions/${action}`, data);
    return response.data;
  },

  async getTenantConfiguration(): Promise<any> {
    const response = await apiClient.get('/metadata/tenant/configuration');
    return response.data;
  },

  async getUserPermissions(): Promise<string[]> {
    const response = await apiClient.get<string[]>('/metadata/user/permissions');
    return response.data;
  },

  async getDashboardMetadata(dashboardId: string = 'default'): Promise<any> {
    const response = await apiClient.get(`/metadata/dashboards/${dashboardId}`);
    return response.data;
  },

  async getWorkflowMetadata(workflowId: string): Promise<any> {
    const response = await apiClient.get(`/metadata/workflows/${workflowId}`);
    return response.data;
  },
};

export default metadataApi;
