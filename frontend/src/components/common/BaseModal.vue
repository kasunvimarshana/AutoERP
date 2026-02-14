<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" @click.self="close">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="close"></div>
      
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start">
            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
              <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                {{ title }}
              </h3>
              <div class="mt-2">
                <slot></slot>
              </div>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
          <slot name="footer">
            <BaseButton @click="close" variant="outline">
              {{ cancelText }}
            </BaseButton>
          </slot>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import BaseButton from './BaseButton.vue'

defineProps({
  show: Boolean,
  title: String,
  cancelText: {
    type: String,
    default: 'Cancel'
  }
})

const emit = defineEmits(['close'])

const close = () => {
  emit('close')
}
</script>
