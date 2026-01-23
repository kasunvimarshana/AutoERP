<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

const goHome = () => {
  router.push('/')
}
</script>

<template>
  <div class="text-center">
    <h1 class="text-6xl font-bold text-gray-900 dark:text-white mb-4">404</h1>
    <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-300 mb-4">Page Not Found</h2>
    <p class="text-gray-600 dark:text-gray-400 mb-8">
      The page you are looking for doesn't exist or has been moved.
    </p>
    <button
      @click="goHome"
      class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
    >
      Go to Homepage
    </button>
  </div>
</template>
