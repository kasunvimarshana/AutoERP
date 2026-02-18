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
