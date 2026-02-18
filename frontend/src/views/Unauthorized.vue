<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 text-center">
      <div>
        <svg
          class="mx-auto h-24 w-24 text-red-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Access Denied
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          You don't have permission to access this page
        </p>
        
        <div v-if="requiredPermission" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
          <p class="text-sm text-red-800">
            <span class="font-semibold">Required Permission:</span>
            <code class="ml-2 px-2 py-1 bg-red-100 rounded text-xs">{{ requiredPermission }}</code>
          </p>
        </div>
        
        <div class="mt-6 space-y-3">
          <button
            @click="goBack"
            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Go Back
          </button>
          
          <router-link
            to="/dashboard"
            class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Go to Dashboard
          </router-link>
          
          <button
            @click="contactAdmin"
            class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Contact Administrator
          </button>
        </div>
      </div>
      
      <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-md">
        <p class="text-xs text-blue-800">
          <span class="font-semibold">Need access?</span> Contact your system administrator to request the necessary permissions for this feature.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useNotificationStore } from '../stores/notificationStore'

const route = useRoute()
const router = useRouter()
const notificationStore = useNotificationStore()

const requiredPermission = computed(() => route.query.permission)
const redirectPath = computed(() => route.query.redirect)

const goBack = () => {
  if (window.history.length > 1) {
    router.go(-1)
  } else {
    router.push('/dashboard')
  }
}

const contactAdmin = () => {
  notificationStore.addNotification({
    type: 'info',
    message: 'Please contact your administrator at admin@example.com to request access.',
    duration: 5000
  })
}
</script>

<style scoped>
code {
  font-family: 'Courier New', Courier, monospace;
}
</style>
