<template>
  <teleport to="body">
    <transition name="modal">
      <div v-if="isVisible" class="modal fade show d-block" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header" :class="`bg-${type}`">
              <h5 class="modal-title text-white" id="confirmModalLabel">
                <i class="fas mr-2" :class="getIcon()"></i>
                {{ title || $t('common.confirm') }}
              </h5>
              <button 
                type="button" 
                class="close text-white" 
                @click="cancel"
                aria-label="Close"
              >
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <p>{{ message }}</p>
            </div>
            <div class="modal-footer">
              <button 
                type="button" 
                class="btn btn-secondary" 
                @click="cancel"
              >
                {{ cancelText || $t('common.cancel') }}
              </button>
              <button 
                type="button" 
                class="btn" 
                :class="`btn-${type}`"
                @click="confirm"
                :disabled="loading"
              >
                <span v-if="loading" class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span>
                {{ confirmText || $t('common.confirm') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>
    <div v-if="isVisible" class="modal-backdrop fade show"></div>
  </teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  isVisible: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: '',
  },
  message: {
    type: String,
    required: true,
  },
  type: {
    type: String,
    default: 'warning',
    validator: (value) => ['primary', 'secondary', 'success', 'danger', 'warning', 'info'].includes(value),
  },
  confirmText: {
    type: String,
    default: '',
  },
  cancelText: {
    type: String,
    default: '',
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['confirm', 'cancel']);

const getIcon = () => {
  const icons = {
    primary: 'fa-info-circle',
    secondary: 'fa-question-circle',
    success: 'fa-check-circle',
    danger: 'fa-exclamation-triangle',
    warning: 'fa-exclamation-triangle',
    info: 'fa-info-circle',
  };
  return icons[props.type] || icons.warning;
};

const confirm = () => {
  emit('confirm');
};

const cancel = () => {
  emit('cancel');
};
</script>

<style scoped>
.modal {
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-backdrop {
  z-index: 1040;
}

.modal {
  z-index: 1050;
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .modal-dialog,
.modal-leave-active .modal-dialog {
  transition: transform 0.3s ease;
}

.modal-enter-from .modal-dialog,
.modal-leave-to .modal-dialog {
  transform: translateY(-50px);
}
</style>
