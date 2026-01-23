<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  variant?: 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info'
  size?: 'sm' | 'md' | 'lg'
  type?: 'button' | 'submit' | 'reset'
  disabled?: boolean
  loading?: boolean
  fullWidth?: boolean
  outline?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  type: 'button',
  disabled: false,
  loading: false,
  fullWidth: false,
  outline: false,
})

const buttonClasses = computed(() => {
  const baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2'
  
  // Size classes
  const sizeClasses = {
    sm: 'px-3 py-1.5 text-sm',
    md: 'px-4 py-2 text-base',
    lg: 'px-6 py-3 text-lg',
  }

  // Variant classes
  const variantClasses = props.outline
    ? {
        primary: 'border-2 border-blue-600 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900 focus:ring-blue-500',
        secondary: 'border-2 border-gray-600 text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 focus:ring-gray-500',
        success: 'border-2 border-green-600 text-green-600 hover:bg-green-50 dark:hover:bg-green-900 focus:ring-green-500',
        danger: 'border-2 border-red-600 text-red-600 hover:bg-red-50 dark:hover:bg-red-900 focus:ring-red-500',
        warning: 'border-2 border-yellow-600 text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900 focus:ring-yellow-500',
        info: 'border-2 border-cyan-600 text-cyan-600 hover:bg-cyan-50 dark:hover:bg-cyan-900 focus:ring-cyan-500',
      }
    : {
        primary: 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
        secondary: 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
        success: 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
        danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
        warning: 'bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500',
        info: 'bg-cyan-600 text-white hover:bg-cyan-700 focus:ring-cyan-500',
      }

  const disabledClasses = props.disabled || props.loading ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
  const widthClasses = props.fullWidth ? 'w-full' : ''

  return `${baseClasses} ${sizeClasses[props.size]} ${variantClasses[props.variant]} ${disabledClasses} ${widthClasses}`
})
</script>

<template>
  <button :type="type" :disabled="disabled || loading" :class="buttonClasses">
    <svg
      v-if="loading"
      class="animate-spin -ml-1 mr-2 h-5 w-5"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path
        class="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
      ></path>
    </svg>
    <slot />
  </button>
</template>
