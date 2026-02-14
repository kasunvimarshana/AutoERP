<template>
  <div :class="cardClasses">
    <div v-if="$slots.header || title" class="border-b border-secondary-200 pb-4 mb-4">
      <slot name="header">
        <h3 class="text-lg font-semibold text-secondary-900">{{ title }}</h3>
      </slot>
    </div>
    
    <div :class="bodyClasses">
      <slot />
    </div>
    
    <div v-if="$slots.footer" class="border-t border-secondary-200 pt-4 mt-4">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: String,
  padding: {
    type: String,
    default: 'md',
    validator: (value) => ['none', 'sm', 'md', 'lg'].includes(value)
  }
})

const cardClasses = computed(() => {
  const paddingMap = {
    none: 'p-0',
    sm: 'p-4',
    md: 'p-6',
    lg: 'p-8'
  }
  
  return ['card', paddingMap[props.padding]].join(' ')
})

const bodyClasses = computed(() => {
  return props.padding === 'none' ? 'p-6' : ''
})
</script>
