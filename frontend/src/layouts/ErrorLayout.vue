<script setup lang="ts">
</script>

<template>
  <div class="error-layout min-h-screen bg-gray-100 dark:bg-gray-900 flex items-center justify-center p-4">
    <div class="error-content text-center">
      <slot />
      
      <div class="mt-8">
        <router-link 
          to="/" 
          class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          Go to Home
        </router-link>
      </div>
    </div>
  </div>
</template>

<style scoped>
.error-layout {
  background-image: 
    radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.3), transparent 50%),
    radial-gradient(circle at 80% 80%, rgba(255, 111, 97, 0.3), transparent 50%);
}
</style>
