import httpClient from '@/api/client'
import type { ApiResponse, ListParams, PaginatedResponse } from '@/types/api'

export interface Organisation {
  id: number
  tenant_id: number
  name: string
  description: string | null
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface Branch {
  id: number
  tenant_id: number
  organisation_id: number
  name: string
  address: string | null
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface Location {
  id: number
  tenant_id: number
  branch_id: number
  name: string
  type: string | null
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface Department {
  id: number
  tenant_id: number
  location_id: number
  name: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface CreateOrganisationPayload {
  name: string
  description?: string
  is_active?: boolean
}

export type UpdateOrganisationPayload = Partial<CreateOrganisationPayload>

export interface CreateBranchPayload {
  name: string
  address?: string
  is_active?: boolean
}

export type UpdateBranchPayload = Partial<CreateBranchPayload>

export interface CreateLocationPayload {
  name: string
  type?: string
  is_active?: boolean
}

export type UpdateLocationPayload = Partial<CreateLocationPayload>

export interface CreateDepartmentPayload {
  name: string
  is_active?: boolean
}

export type UpdateDepartmentPayload = Partial<CreateDepartmentPayload>

const organisationApi = {
  // Organisations
  listOrganisations: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<Organisation>>('/organisations', { params }),

  getOrganisation: (id: number) =>
    httpClient.get<ApiResponse<Organisation>>(`/organisations/${id}`),

  createOrganisation: (payload: CreateOrganisationPayload) =>
    httpClient.post<ApiResponse<Organisation>>('/organisations', payload),

  updateOrganisation: (id: number, payload: UpdateOrganisationPayload) =>
    httpClient.put<ApiResponse<Organisation>>(`/organisations/${id}`, payload),

  deleteOrganisation: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/organisations/${id}`),

  // Branches
  listBranches: (orgId: number, params?: ListParams) =>
    httpClient.get<PaginatedResponse<Branch>>(`/organisations/${orgId}/branches`, { params }),

  createBranch: (orgId: number, payload: CreateBranchPayload) =>
    httpClient.post<ApiResponse<Branch>>(`/organisations/${orgId}/branches`, payload),

  getBranch: (id: number) =>
    httpClient.get<ApiResponse<Branch>>(`/branches/${id}`),

  updateBranch: (id: number, payload: UpdateBranchPayload) =>
    httpClient.put<ApiResponse<Branch>>(`/branches/${id}`, payload),

  deleteBranch: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/branches/${id}`),

  // Locations
  listLocations: (branchId: number, params?: ListParams) =>
    httpClient.get<PaginatedResponse<Location>>(`/branches/${branchId}/locations`, { params }),

  createLocation: (branchId: number, payload: CreateLocationPayload) =>
    httpClient.post<ApiResponse<Location>>(`/branches/${branchId}/locations`, payload),

  getLocation: (id: number) =>
    httpClient.get<ApiResponse<Location>>(`/locations/${id}`),

  updateLocation: (id: number, payload: UpdateLocationPayload) =>
    httpClient.put<ApiResponse<Location>>(`/locations/${id}`, payload),

  deleteLocation: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/locations/${id}`),

  // Departments
  listDepartments: (locationId: number, params?: ListParams) =>
    httpClient.get<PaginatedResponse<Department>>(`/locations/${locationId}/departments`, { params }),

  createDepartment: (locationId: number, payload: CreateDepartmentPayload) =>
    httpClient.post<ApiResponse<Department>>(`/locations/${locationId}/departments`, payload),

  getDepartment: (id: number) =>
    httpClient.get<ApiResponse<Department>>(`/departments/${id}`),

  updateDepartment: (id: number, payload: UpdateDepartmentPayload) =>
    httpClient.put<ApiResponse<Department>>(`/departments/${id}`, payload),

  deleteDepartment: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/departments/${id}`),
}

export default organisationApi
