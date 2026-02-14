<template>
  <div class="min-h-screen bg-gray-100">
    <AppNavbar />
    <div class="flex">
      <AppSidebar />
      <main class="flex-1 transition-all duration-300" :class="{ 'ml-64': !sidebarCollapsed, 'ml-16': sidebarCollapsed }">
        <div class="py-6 px-4 sm:px-6 lg:px-8">
          <router-view />
        </div>
      </main>
    </div>
    <NotificationContainer />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useUiStore } from '@stores/ui'
import AppNavbar from '@components/layout/AppNavbar.vue'
import AppSidebar from '@components/layout/AppSidebar.vue'
import NotificationContainer from '@components/layout/NotificationContainer.vue'

const uiStore = useUiStore()
const sidebarCollapsed = computed(() => uiStore.sidebarCollapsed)
</script>
