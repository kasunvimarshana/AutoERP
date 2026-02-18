<template>
  <div class="text-center py-12 px-4">
    <!-- Icon -->
    <div class="flex justify-center mb-4">
      <component 
        :is="icon" 
        v-if="icon"
        class="h-16 w-16"
        :class="iconColorClass"
        aria-hidden="true"
      />
      <svg 
        v-else
        class="h-16 w-16 text-gray-300"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        aria-hidden="true"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
        />
      </svg>
    </div>
    
    <!-- Title -->
    <h3 class="text-lg font-medium text-gray-900 mb-2">
      {{ title }}
    </h3>
    
    <!-- Description -->
    <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">
      {{ description }}
    </p>
    
    <!-- Actions -->
    <div v-if="$slots.actions || primaryAction" class="flex justify-center space-x-3">
      <slot name="actions">
        <button
          v-if="primaryAction"
          @click="primaryAction.onClick"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          <component 
            :is="primaryAction.icon" 
            v-if="primaryAction.icon"
            class="-ml-1 mr-2 h-5 w-5"
            aria-hidden="true"
          />
          {{ primaryAction.label }}
        </button>
        
        <button
          v-if="secondaryAction"
          @click="secondaryAction.onClick"
          class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          <component 
            :is="secondaryAction.icon" 
            v-if="secondaryAction.icon"
            class="-ml-1 mr-2 h-5 w-5"
            aria-hidden="true"
          />
          {{ secondaryAction.label }}
        </button>
      </slot>
    </div>
    
    <!-- Additional Content Slot -->
    <div v-if="$slots.default" class="mt-6">
      <slot></slot>
    </div>
  </div>
</template>

<script setup>
import { defineProps, computed } from 'vue'

const props = defineProps({
  icon: {
    type: [Object, Function],
    default: null
  },
  iconColor: {
    type: String,
    default: 'gray',
    validator: (value) => ['gray', 'blue', 'green', 'yellow', 'red'].includes(value)
  },
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    required: true
  },
  primaryAction: {
    type: Object,
    default: null
    // Expected shape: { label: String, onClick: Function, icon: Component }
  },
  secondaryAction: {
    type: Object,
    default: null
    // Expected shape: { label: String, onClick: Function, icon: Component }
  }
})

const iconColorClass = computed(() => {
  const colors = {
    gray: 'text-gray-400',
    blue: 'text-blue-400',
    green: 'text-green-400',
    yellow: 'text-yellow-400',
    red: 'text-red-400'
  }
  return colors[props.iconColor] || colors.gray
})
</script>
