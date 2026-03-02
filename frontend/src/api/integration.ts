import httpClient from '@/api/client'
import type { ApiResponse, ListParams, PaginatedResponse } from '@/types/api'

export interface WebhookEndpoint {
  id: number
  tenant_id: number
  name: string
  url: string
  events: string[]
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface IntegrationLog {
  id: number
  tenant_id: number
  webhook_id: number
  event: string
  status: string
  response_code: number | null
  created_at: string
}

export interface WebhookDelivery {
  id: number
  tenant_id: number
  webhook_endpoint_id: number
  event_name: string
  status: string
  attempt_count: number
  payload: Record<string, unknown>
  created_at: string
  updated_at: string
}

export interface RegisterWebhookPayload {
  name: string
  url: string
  events: string[]
  is_active?: boolean
}

export type UpdateWebhookPayload = Partial<RegisterWebhookPayload>

const integrationApi = {
  // Webhooks
  listWebhooks: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<WebhookEndpoint>>('/integration/webhooks', { params }),

  getWebhook: (id: number) =>
    httpClient.get<ApiResponse<WebhookEndpoint>>(`/integration/webhooks/${id}`),

  createWebhook: (payload: RegisterWebhookPayload) =>
    httpClient.post<ApiResponse<WebhookEndpoint>>('/integration/webhooks', payload),

  updateWebhook: (id: number, payload: UpdateWebhookPayload) =>
    httpClient.put<ApiResponse<WebhookEndpoint>>(`/integration/webhooks/${id}`, payload),

  deleteWebhook: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/integration/webhooks/${id}`),

  dispatchWebhook: (id: number, payload: Record<string, unknown>) =>
    httpClient.post<ApiResponse<WebhookDelivery>>(`/integration/webhooks/${id}/dispatch`, payload),

  // Deliveries
  listDeliveries: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<WebhookDelivery>>('/integration/deliveries', { params }),

  // Logs
  listLogs: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<IntegrationLog>>('/integration/logs', { params }),
}

export default integrationApi
