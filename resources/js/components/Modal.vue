<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="relative z-50"
      @click.self="handleClose"
    >
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

      <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <div
            class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full"
            :class="sizeClasses"
          >
            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
              <div class="flex items-start justify-between mb-4">
                <h3 class="text-lg font-semibold leading-6 text-gray-900">
                  {{ title }}
                </h3>
                <button
                  type="button"
                  class="rounded-md bg-white text-gray-400 hover:text-gray-500"
                  @click="handleClose"
                >
                  <span class="sr-only">Close</span>
                  <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              <div>
                <slot></slot>
              </div>
            </div>
            <div v-if="hasFooter" class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
              <slot name="footer"></slot>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, useSlots } from 'vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: '',
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value),
  },
});

const emit = defineEmits(['update:modelValue', 'close']);

const slots = useSlots();
const hasFooter = computed(() => !!slots.footer);

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
  };
  return sizes[props.size];
});

const handleClose = () => {
  emit('update:modelValue', false);
  emit('close');
};
</script>
