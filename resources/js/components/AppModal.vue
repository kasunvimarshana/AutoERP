<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        class="fixed inset-0 z-40 flex items-center justify-center p-4 bg-black/50"
        @mousedown.self="close"
      >
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div
            v-if="modelValue"
            class="relative bg-white rounded-xl shadow-xl w-full flex flex-col max-h-[90vh]"
            :class="sizeClass"
            role="dialog"
            :aria-label="title"
          >
            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 shrink-0">
              <h3 class="text-base font-semibold text-gray-900">{{ title }}</h3>
              <button
                type="button"
                class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded"
                @click="close"
                aria-label="Close"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <div class="overflow-y-auto p-5 flex-1">
              <slot />
            </div>

            <!-- Footer -->
            <div
              v-if="$slots.footer"
              class="px-5 py-4 border-t border-gray-200 flex items-center justify-end gap-3 shrink-0 bg-gray-50 rounded-b-xl"
            >
              <slot name="footer" />
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    modelValue: boolean;
    title: string;
    size?: 'sm' | 'md' | 'lg' | 'xl';
  }>(),
  { size: 'md' },
);

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
}>();

const sizeClass = computed(() => ({
  sm: 'max-w-sm',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
}[props.size]));

function close(): void {
  emit('update:modelValue', false);
}
</script>
