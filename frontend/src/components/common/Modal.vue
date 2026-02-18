<template>
  <transition name="modal">
    <div v-if="show" class="modal-overlay" @click="handleOverlayClick">
      <div class="modal-container" :class="sizeClass" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">{{ title }}</h3>
          <button @click="close" class="close-btn">
            <XMarkIcon class="icon" />
          </button>
        </div>
        
        <div class="modal-body">
          <slot></slot>
        </div>
        
        <div v-if="!hideFooter" class="modal-footer">
          <slot name="footer">
            <button @click="close" class="btn btn-secondary">Cancel</button>
            <button @click="$emit('confirm')" class="btn btn-primary">
              {{ confirmText }}
            </button>
          </slot>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { computed } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, required: true },
  size: { type: String, default: 'medium' },
  confirmText: { type: String, default: 'Confirm' },
  hideFooter: { type: Boolean, default: false },
  closeOnOverlay: { type: Boolean, default: true }
})

const emit = defineEmits(['close', 'confirm'])

const sizeClass = computed(() => `modal-${props.size}`)

const close = () => {
  emit('close')
}

const handleOverlayClick = () => {
  if (props.closeOnOverlay) {
    close()
  }
}
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
  z-index: 9999;
  padding: 20px;
}

.modal-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}

.modal-small {
  width: 400px;
  max-width: 100%;
}

.modal-medium {
  width: 600px;
  max-width: 100%;
}

.modal-large {
  width: 900px;
  max-width: 100%;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-title {
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.close-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  color: #9ca3af;
  transition: color 0.2s;
}

.close-btn:hover {
  color: #4b5563;
}

.icon {
  width: 24px;
  height: 24px;
}

.modal-body {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid #e5e7eb;
}

.btn {
  padding: 10px 20px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-secondary {
  background: white;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background: #f9fafb;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s;
}

.modal-enter-active .modal-container,
.modal-leave-active .modal-container {
  transition: transform 0.3s;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
  transform: scale(0.9);
}
</style>
