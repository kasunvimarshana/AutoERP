<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="modelValue"
        class="modal-overlay"
        role="dialog"
        :aria-modal="true"
        :aria-labelledby="titleId"
        :aria-describedby="descriptionId"
        @click.self="handleOverlayClick"
        @keydown.esc="handleEscape"
      >
        <div
          ref="modalRef"
          class="modal-container"
          :class="sizeClass"
          role="document"
        >
          <!-- Header -->
          <div class="modal-header">
            <h2 :id="titleId" class="modal-title">
              <slot name="title">{{ title }}</slot>
            </h2>
            <button
              type="button"
              class="modal-close"
              :aria-label="closeLabel"
              @click="handleClose"
            >
              <span aria-hidden="true">✕</span>
            </button>
          </div>

          <!-- Body -->
          <div :id="descriptionId" class="modal-body">
            <slot />
          </div>

          <!-- Footer -->
          <div v-if="$slots.footer || showActions" class="modal-footer">
            <slot name="footer">
              <button
                v-if="showCancel"
                type="button"
                class="btn btn-secondary"
                @click="handleCancel"
              >
                {{ cancelLabel }}
              </button>
              <button
                v-if="showConfirm"
                type="button"
                class="btn btn-primary"
                :disabled="confirmDisabled"
                @click="handleConfirm"
              >
                {{ confirmLabel }}
              </button>
            </slot>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { useAccessibility } from '../../composables/useAccessibility';

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true
  },
  title: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'xl', 'full'].includes(value)
  },
  closeOnOverlay: {
    type: Boolean,
    default: true
  },
  closeOnEscape: {
    type: Boolean,
    default: true
  },
  showActions: {
    type: Boolean,
    default: false
  },
  showCancel: {
    type: Boolean,
    default: true
  },
  showConfirm: {
    type: Boolean,
    default: true
  },
  cancelLabel: {
    type: String,
    default: 'Cancel'
  },
  confirmLabel: {
    type: String,
    default: 'Confirm'
  },
  confirmDisabled: {
    type: Boolean,
    default: false
  },
  closeLabel: {
    type: String,
    default: 'Close dialog'
  }
});

const emit = defineEmits(['update:modelValue', 'close', 'cancel', 'confirm']);

const { setupFocusTrap, removeFocusTrap, announce, generateId } = useAccessibility();

const modalRef = ref(null);
const titleId = generateId('modal-title');
const descriptionId = generateId('modal-description');
const previousActiveElement = ref(null);
const focusTrapCleanup = ref(null);

const sizeClass = computed(() => `modal-${props.size}`);

const handleClose = () => {
  emit('update:modelValue', false);
  emit('close');
};

const handleCancel = () => {
  emit('cancel');
  handleClose();
};

const handleConfirm = () => {
  emit('confirm');
};

const handleOverlayClick = () => {
  if (props.closeOnOverlay) {
    handleClose();
  }
};

const handleEscape = () => {
  if (props.closeOnEscape) {
    handleClose();
  }
};

// Watch for modal visibility
watch(() => props.modelValue, async (isOpen) => {
  if (isOpen) {
    // Store current focus
    previousActiveElement.value = document.activeElement;
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
    
    // Wait for modal to render
    await nextTick();
    
    // Setup focus trap
    if (modalRef.value) {
      focusTrapCleanup.value = setupFocusTrap(modalRef.value);
    }
    
    // Announce modal opening
    announce(`${props.title || 'Dialog'} opened`, 'assertive');
  } else {
    // Restore body scroll
    document.body.style.overflow = '';
    
    // Remove focus trap
    if (focusTrapCleanup.value) {
      focusTrapCleanup.value();
      focusTrapCleanup.value = null;
    }
    
    // Restore focus
    if (previousActiveElement.value) {
      previousActiveElement.value.focus();
      previousActiveElement.value = null;
    }
    
    // Announce modal closing
    announce('Dialog closed');
  }
});
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-container {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  width: 100%;
}

.modal-sm {
  max-width: 400px;
}

.modal-md {
  max-width: 600px;
}

.modal-lg {
  max-width: 800px;
}

.modal-xl {
  max-width: 1200px;
}

.modal-full {
  max-width: 100%;
  max-height: 100vh;
  height: 100vh;
  margin: 0;
  border-radius: 0;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.modal-close {
  padding: 0.5rem;
  border: none;
  background: transparent;
  cursor: pointer;
  color: #6b7280;
  font-size: 1.5rem;
  line-height: 1;
  transition: color 0.15s;
}

.modal-close:hover {
  color: #111827;
}

.modal-close:focus {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
  border-radius: 0.25rem;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.btn {
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s;
  border: none;
}

.btn:focus {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2563eb;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: #e5e7eb;
  color: #374151;
}

.btn-secondary:hover {
  background-color: #d1d5db;
}

/* Transitions */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .modal-container,
.modal-leave-active .modal-container {
  transition: transform 0.3s ease;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
  transform: scale(0.95);
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .modal-enter-active,
  .modal-leave-active,
  .modal-enter-active .modal-container,
  .modal-leave-active .modal-container {
    transition: none;
  }
}

/* Screen reader only class */
:global(.sr-only) {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}
</style>
