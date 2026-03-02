import httpClient from '@/api/client'
import type { ApiResponse, ListParams, PaginatedResponse } from '@/types/api'

export interface NotificationTemplate {
  id: number
  tenant_id: number
  name: string
  slug: string
  channel: 'email' | 'sms' | 'push' | 'in_app'
  subject: string | null
  body_template: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface NotificationLog {
  id: number
  tenant_id: number
  template_id: number | null
  channel: string
  recipient_id: number
  status: string
  sent_at: string | null
  created_at: string
}

export interface SendNotificationPayload {
  template_slug: string
  recipient_id: number
  data?: Record<string, unknown>
}

export interface CreateNotificationTemplatePayload {
  name: string
  slug: string
  channel: NotificationTemplate['channel']
  body_template: string
  subject?: string
  is_active?: boolean
}

export type UpdateNotificationTemplatePayload = Partial<CreateNotificationTemplatePayload>

const notificationApi = {
  // Templates
  listTemplates: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<NotificationTemplate>>('/notification/templates', { params }),

  getTemplate: (id: number) =>
    httpClient.get<ApiResponse<NotificationTemplate>>(`/notification/templates/${id}`),

  createTemplate: (payload: CreateNotificationTemplatePayload) =>
    httpClient.post<ApiResponse<NotificationTemplate>>('/notification/templates', payload),

  updateTemplate: (id: number, payload: UpdateNotificationTemplatePayload) =>
    httpClient.put<ApiResponse<NotificationTemplate>>(`/notification/templates/${id}`, payload),

  deleteTemplate: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/notification/templates/${id}`),

  // Send
  sendNotification: (payload: SendNotificationPayload) =>
    httpClient.post<ApiResponse<null>>('/notification/send', payload),

  // Logs
  listLogs: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<NotificationLog>>('/notification/logs', { params }),
}

export default notificationApi
