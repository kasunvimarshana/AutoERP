import httpClient from '@/api/client'
import type { ApiResponse, ListParams, PaginatedResponse } from '@/types/api'

export interface MetadataField {
  id: number
  tenant_id: number
  entity_type: string
  field_name: string
  field_type: string
  is_required: boolean
  default_value: string | null
  created_at: string
  updated_at: string
}

export interface CreateMetadataFieldPayload {
  entity_type: string
  field_name: string
  field_type: string
  is_required?: boolean
  default_value?: string
}

export type UpdateMetadataFieldPayload = Partial<CreateMetadataFieldPayload>

export interface FeatureFlag {
  id: number
  tenant_id: number
  flag_key: string
  flag_value: boolean
  created_at: string
  updated_at: string
}

const metadataApi = {
  // Custom Fields
  listFields: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<MetadataField>>('/metadata/fields', { params }),

  getField: (id: number) =>
    httpClient.get<ApiResponse<MetadataField>>(`/metadata/fields/${id}`),

  createField: (payload: CreateMetadataFieldPayload) =>
    httpClient.post<ApiResponse<MetadataField>>('/metadata/fields', payload),

  updateField: (id: number, payload: UpdateMetadataFieldPayload) =>
    httpClient.put<ApiResponse<MetadataField>>(`/metadata/fields/${id}`, payload),

  deleteField: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/metadata/fields/${id}`),
}

export default metadataApi
