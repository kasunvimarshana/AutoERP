<template>
  <div
    id="app"
    class="min-h-screen bg-gray-50"
  >
    <RouterView />
    <ToastNotification />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { RouterView } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import ToastNotification from '@/components/common/ToastNotification.vue';

const authStore = useAuthStore();
const tenantStore = useTenantStore();

onMounted(async () => {
  // Initialize auth state from storage
  await authStore.initialize();
  
  // Initialize tenant context if authenticated
  if (authStore.isAuthenticated) {
    await tenantStore.initialize();
  }
});
</script>

<style>
#app {
  font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>
