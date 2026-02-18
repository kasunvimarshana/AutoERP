<template>
  <TransitionRoot
    appear
    :show="isOpen"
    as="template"
  >
    <Dialog
      as="div"
      class="relative z-50"
      @close="handleClose"
    >
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black bg-opacity-25" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel
              :class="[
                'w-full transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all',
                sizeClasses,
              ]"
            >
              <DialogTitle
                v-if="title"
                as="h3"
                class="text-lg font-medium leading-6 text-gray-900 mb-4"
              >
                {{ title }}
              </DialogTitle>

              <div class="mt-2">
                <slot />
              </div>

              <div
                v-if="showFooter"
                class="mt-6 flex justify-end space-x-3"
              >
                <slot name="footer">
                  <button
                    type="button"
                    class="btn btn-outline"
                    @click="handleClose"
                  >
                    {{ cancelText }}
                  </button>
                  <button
                    v-if="showConfirm"
                    type="button"
                    class="btn btn-primary"
                    @click="handleConfirm"
                  >
                    {{ confirmText }}
                  </button>
                </slot>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogPanel,
  DialogTitle,
} from '@headlessui/vue';

interface Props {
  modelValue?: boolean;
  title?: string;
  size?: 'sm' | 'md' | 'lg' | 'xl' | 'full';
  showFooter?: boolean;
  showConfirm?: boolean;
  confirmText?: string;
  cancelText?: string;
  persistent?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: true,
  size: 'md',
  showFooter: true,
  showConfirm: true,
  confirmText: 'Confirm',
  cancelText: 'Cancel',
  persistent: false,
});

const emit = defineEmits(['update:modelValue', 'close', 'confirm']);

const isOpen = computed(() => props.modelValue);

const sizeClasses = computed(() => {
  const sizes: Record<string, string> = {
    sm: 'max-w-md',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
    full: 'max-w-7xl',
  };
  return sizes[props.size] || sizes.md;
});

const handleClose = () => {
  if (!props.persistent) {
    emit('update:modelValue', false);
    emit('close');
  }
};

const handleConfirm = () => {
  emit('confirm');
};
</script>
