<template>
  <div class="animate-pulse">
    <!-- Table Header Skeleton -->
    <div v-if="showHeader" class="bg-gray-200 h-12 rounded-t-lg mb-2"></div>
    
    <!-- Table Rows Skeleton -->
    <div v-for="i in rows" :key="i" class="border-b border-gray-200 last:border-0">
      <div class="flex items-center space-x-4 py-4 px-6">
        <!-- Checkbox Column (if selectable) -->
        <div v-if="selectable" class="flex-shrink-0">
          <div class="w-4 h-4 bg-gray-200 rounded"></div>
        </div>
        
        <!-- Data Columns -->
        <div 
          v-for="col in columns" 
          :key="col" 
          class="flex-1"
        >
          <div class="h-4 bg-gray-200 rounded" :style="{ width: getRandomWidth() }"></div>
        </div>
        
        <!-- Actions Column -->
        <div v-if="showActions" class="flex-shrink-0 flex space-x-2">
          <div class="w-8 h-8 bg-gray-200 rounded"></div>
          <div class="w-8 h-8 bg-gray-200 rounded"></div>
        </div>
      </div>
    </div>
    
    <!-- Pagination Skeleton -->
    <div v-if="showPagination" class="flex items-center justify-between px-6 py-4 bg-gray-50">
      <div class="h-4 bg-gray-200 rounded w-32"></div>
      <div class="flex space-x-2">
        <div class="h-8 w-20 bg-gray-200 rounded"></div>
        <div class="h-8 w-20 bg-gray-200 rounded"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

defineProps({
  rows: {
    type: Number,
    default: 5
  },
  columns: {
    type: Number,
    default: 4
  },
  showHeader: {
    type: Boolean,
    default: true
  },
  showActions: {
    type: Boolean,
    default: true
  },
  showPagination: {
    type: Boolean,
    default: true
  },
  selectable: {
    type: Boolean,
    default: false
  }
})

// Generate random widths for more natural skeleton appearance
const getRandomWidth = () => {
  const widths = ['60%', '70%', '80%', '90%', '100%']
  return widths[Math.floor(Math.random() * widths.length)]
}
</script>

<style scoped>
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
