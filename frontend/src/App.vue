<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppLayout from './layouts/AppLayout.vue'
import AuthLayout from './layouts/AuthLayout.vue'
import ErrorLayout from './layouts/ErrorLayout.vue'

const route = useRoute()

const layout = computed(() => {
  return route.meta.layout || 'default'
})

const layoutComponent = computed(() => {
  switch (layout.value) {
    case 'app':
      return AppLayout
    case 'auth':
      return AuthLayout
    case 'error':
      return ErrorLayout
    default:
      return AppLayout
  }
})
</script>

<template>
  <component :is="layoutComponent">
    <RouterView />
  </component>
</template>

<style scoped>
/* Global app styles */
</style>
