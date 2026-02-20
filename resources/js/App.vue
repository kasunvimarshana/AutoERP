<template>
  <RouterView />
  <AppToast />
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { RouterView } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppToast from '@/components/AppToast.vue';

const auth = useAuthStore();

onMounted(async () => {
  if (auth.isAuthenticated) {
    // Silently restore user profile; http interceptor handles token expiry
    await auth.fetchMe().catch(() => undefined);
  }
});
</script>
