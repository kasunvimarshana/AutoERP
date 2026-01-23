<script setup lang="ts">
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const goBack = () => {
  const from = route.query.from as string
  if (from) {
    router.push(from)
  } else {
    router.push('/dashboard')
  }
}
</script>

<template>
  <div class="text-center">
    <h1 class="text-6xl font-bold text-red-600 dark:text-red-400 mb-4">403</h1>
    <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-300 mb-4">Unauthorized Access</h2>
    <p class="text-gray-600 dark:text-gray-400 mb-8">
      You don't have permission to access this resource.
    </p>
    <button
      @click="goBack"
      class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
    >
      Go Back
    </button>
  </div>
</template>
