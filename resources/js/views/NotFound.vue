<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center">
      <h1 class="text-9xl font-bold text-gray-800">404</h1>
      <p class="text-2xl font-semibold text-gray-600 mt-4">Page Not Found</p>
      <p class="text-gray-500 mt-2">The page you are looking for doesn't exist.</p>
      <router-link
        to="/dashboard"
        class="mt-6 inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        Go to Dashboard
      </router-link>
    </div>
  </div>
</template>

<script setup>
// 404 Not Found page
</script>
