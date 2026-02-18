import type { ApiResponse, PaginatedResponse } from './types'
import { apiClient } from './client'

export interface Notification {
  id: string
  type: string
  data: {
    type: string
    title: string
    message: string
    severity?: string
    action_url?: string
    [key: string]: any
  }
  read_at: string | null
  created_at: string
  updated_at: string
}

export interface NotificationPreference {
  id: number
  user_id: number
  notification_type: string
  email_enabled: boolean
  database_enabled: boolean
  broadcast_enabled: boolean
  push_enabled: boolean
  created_at: string
  updated_at: string
}

export interface NotificationStatistics {
  total: number
  unread: number
  read: number
  byType: Record<string, number>
}

export const notificationApi = {
  /**
   * Get paginated notifications
   */
  async getNotifications(page = 1, perPage = 20): Promise<PaginatedResponse<Notification>> {
    const response = await apiClient.get<PaginatedResponse<Notification>>('/api/notifications', {
      params: { page, per_page: perPage },
    })
    return response.data
  },

  /**
   * Get unread notifications
   */
  async getUnreadNotifications(limit = 10): Promise<ApiResponse<Notification[]>> {
    const response = await apiClient.get<ApiResponse<Notification[]>>('/api/notifications/unread', {
      params: { limit },
    })
    return response.data
  },

  /**
   * Get unread notification count
   */
  async getUnreadCount(): Promise<number> {
    const response = await apiClient.get<ApiResponse<{ count: number }>>('/api/notifications/count')
    return response.data.data.count
  },

  /**
   * Get notification statistics
   */
  async getStatistics(): Promise<NotificationStatistics> {
    const response = await apiClient.get<ApiResponse<NotificationStatistics>>('/api/notifications/statistics')
    return response.data.data
  },

  /**
   * Mark notification as read
   */
  async markAsRead(notificationId: string): Promise<void> {
    await apiClient.post(`/api/notifications/${notificationId}/read`)
  },

  /**
   * Mark multiple notifications as read
   */
  async markMultipleAsRead(notificationIds: string[]): Promise<number> {
    const response = await apiClient.post<ApiResponse<{ count: number }>>('/api/notifications/read-multiple', {
      notification_ids: notificationIds,
    })
    return response.data.data.count
  },

  /**
   * Mark all notifications as read
   */
  async markAllAsRead(): Promise<number> {
    const response = await apiClient.post<ApiResponse<{ count: number }>>('/api/notifications/read-all')
    return response.data.data.count
  },

  /**
   * Delete a notification
   */
  async deleteNotification(notificationId: string): Promise<void> {
    await apiClient.delete(`/api/notifications/${notificationId}`)
  },

  /**
   * Get notification preferences
   */
  async getPreferences(): Promise<NotificationPreference[]> {
    const response = await apiClient.get<ApiResponse<NotificationPreference[]>>('/api/notifications/preferences')
    return response.data.data
  },

  /**
   * Update notification preferences
   */
  async updatePreferences(
    notificationType: string,
    channels: {
      email?: boolean
      database?: boolean
      broadcast?: boolean
      push?: boolean
    }
  ): Promise<NotificationPreference> {
    const response = await apiClient.put<ApiResponse<NotificationPreference>>('/api/notifications/preferences', {
      notification_type: notificationType,
      channels,
    })
    return response.data.data
  },
}
