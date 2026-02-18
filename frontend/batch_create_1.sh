#!/bin/bash

# NotificationContainer Component
cat > src/components/common/NotificationContainer.vue << 'EOF'
<template>
  <div class="notification-container">
    <transition-group name="notification">
      <div
        v-for="notification in notifications"
        :key="notification.id"
        :class="['notification', `notification-${notification.type}`]"
      >
        <div class="notification-content">
          <component :is="getIcon(notification.type)" class="notification-icon" />
          <p class="notification-message">{{ notification.message }}</p>
        </div>
        <button @click="removeNotification(notification.id)" class="notification-close">
          <XMarkIcon class="icon" />
        </button>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useNotificationStore } from '../../stores/notificationStore'
import { 
  CheckCircleIcon, 
  XCircleIcon, 
  ExclamationTriangleIcon, 
  InformationCircleIcon,
  XMarkIcon 
} from '@heroicons/vue/24/outline'

const notificationStore = useNotificationStore()
const notifications = computed(() => notificationStore.notifications.slice(0, 5))

const getIcon = (type) => {
  const icons = {
    success: CheckCircleIcon,
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon
  }
  return icons[type] || InformationCircleIcon
}

const removeNotification = (id) => {
  notificationStore.removeNotification(id)
}
</script>

<style scoped>
.notification-container {
  position: fixed;
  top: 80px;
  right: 24px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.notification {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-width: 300px;
  max-width: 400px;
  padding: 16px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  border-left: 4px solid;
}

.notification-success {
  border-left-color: #10b981;
}

.notification-error {
  border-left-color: #ef4444;
}

.notification-warning {
  border-left-color: #f59e0b;
}

.notification-info {
  border-left-color: #3b82f6;
}

.notification-content {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.notification-icon {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
}

.notification-success .notification-icon {
  color: #10b981;
}

.notification-error .notification-icon {
  color: #ef4444;
}

.notification-warning .notification-icon {
  color: #f59e0b;
}

.notification-info .notification-icon {
  color: #3b82f6;
}

.notification-message {
  font-size: 14px;
  color: #1f2937;
  line-height: 1.5;
}

.notification-close {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  color: #9ca3af;
  transition: color 0.2s;
  flex-shrink: 0;
}

.notification-close:hover {
  color: #4b5563;
}

.icon {
  width: 20px;
  height: 20px;
}

.notification-enter-active,
.notification-leave-active {
  transition: all 0.3s ease;
}

.notification-enter-from {
  opacity: 0;
  transform: translateX(100px);
}

.notification-leave-to {
  opacity: 0;
  transform: translateX(100px);
}
</style>
EOF

# NotificationPanel Component
cat > src/components/common/NotificationPanel.vue << 'EOF'
<template>
  <div class="notification-panel-overlay" @click="$emit('close')">
    <div class="notification-panel" @click.stop>
      <div class="panel-header">
        <h3>Notifications</h3>
        <button v-if="unreadCount > 0" @click="markAllAsRead" class="mark-all-btn">
          Mark all as read
        </button>
      </div>
      
      <div class="panel-body">
        <div v-if="notifications.length === 0" class="empty-state">
          No notifications
        </div>
        
        <div
          v-for="notification in notifications"
          :key="notification.id"
          :class="['notification-item', { unread: !notification.read }]"
          @click="markAsRead(notification.id)"
        >
          <div class="notification-content">
            <p class="notification-message">{{ notification.message }}</p>
            <span class="notification-time">{{ formatTime(notification.timestamp) }}</span>
          </div>
          <button @click.stop="removeNotification(notification.id)" class="remove-btn">
            <XMarkIcon class="icon" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useNotificationStore } from '../../stores/notificationStore'
import { XMarkIcon } from '@heroicons/vue/24/outline'

defineEmits(['close'])

const notificationStore = useNotificationStore()
const notifications = computed(() => notificationStore.notifications)
const unreadCount = computed(() => notificationStore.unreadCount)

const markAsRead = (id) => {
  notificationStore.markAsRead(id)
}

const markAllAsRead = () => {
  notificationStore.markAllAsRead()
}

const removeNotification = (id) => {
  notificationStore.removeNotification(id)
}

const formatTime = (timestamp) => {
  const now = new Date()
  const time = new Date(timestamp)
  const diff = Math.floor((now - time) / 1000)
  
  if (diff < 60) return 'Just now'
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
  return `${Math.floor(diff / 86400)}d ago`
}
</script>

<style scoped>
.notification-panel-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.3);
  z-index: 9998;
}

.notification-panel {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  width: 400px;
  max-width: 100%;
  background: white;
  box-shadow: -4px 0 6px -1px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.panel-header h3 {
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.mark-all-btn {
  font-size: 12px;
  color: #3b82f6;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px 8px;
}

.mark-all-btn:hover {
  text-decoration: underline;
}

.panel-body {
  flex: 1;
  overflow-y: auto;
}

.empty-state {
  padding: 40px 20px;
  text-align: center;
  color: #9ca3af;
}

.notification-item {
  display: flex;
  align-items: start;
  gap: 12px;
  padding: 16px 20px;
  border-bottom: 1px solid #f3f4f6;
  cursor: pointer;
  transition: background 0.2s;
}

.notification-item:hover {
  background: #f9fafb;
}

.notification-item.unread {
  background: #eff6ff;
}

.notification-content {
  flex: 1;
}

.notification-message {
  font-size: 14px;
  color: #1f2937;
  margin-bottom: 4px;
}

.notification-time {
  font-size: 12px;
  color: #6b7280;
}

.remove-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  color: #9ca3af;
}

.remove-btn:hover {
  color: #4b5563;
}

.icon {
  width: 16px;
  height: 16px;
}
</style>
EOF

echo "Common components created"
