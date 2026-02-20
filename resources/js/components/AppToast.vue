<template>
  <Teleport to="body">
    <div class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 pointer-events-none">
      <TransitionGroup
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
      >
        <div
          v-for="toast in notifications.toasts"
          :key="toast.id"
          class="pointer-events-auto flex items-start gap-3 rounded-lg shadow-lg px-4 py-3 min-w-[280px] max-w-sm"
          :class="toastClass(toast.type)"
        >
          <span class="text-lg shrink-0">{{ toastIcon(toast.type) }}</span>
          <p class="text-sm flex-1">{{ toast.message }}</p>
          <button
            class="shrink-0 opacity-60 hover:opacity-100 text-sm leading-none"
            @click="notifications.remove(toast.id)"
          >✕</button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { useNotificationStore } from '@/stores/notifications';
import type { ToastType } from '@/stores/notifications';

const notifications = useNotificationStore();

function toastClass(type: ToastType): string {
  return {
    success: 'bg-green-50 border border-green-200 text-green-800',
    error: 'bg-red-50 border border-red-200 text-red-800',
    warning: 'bg-yellow-50 border border-yellow-200 text-yellow-800',
    info: 'bg-blue-50 border border-blue-200 text-blue-800',
  }[type];
}

function toastIcon(type: ToastType): string {
  return {
    success: '✅',
    error: '❌',
    warning: '⚠️',
    info: 'ℹ️',
  }[type];
}
</script>
