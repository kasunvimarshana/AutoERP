<template>
  <div id="app" class="min-h-screen bg-gray-50">
    <RouterView />
  </div>
</template>

<script setup>
import { RouterView, useRouter } from 'vue-router';
import { onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { AUTH_EVENTS } from '@/config/constants';

const router = useRouter();
const authStore = useAuthStore();

// Handle 401 logout event from API interceptor
const handleLogout = () => {
  authStore.clearAuth();
  router.push('/login');
};

onMounted(() => {
  // Check if user is authenticated on app mount
  authStore.checkAuth();
  
  // Listen for logout events from API interceptor
  window.addEventListener(AUTH_EVENTS.LOGOUT, handleLogout);
});

onUnmounted(() => {
  window.removeEventListener(AUTH_EVENTS.LOGOUT, handleLogout);
});
</script>

<style>
/* Global styles */
</style>
