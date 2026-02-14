<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="fixed inset-0 z-50 overflow-y-auto" @click.self="close">
        <div class="flex min-h-screen items-center justify-center p-4">
          <!-- Backdrop -->
          <div class="fixed inset-0 bg-black/50 transition-opacity"></div>
          
          <!-- Modal -->
          <div 
            class="relative bg-white rounded-xl shadow-2xl max-w-md w-full mx-auto z-10"
            @click.stop
          >
            <!-- Header -->
            <div v-if="$slots.header || title" class="flex items-center justify-between p-6 border-b border-secondary-200">
              <slot name="header">
                <h3 class="text-xl font-semibold text-secondary-900">{{ title }}</h3>
              </slot>
              <button @click="close" class="text-secondary-400 hover:text-secondary-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            
            <!-- Body -->
            <div class="p-6">
              <slot />
            </div>
            
            <!-- Footer -->
            <div v-if="$slots.footer" class="flex justify-end space-x-3 p-6 border-t border-secondary-200">
              <slot name="footer" />
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
const props = defineProps({
  modelValue: Boolean,
  title: String,
  closeOnEscape: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:modelValue', 'close'])

const close = () => {
  emit('update:modelValue', false)
  emit('close')
}

// Handle escape key
if (props.closeOnEscape) {
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && props.modelValue) {
      close()
    }
  })
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
