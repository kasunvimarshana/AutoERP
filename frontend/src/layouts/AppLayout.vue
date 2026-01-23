<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { useUiStore } from '@/stores/ui'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import NotificationContainer from '@/components/ui/NotificationContainer.vue'

const router = useRouter()
const { isAuthenticated, user } = useAuth()
const uiStore = useUiStore()

onMounted(() => {
  // Check authentication on mount
  if (!isAuthenticated.value) {
    router.push({ name: 'login' })
  }
})
</script>

<template>
  <div class="app-layout min-h-screen bg-gray-50 dark:bg-gray-900">
    <AppHeader />
    
    <div class="flex">
      <AppSidebar />
      
      <main 
        class="flex-1 p-6 transition-all duration-300"
        :class="{ 'ml-64': !uiStore.sidebarCollapsed, 'ml-16': uiStore.sidebarCollapsed }"
      >
        <slot />
      </main>
    </div>
    
    <AppFooter />
    
    <NotificationContainer />
  </div>
</template>

<style scoped>
.app-layout {
  transition: all 0.3s ease;
}
</style>
