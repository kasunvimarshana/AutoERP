<template>
  <div :class="cardClasses">
    <div v-if="$slots.header || title" class="px-6 py-4 border-b border-gray-200">
      <slot name="header">
        <h3 class="text-lg font-medium text-gray-900">{{ title }}</h3>
      </slot>
    </div>
    
    <div :class="bodyClasses">
      <slot />
    </div>
    
    <div v-if="$slots.footer" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: {
    type: String,
    default: '',
  },
  padding: {
    type: Boolean,
    default: true,
  },
  shadow: {
    type: String,
    default: 'md',
    validator: (value) => ['none', 'sm', 'md', 'lg', 'xl'].includes(value),
  },
  hover: {
    type: Boolean,
    default: false,
  },
});

const cardClasses = computed(() => {
  const baseClasses = 'bg-white rounded-lg overflow-hidden';
  const shadowClasses = {
    none: '',
    sm: 'shadow-sm',
    md: 'shadow-md',
    lg: 'shadow-lg',
    xl: 'shadow-xl',
  };
  const hoverClass = props.hover ? 'hover:shadow-lg transition-shadow duration-200' : '';
  
  return `${baseClasses} ${shadowClasses[props.shadow]} ${hoverClass}`;
});

const bodyClasses = computed(() => {
  return props.padding ? 'px-6 py-4' : '';
});
</script>
