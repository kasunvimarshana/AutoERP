<template>
  <router-view />
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '@/modules/auth/store/authStore'

const authStore = useAuthStore()

onMounted(() => {
  authStore.checkAuth()
})
</script>

<style>
#app {
  font-family: Inter, system-ui, Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>
