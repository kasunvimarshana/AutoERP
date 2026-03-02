import httpClient from '@/api/client'
import type { ApiResponse, ListParams, PaginatedResponse } from '@/types/api'

export interface Plugin {
  id: number
  name: string
  alias: string
  version: string
  description: string | null
  keywords: string[]
  requires: string[]
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface TenantPlugin {
  id: number
  tenant_id: number
  plugin_manifest_id: number
  is_enabled: boolean
  created_at: string
  updated_at: string
}

export interface InstallPluginPayload {
  name: string
  alias: string
  version: string
  description?: string
  keywords?: string[]
  requires?: string[]
}

export type UpdatePluginPayload = Partial<InstallPluginPayload>

const pluginApi = {
  listPlugins: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<Plugin>>('/plugins', { params }),

  getPlugin: (id: number) =>
    httpClient.get<ApiResponse<Plugin>>(`/plugins/${id}`),

  installPlugin: (payload: InstallPluginPayload) =>
    httpClient.post<ApiResponse<Plugin>>('/plugins', payload),

  updatePlugin: (id: number, payload: UpdatePluginPayload) =>
    httpClient.put<ApiResponse<Plugin>>(`/plugins/${id}`, payload),

  uninstallPlugin: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/plugins/${id}`),

  enablePlugin: (id: number) =>
    httpClient.post<ApiResponse<TenantPlugin>>(`/plugins/${id}/enable`),

  disablePlugin: (id: number) =>
    httpClient.post<ApiResponse<TenantPlugin>>(`/plugins/${id}/disable`),

  listTenantPlugins: () =>
    httpClient.get<ApiResponse<TenantPlugin[]>>('/plugins/tenant/enabled'),
}

export default pluginApi
