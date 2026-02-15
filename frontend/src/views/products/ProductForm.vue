<template>
  <div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-6">{{ isEdit ? 'Edit Product' : 'Create Product' }}</h1>
      <div class="bg-white shadow rounded-lg p-6">
        <p class="text-gray-600">Product form component - Coming soon</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const isEdit = computed(() => !!route.params.id)
</script>
