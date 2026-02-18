<template>
  <div class="flex items-center justify-center" :class="containerClass">
    <div 
      class="spinner"
      :class="sizeClass"
      :style="{ borderTopColor: color }"
      role="status"
      aria-label="Loading"
    >
      <span class="sr-only">{{ message }}</span>
    </div>
    <p v-if="showMessage && message" class="ml-3 text-sm text-gray-600">
      {{ message }}
    </p>
  </div>
</template>

<script setup>
import { defineProps, computed } from 'vue'

const props = defineProps({
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
  },
  color: {
    type: String,
    default: '#3b82f6' // blue-600
  },
  message: {
    type: String,
    default: 'Loading...'
  },
  showMessage: {
    type: Boolean,
    default: false
  },
  fullScreen: {
    type: Boolean,
    default: false
  },
  overlay: {
    type: Boolean,
    default: false
  }
})

const sizeClass = computed(() => {
  const sizes = {
    xs: 'w-4 h-4 border-2',
    sm: 'w-6 h-6 border-2',
    md: 'w-8 h-8 border-2',
    lg: 'w-12 h-12 border-3',
    xl: 'w-16 h-16 border-4'
  }
  return sizes[props.size]
})

const containerClass = computed(() => {
  const classes = []
  
  if (props.fullScreen) {
    classes.push('min-h-screen')
  }
  
  if (props.overlay) {
    classes.push(
      'fixed inset-0 bg-white bg-opacity-75 z-50',
      'flex items-center justify-center'
    )
  }
  
  return classes.join(' ')
})
</script>

<style scoped>
.spinner {
  border-radius: 50%;
  border-style: solid;
  border-color: #e5e7eb; /* gray-200 */
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.sr-only {
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
