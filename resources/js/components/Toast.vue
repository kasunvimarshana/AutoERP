<template>
  <teleport to="body">
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
      <transition-group name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="toast show"
          :class="`bg-${toast.type}`"
          role="alert"
          aria-live="assertive"
          aria-atomic="true"
        >
          <div class="toast-header" :class="`bg-${toast.type} text-white`">
            <i class="fas mr-2" :class="getIcon(toast.type)"></i>
            <strong class="me-auto">{{ getTitle(toast.type) }}</strong>
            <button
              type="button"
              class="btn-close btn-close-white"
              @click="removeToast(toast.id)"
              aria-label="Close"
            ></button>
          </div>
          <div class="toast-body text-white">
            {{ toast.message }}
          </div>
        </div>
      </transition-group>
    </div>
  </teleport>
</template>

<script setup>
import { computed } from 'vue';
import { useToastStore } from '@/stores/toast';
import { useI18n } from 'vue-i18n';

const toastStore = useToastStore();
const { t } = useI18n();

const toasts = computed(() => toastStore.toasts);

const getIcon = (type) => {
  const icons = {
    success: 'fa-check-circle',
    error: 'fa-times-circle',
    warning: 'fa-exclamation-triangle',
    info: 'fa-info-circle',
  };
  return icons[type] || icons.info;
};

const getTitle = (type) => {
  const titles = {
    success: t('common.success'),
    error: t('common.error'),
    warning: t('common.warning'),
    info: t('common.info'),
  };
  return titles[type] || titles.info;
};

const removeToast = (id) => {
  toastStore.removeToast(id);
};
</script>

<style scoped>
.toast-container {
  min-width: 300px;
  max-width: 400px;
}

.toast {
  margin-bottom: 0.5rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

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
  transform: translateX(100%);
}

.bg-success {
  background-color: #28a745 !important;
}

.bg-error {
  background-color: #dc3545 !important;
}

.bg-warning {
  background-color: #ffc107 !important;
}

.bg-info {
  background-color: #17a2b8 !important;
}
</style>
