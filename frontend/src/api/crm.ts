import httpClient from '@/api/client'
import type { ApiResponse, ListParams, PaginatedResponse } from '@/types/api'

export interface CrmLead {
  id: number
  tenant_id: number
  name: string
  email: string | null
  phone: string | null
  company: string | null
  source: string | null
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  assigned_to: number | null
  estimated_value: string
  notes: string | null
  created_at: string
  updated_at: string
}

export interface CrmOpportunity {
  id: number
  tenant_id: number
  lead_id: number | null
  name: string
  stage_id: number
  status: 'open' | 'won' | 'lost'
  estimated_value: string
  probability: string
  expected_close_date: string | null
  assigned_to: number | null
  created_at: string
  updated_at: string
}

export interface CrmCustomer {
  id: number
  tenant_id: number
  name: string
  email: string | null
  phone: string | null
  created_at: string
  updated_at: string
}

const crmApi = {
  // Leads
  listLeads: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<CrmLead>>('/crm/leads', { params }),

  getLead: (id: number) =>
    httpClient.get<ApiResponse<CrmLead>>(`/crm/leads/${id}`),

  createLead: (payload: Partial<CrmLead>) =>
    httpClient.post<ApiResponse<CrmLead>>('/crm/leads', payload),

  deleteLead: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/crm/leads/${id}`),

  convertLead: (id: number, payload: { name: string; estimated_value?: string }) =>
    httpClient.post<ApiResponse<CrmOpportunity>>(`/crm/leads/${id}/convert`, payload),

  // Opportunities
  listOpportunities: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<CrmOpportunity>>('/crm/opportunities', { params }),

  getOpportunity: (id: number) =>
    httpClient.get<ApiResponse<CrmOpportunity>>(`/crm/opportunities/${id}`),

  updateOpportunityStage: (id: number, payload: { stage_id: number }) =>
    httpClient.post<ApiResponse<CrmOpportunity>>(`/crm/opportunities/${id}/stage`, payload),

  closeWon: (id: number) =>
    httpClient.post<ApiResponse<CrmOpportunity>>(`/crm/opportunities/${id}/close-won`),

  closeLost: (id: number) =>
    httpClient.post<ApiResponse<CrmOpportunity>>(`/crm/opportunities/${id}/close-lost`),

  // Customers
  listCustomers: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<CrmCustomer>>('/crm/customers', { params }),
}

export default crmApi
