import httpClient from '@/api/client'
import type { ApiResponse, ListParams, PaginatedResponse } from '@/types/api'

export interface ReportDefinition {
  id: number
  tenant_id: number
  name: string
  slug: string
  report_type: string
  description: string | null
  filters: Record<string, unknown>
  columns: string[]
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface ReportSchedule {
  id: number
  tenant_id: number
  report_definition_id: number
  frequency: string
  recipients: string[]
  is_active: boolean
  next_run_at: string | null
  created_at: string
  updated_at: string
}

export interface ReportExport {
  id: number
  tenant_id: number
  report_definition_id: number
  status: 'pending' | 'processing' | 'completed' | 'failed'
  format: 'csv' | 'pdf' | 'xlsx'
  file_url: string | null
  generated_at: string | null
  created_at: string
}

export interface CreateReportDefinitionPayload {
  name: string
  slug: string
  report_type: string
  description?: string
  filters?: Record<string, unknown>
  columns?: string[]
  is_active?: boolean
}

export type UpdateReportDefinitionPayload = Partial<CreateReportDefinitionPayload>

export interface GenerateReportPayload {
  report_definition_id: number
  filters?: Record<string, unknown>
  format: 'csv' | 'pdf' | 'xlsx'
}

export interface ScheduleReportPayload {
  report_definition_id: number
  frequency: string
  recipients?: string[]
}

export type UpdateSchedulePayload = Partial<ScheduleReportPayload>

const reportingApi = {
  // Report Definitions
  listDefinitions: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<ReportDefinition>>('/reporting/definitions', { params }),

  getDefinition: (id: number) =>
    httpClient.get<ApiResponse<ReportDefinition>>(`/reporting/definitions/${id}`),

  createDefinition: (payload: CreateReportDefinitionPayload) =>
    httpClient.post<ApiResponse<ReportDefinition>>('/reporting/definitions', payload),

  updateDefinition: (id: number, payload: UpdateReportDefinitionPayload) =>
    httpClient.put<ApiResponse<ReportDefinition>>(`/reporting/definitions/${id}`, payload),

  deleteDefinition: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/reporting/definitions/${id}`),

  // Generate
  generateReport: (payload: GenerateReportPayload) =>
    httpClient.post<ApiResponse<ReportExport>>('/reporting/generate', payload),

  // Schedules
  listSchedules: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<ReportSchedule>>('/reporting/schedules', { params }),

  getSchedule: (id: number) =>
    httpClient.get<ApiResponse<ReportSchedule>>(`/reporting/schedules/${id}`),

  scheduleReport: (payload: ScheduleReportPayload) =>
    httpClient.post<ApiResponse<ReportSchedule>>('/reporting/schedules', payload),

  updateSchedule: (id: number, payload: UpdateSchedulePayload) =>
    httpClient.put<ApiResponse<ReportSchedule>>(`/reporting/schedules/${id}`, payload),

  deleteSchedule: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/reporting/schedules/${id}`),

  // Exports
  listExports: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<ReportExport>>('/reporting/exports', { params }),

  getExport: (id: number) =>
    httpClient.get<ApiResponse<ReportExport>>(`/reporting/exports/${id}`),
}

export default reportingApi
