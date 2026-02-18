<template>
  <div class="animate-pulse space-y-4 p-6">
    <!-- Card Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="h-8 bg-gray-200 rounded w-1/3"></div>
      <div class="h-10 bg-gray-200 rounded w-24"></div>
    </div>
    
    <!-- Card Content -->
    <div class="space-y-4">
      <div v-for="i in lines" :key="i" class="space-y-2">
        <div class="h-4 bg-gray-200 rounded" :style="{ width: getWidth(i) }"></div>
      </div>
    </div>
    
    <!-- Card Footer (optional) -->
    <div v-if="showFooter" class="flex items-center justify-between pt-4 mt-6 border-t border-gray-200">
      <div class="h-4 bg-gray-200 rounded w-24"></div>
      <div class="h-8 bg-gray-200 rounded w-20"></div>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

defineProps({
  lines: {
    type: Number,
    default: 4
  },
  showFooter: {
    type: Boolean,
    default: false
  }
})

const getWidth = (index) => {
  const widths = ['100%', '95%', '85%', '90%', '80%']
  return widths[index % widths.length]
}
</script>
