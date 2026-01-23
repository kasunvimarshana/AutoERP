<template>
  <div id="app">
    <RouterView />
  </div>
</template>

<script setup>
import { RouterView, useRouter, useRoute } from 'vue-router';
import { onMounted, onUnmounted, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { AUTH_EVENTS } from '@/config/constants';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

// Handle 401 logout event from API interceptor
const handleLogout = () => {
  authStore.clearAuth();
  router.push('/login');
};

// Add AdminLTE body class for authenticated routes
const updateBodyClass = () => {
  const authRoutes = ['/dashboard', '/profile'];
  if (authRoutes.includes(route.path)) {
    document.body.classList.add('hold-transition', 'sidebar-mini', 'layout-fixed');
  } else {
    document.body.classList.remove('hold-transition', 'sidebar-mini', 'layout-fixed');
  }
};

onMounted(() => {
  // Check if user is authenticated on app mount
  authStore.checkAuth();
  
  // Listen for logout events from API interceptor
  window.addEventListener(AUTH_EVENTS.LOGOUT, handleLogout);
  
  // Update body class based on route
  updateBodyClass();
});

onUnmounted(() => {
  window.removeEventListener(AUTH_EVENTS.LOGOUT, handleLogout);
});

// Watch route changes to update body class
watch(() => route.path, () => {
  updateBodyClass();
});
</script>

<style>
/* Global styles */
#app {
  min-height: 100vh;
}

/* Ensure Tailwind plays nice with AdminLTE */
.wrapper {
  min-height: 100vh;
}
</style>
