<script setup lang="ts">
import { appConfig } from '@/config/app'
</script>

<template>
  <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-4 px-6">
    <div class="flex flex-col md:flex-row items-center justify-between text-sm text-gray-600 dark:text-gray-400">
      <div class="mb-2 md:mb-0">
        <p>&copy; {{ new Date().getFullYear() }} {{ appConfig.name }}. All rights reserved.</p>
      </div>
      <div class="flex space-x-4">
        <span>Version {{ appConfig.version }}</span>
        <span>•</span>
        <a href="#" class="hover:text-blue-600 dark:hover:text-blue-400">Support</a>
        <span>•</span>
        <a href="#" class="hover:text-blue-600 dark:hover:text-blue-400">Documentation</a>
      </div>
    </div>
  </footer>
</template>
