<script setup lang="ts">
import { appConfig } from '@/config/app'
</script>

<template>
  <div class="auth-layout min-h-screen bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center p-4">
    <div class="auth-card bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-8 w-full max-w-md">
      <!-- Logo/Brand -->
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          {{ appConfig.name }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
          Vehicle Service Center Management
        </p>
      </div>
      
      <!-- Auth content -->
      <slot />
      
      <!-- Footer -->
      <div class="mt-8 text-center text-sm text-gray-600 dark:text-gray-400">
        <p>&copy; {{ new Date().getFullYear() }} {{ appConfig.name }}. All rights reserved.</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auth-layout {
  background-image: 
    linear-gradient(135deg, rgba(99, 102, 241, 0.9), rgba(139, 92, 246, 0.9)),
    url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
  background-size: cover;
  background-position: center;
}

.auth-card {
  animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
