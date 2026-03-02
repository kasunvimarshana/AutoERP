import httpClient from '@/api/client'
import type { ApiResponse, ListParams, PaginatedResponse } from '@/types/api'

export interface Workflow {
  id: number
  tenant_id: number
  name: string
  entity_type: string
  initial_state: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface WorkflowInstance {
  id: number
  tenant_id: number
  workflow_definition_id: number
  entity_id: number
  entity_type: string
  current_state: string
  status: 'active' | 'completed' | 'cancelled'
  created_at: string
  updated_at: string
}

export interface CreateWorkflowPayload {
  name: string
  entity_type: string
  initial_state: string
  is_active?: boolean
}

export type UpdateWorkflowPayload = Partial<CreateWorkflowPayload>

export interface CreateWorkflowInstancePayload {
  workflow_definition_id: number
  entity_id: number
  entity_type: string
}

const workflowApi = {
  // Workflow Definitions
  listWorkflows: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<Workflow>>('/workflows', { params }),

  getWorkflow: (id: number) =>
    httpClient.get<ApiResponse<Workflow>>(`/workflows/${id}`),

  createWorkflow: (payload: CreateWorkflowPayload) =>
    httpClient.post<ApiResponse<Workflow>>('/workflows', payload),

  updateWorkflow: (id: number, payload: UpdateWorkflowPayload) =>
    httpClient.put<ApiResponse<Workflow>>(`/workflows/${id}`, payload),

  deleteWorkflow: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/workflows/${id}`),

  // Workflow Instances
  listInstances: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<WorkflowInstance>>('/workflow-instances', { params }),

  getInstance: (id: number) =>
    httpClient.get<ApiResponse<WorkflowInstance>>(`/workflow-instances/${id}`),

  createInstance: (payload: CreateWorkflowInstancePayload) =>
    httpClient.post<ApiResponse<WorkflowInstance>>('/workflow-instances', payload),

  applyTransition: (id: number, payload: { event: string }) =>
    httpClient.post<ApiResponse<WorkflowInstance>>(`/workflow-instances/${id}/transition`, payload),
}

export default workflowApi
