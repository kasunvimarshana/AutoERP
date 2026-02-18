<template>
  <div class="page-header" :class="{ 'border-b border-gray-200': border }">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between py-4 px-6">
      <!-- Left Section: Title and Description -->
      <div class="flex-1 min-w-0 mb-4 sm:mb-0">
        <div class="flex items-center">
          <!-- Back Button (optional) -->
          <button
            v-if="showBack"
            @click="handleBack"
            class="mr-3 p-1 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded"
            aria-label="Go back"
          >
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          
          <!-- Icon (optional) -->
          <component 
            :is="icon" 
            v-if="icon"
            class="flex-shrink-0 h-8 w-8 mr-3"
            :class="iconColorClass"
            aria-hidden="true"
          />
          
          <div>
            <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl sm:truncate">
              {{ title }}
            </h1>
            <p v-if="description" class="mt-1 text-sm text-gray-500">
              {{ description }}
            </p>
          </div>
        </div>
        
        <!-- Breadcrumbs Slot -->
        <div v-if="$slots.breadcrumbs" class="mt-2">
          <slot name="breadcrumbs"></slot>
        </div>
      </div>
      
      <!-- Right Section: Actions -->
      <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-3">
        <!-- Secondary Actions Slot -->
        <slot name="secondaryActions"></slot>
        
        <!-- Primary Action Button -->
        <button
          v-if="primaryAction"
          @click="primaryAction.onClick"
          :disabled="primaryAction.disabled"
          class="inline-flex items-center justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <component 
            :is="primaryAction.icon" 
            v-if="primaryAction.icon"
            class="-ml-1 mr-2 h-5 w-5"
            aria-hidden="true"
          />
          {{ primaryAction.label }}
        </button>
        
        <!-- Primary Actions Slot (for custom buttons) -->
        <slot name="primaryActions"></slot>
      </div>
    </div>
    
    <!-- Tabs/Filters Section (optional) -->
    <div v-if="$slots.tabs || tabs" class="px-6 -mb-px">
      <slot name="tabs">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
          <button
            v-for="tab in tabs"
            :key="tab.name"
            @click="handleTabClick(tab)"
            :class="[
              tab.current
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
            ]"
            :aria-current="tab.current ? 'page' : undefined"
          >
            {{ tab.name }}
            <span
              v-if="tab.count !== undefined"
              :class="[
                tab.current ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-900',
                'ml-3 py-0.5 px-2.5 rounded-full text-xs font-medium'
              ]"
            >
              {{ tab.count }}
            </span>
          </button>
        </nav>
      </slot>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits, computed } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    default: ''
  },
  icon: {
    type: [Object, Function],
    default: null
  },
  iconColor: {
    type: String,
    default: 'blue',
    validator: (value) => ['gray', 'blue', 'green', 'yellow', 'red', 'purple'].includes(value)
  },
  primaryAction: {
    type: Object,
    default: null
    // Expected shape: { label: String, onClick: Function, icon: Component, disabled: Boolean }
  },
  tabs: {
    type: Array,
    default: null
    // Expected shape: [{ name: String, current: Boolean, count: Number }]
  },
  showBack: {
    type: Boolean,
    default: false
  },
  border: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['back', 'tab-click'])
const router = useRouter()

const iconColorClass = computed(() => {
  const colors = {
    gray: 'text-gray-500',
    blue: 'text-blue-500',
    green: 'text-green-500',
    yellow: 'text-yellow-500',
    red: 'text-red-500',
    purple: 'text-purple-500'
  }
  return colors[props.iconColor] || colors.blue
})

const handleBack = () => {
  emit('back')
  if (window.history.length > 1) {
    router.go(-1)
  } else {
    router.push('/')
  }
}

const handleTabClick = (tab) => {
  emit('tab-click', tab)
}
</script>

<style scoped>
.page-header {
  background-color: white;
}
</style>
