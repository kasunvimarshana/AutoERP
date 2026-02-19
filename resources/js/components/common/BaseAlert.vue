<template>
  <div v-if="show" :class="alertClasses" role="alert">
    <div class="flex">
      <div class="flex-shrink-0">
        <component :is="iconComponent" class="h-5 w-5" aria-hidden="true" />
      </div>
      <div class="ml-3 flex-1">
        <p v-if="title" class="text-sm font-medium" :class="titleColorClass">
          {{ title }}
        </p>
        <div class="text-sm" :class="messageColorClass">
          <slot>{{ message }}</slot>
        </div>
      </div>
      <div v-if="dismissible" class="ml-auto pl-3">
        <div class="-mx-1.5 -my-1.5">
          <button
            type="button"
            :class="closeButtonClasses"
            @click="handleDismiss"
          >
            <span class="sr-only">Dismiss</span>
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { 
  CheckCircleIcon, 
  ExclamationTriangleIcon, 
  InformationCircleIcon, 
  XCircleIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
  variant: {
    type: String,
    default: 'info',
    validator: (value) => ['success', 'danger', 'warning', 'info'].includes(value),
  },
  title: {
    type: String,
    default: '',
  },
  message: {
    type: String,
    default: '',
  },
  dismissible: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['dismiss']);

const show = ref(true);

const iconComponent = computed(() => {
  const icons = {
    success: CheckCircleIcon,
    danger: XCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon,
  };
  return icons[props.variant];
});

const alertClasses = computed(() => {
  const baseClasses = 'rounded-md p-4';
  const variantClasses = {
    success: 'bg-green-50',
    danger: 'bg-red-50',
    warning: 'bg-yellow-50',
    info: 'bg-blue-50',
  };
  return `${baseClasses} ${variantClasses[props.variant]}`;
});

const titleColorClass = computed(() => {
  const classes = {
    success: 'text-green-800',
    danger: 'text-red-800',
    warning: 'text-yellow-800',
    info: 'text-blue-800',
  };
  return classes[props.variant];
});

const messageColorClass = computed(() => {
  const classes = {
    success: 'text-green-700',
    danger: 'text-red-700',
    warning: 'text-yellow-700',
    info: 'text-blue-700',
  };
  return classes[props.variant];
});

const closeButtonClasses = computed(() => {
  const baseClasses = 'inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2';
  const variantClasses = {
    success: 'bg-green-50 text-green-500 hover:bg-green-100 focus:ring-green-600 focus:ring-offset-green-50',
    danger: 'bg-red-50 text-red-500 hover:bg-red-100 focus:ring-red-600 focus:ring-offset-red-50',
    warning: 'bg-yellow-50 text-yellow-500 hover:bg-yellow-100 focus:ring-yellow-600 focus:ring-offset-yellow-50',
    info: 'bg-blue-50 text-blue-500 hover:bg-blue-100 focus:ring-blue-600 focus:ring-offset-blue-50',
  };
  return `${baseClasses} ${variantClasses[props.variant]}`;
});

function handleDismiss() {
  show.value = false;
  emit('dismiss');
}
</script>
