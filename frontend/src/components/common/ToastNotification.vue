<template>
  <teleport to="body">
    <div class="toast-container">
      <transition-group name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="[
            'toast',
            `toast-${toast.type}`,
            { 'toast-dismissible': toast.dismissible }
          ]"
          role="alert"
          :aria-live="toast.type === 'error' ? 'assertive' : 'polite'"
        >
          <div class="toast-icon">
            <!-- Success Icon -->
            <svg
              v-if="toast.type === 'success'"
              class="w-5 h-5"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clip-rule="evenodd"
              />
            </svg>

            <!-- Error Icon -->
            <svg
              v-else-if="toast.type === 'error'"
              class="w-5 h-5"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                clip-rule="evenodd"
              />
            </svg>

            <!-- Warning Icon -->
            <svg
              v-else-if="toast.type === 'warning'"
              class="w-5 h-5"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                clip-rule="evenodd"
              />
            </svg>

            <!-- Info Icon -->
            <svg
              v-else
              class="w-5 h-5"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                clip-rule="evenodd"
              />
            </svg>
          </div>

          <div class="toast-content">
            <div
              v-if="toast.title"
              class="toast-title"
            >
              {{ toast.title }}
            </div>
            <div class="toast-message">
              {{ toast.message }}
            </div>
            <div
              v-if="toast.action"
              class="toast-action"
            >
              <button
                class="toast-action-button"
                @click="handleAction(toast)"
              >
                {{ toast.action.label }}
              </button>
            </div>
          </div>

          <button
            v-if="toast.dismissible"
            class="toast-close"
            aria-label="Close notification"
            @click="removeToast(toast.id)"
          >
            <svg
              class="w-4 h-4"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clip-rule="evenodd"
              />
            </svg>
          </button>

          <!-- Progress bar for auto-dismiss -->
          <div
            v-if="toast.duration && toast.duration > 0"
            class="toast-progress"
            :style="{ animationDuration: `${toast.duration}ms` }"
          />
        </div>
      </transition-group>
    </div>
  </teleport>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

export interface Toast {
  id: string;
  type: 'success' | 'error' | 'warning' | 'info';
  title?: string;
  message: string;
  duration?: number; // 0 for persistent
  dismissible?: boolean;
  action?: {
    label: string;
    callback: () => void;
  };
}

const toasts = ref<Toast[]>([]);
let toastIdCounter = 0;

const generateId = () => {
  // Use crypto.randomUUID if available, fallback to timestamp-based ID
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return `toast-${crypto.randomUUID()}`;
  }
  return `toast-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
};

const addToast = (toast: Omit<Toast, 'id'>) => {
  const id = generateId();
  const newToast: Toast = {
    id,
    dismissible: true,
    duration: 5000,
    ...toast,
  };

  toasts.value.push(newToast);

  // Auto-remove after duration
  if (newToast.duration && newToast.duration > 0) {
    setTimeout(() => {
      removeToast(id);
    }, newToast.duration);
  }

  return id;
};

const removeToast = (id: string) => {
  const index = toasts.value.findIndex(t => t.id === id);
  if (index !== -1) {
    toasts.value.splice(index, 1);
  }
};

const removeAllToasts = () => {
  toasts.value = [];
};

const handleAction = (toast: Toast) => {
  if (toast.action?.callback) {
    toast.action.callback();
  }
  removeToast(toast.id);
};

// Listen for global toast events
const handleToastEvent = (event: Event) => {
  const customEvent = event as CustomEvent;
  addToast(customEvent.detail);
};

onMounted(() => {
  window.addEventListener('show-toast', handleToastEvent);
  window.addEventListener('show-notification', handleToastEvent);
});

onUnmounted(() => {
  window.removeEventListener('show-toast', handleToastEvent);
  window.removeEventListener('show-notification', handleToastEvent);
});

// Expose methods for parent components
defineExpose({
  addToast,
  removeToast,
  removeAllToasts,
});
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 1rem;
  right: 1rem;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  max-width: 400px;
  pointer-events: none;
}

.toast {
  display: flex;
  align-items: start;
  gap: 0.75rem;
  padding: 1rem;
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  pointer-events: auto;
  position: relative;
  overflow: hidden;
  min-width: 300px;
  max-width: 400px;
}

.toast-icon {
  flex-shrink: 0;
  margin-top: 0.125rem;
}

.toast-content {
  flex: 1;
  min-width: 0;
}

.toast-title {
  font-weight: 600;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
  color: #111827;
}

.toast-message {
  font-size: 0.875rem;
  color: #6b7280;
  word-wrap: break-word;
}

.toast-action {
  margin-top: 0.5rem;
}

.toast-action-button {
  font-size: 0.875rem;
  font-weight: 500;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  border: none;
  cursor: pointer;
  transition: background-color 0.2s;
}

.toast-close {
  flex-shrink: 0;
  background: none;
  border: none;
  cursor: pointer;
  color: #9ca3af;
  padding: 0.25rem;
  border-radius: 0.25rem;
  transition: all 0.2s;
}

.toast-close:hover {
  background: #f3f4f6;
  color: #6b7280;
}

.toast-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  width: 100%;
  background: currentColor;
  opacity: 0.3;
  transform-origin: left;
  animation: toast-progress linear;
}

@keyframes toast-progress {
  from {
    transform: scaleX(1);
  }
  to {
    transform: scaleX(0);
  }
}

/* Toast types */
.toast-success {
  border-left: 4px solid #10b981;
}

.toast-success .toast-icon {
  color: #10b981;
}

.toast-success .toast-action-button {
  background: #d1fae5;
  color: #065f46;
}

.toast-success .toast-action-button:hover {
  background: #a7f3d0;
}

.toast-error {
  border-left: 4px solid #ef4444;
}

.toast-error .toast-icon {
  color: #ef4444;
}

.toast-error .toast-action-button {
  background: #fee2e2;
  color: #991b1b;
}

.toast-error .toast-action-button:hover {
  background: #fecaca;
}

.toast-warning {
  border-left: 4px solid #f59e0b;
}

.toast-warning .toast-icon {
  color: #f59e0b;
}

.toast-warning .toast-action-button {
  background: #fef3c7;
  color: #92400e;
}

.toast-warning .toast-action-button:hover {
  background: #fde68a;
}

.toast-info {
  border-left: 4px solid #3b82f6;
}

.toast-info .toast-icon {
  color: #3b82f6;
}

.toast-info .toast-action-button {
  background: #dbeafe;
  color: #1e3a8a;
}

.toast-info .toast-action-button:hover {
  background: #bfdbfe;
}

/* Animations */
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%) scale(0.9);
}

/* Mobile responsive */
@media (max-width: 640px) {
  .toast-container {
    top: auto;
    bottom: 1rem;
    right: 1rem;
    left: 1rem;
    max-width: none;
  }

  .toast {
    min-width: 0;
    max-width: none;
  }
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .toast {
    background: #1f2937;
  }

  .toast-title {
    color: #f9fafb;
  }

  .toast-message {
    color: #d1d5db;
  }

  .toast-close {
    color: #6b7280;
  }

  .toast-close:hover {
    background: #374151;
    color: #9ca3af;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .toast-enter-active,
  .toast-leave-active {
    transition: none;
  }

  .toast-progress {
    animation: none;
  }
}
</style>
