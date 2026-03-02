import httpClient from '@/api/client'
import type { ApiResponse, ListParams, PaginatedResponse } from '@/types/api'

export interface Tenant {
  id: number
  name: string
  slug: string
  domain: string | null
  is_active: boolean
  pharma_compliance_mode: boolean
  created_at: string
  updated_at: string
}

export interface CreateTenantPayload {
  name: string
  slug: string
  domain?: string
  is_active?: boolean
  pharma_compliance_mode?: boolean
}

export type UpdateTenantPayload = Partial<CreateTenantPayload>

const tenancyApi = {
  listTenants: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<Tenant>>('/tenants', { params }),

  getTenant: (id: number) =>
    httpClient.get<ApiResponse<Tenant>>(`/tenants/${id}`),

  createTenant: (payload: CreateTenantPayload) =>
    httpClient.post<ApiResponse<Tenant>>('/tenants', payload),

  updateTenant: (id: number, payload: UpdateTenantPayload) =>
    httpClient.put<ApiResponse<Tenant>>(`/tenants/${id}`, payload),

  deleteTenant: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/tenants/${id}`),
}

export default tenancyApi
