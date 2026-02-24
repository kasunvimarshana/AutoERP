<template>
  <RouterView />
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue';
import { RouterView, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();

function handleUnauthorized() {
    auth.clearAuth();
    router.push({ name: 'login' });
}

onMounted(() => window.addEventListener('auth:unauthorized', handleUnauthorized));
onUnmounted(() => window.removeEventListener('auth:unauthorized', handleUnauthorized));
</script>
