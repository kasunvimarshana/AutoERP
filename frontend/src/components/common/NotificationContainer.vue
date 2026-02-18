<template>
  <TransitionGroup
    name="notification"
    tag="div"
    class="fixed top-4 right-4 z-50 space-y-2 w-96 max-w-full"
  >
    <div
      v-for="notification in notifications"
      :key="notification.id"
      :class="[
        'bg-white rounded-lg shadow-lg p-4 flex items-start space-x-3 border-l-4',
        borderColor(notification.type),
      ]"
    >
      <!-- Icon -->
      <div :class="['flex-shrink-0', iconColor(notification.type)]">
        <component
          :is="getIcon(notification.type)"
          class="h-6 w-6"
        />
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <p class="text-sm font-medium text-gray-900">
          {{ notification.title }}
        </p>
        <p class="text-sm text-gray-500 mt-1">
          {{ notification.message }}
        </p>
        <button
          v-if="notification.action"
          class="text-sm font-medium text-blue-600 hover:text-blue-500 mt-2"
          @click="notification.action.callback"
        >
          {{ notification.action.label }}
        </button>
      </div>

      <!-- Close Button -->
      <button
        class="flex-shrink-0 text-gray-400 hover:text-gray-500"
        @click="removeNotification(notification.id)"
      >
        <XMarkIcon class="h-5 w-5" />
      </button>
    </div>
  </TransitionGroup>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
  CheckCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
  InformationCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline';
import { useUiStore } from '@/stores/ui';

const uiStore = useUiStore();

const notifications = computed(() => uiStore.notifications);

const getIcon = (type: string) => {
  const icons: Record<string, any> = {
    success: CheckCircleIcon,
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon,
  };
  return icons[type] || InformationCircleIcon;
};

const borderColor = (type: string) => {
  const colors: Record<string, string> = {
    success: 'border-green-500',
    error: 'border-red-500',
    warning: 'border-yellow-500',
    info: 'border-blue-500',
  };
  return colors[type] || 'border-gray-500';
};

const iconColor = (type: string) => {
  const colors: Record<string, string> = {
    success: 'text-green-500',
    error: 'text-red-500',
    warning: 'text-yellow-500',
    info: 'text-blue-500',
  };
  return colors[type] || 'text-gray-500';
};

const removeNotification = (id: string) => {
  uiStore.removeNotification(id);
};
</script>

<style scoped>
.notification-enter-active,
.notification-leave-active {
  transition: all 0.3s ease;
}

.notification-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.notification-leave-to {
  opacity: 0;
  transform: translateX(100%);
}
</style>
