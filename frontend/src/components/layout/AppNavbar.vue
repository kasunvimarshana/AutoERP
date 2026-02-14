<template>
  <nav class="bg-white shadow-sm fixed top-0 left-0 right-0 z-40 h-16">
    <div class="mx-auto px-4 sm:px-6 lg:px-8 h-full">
      <div class="flex justify-between items-center h-full">
        <div class="flex items-center">
          <button
            @click="uiStore.toggleSidebar"
            class="text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700"
          >
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          <h1 class="ml-4 text-xl font-semibold text-gray-800">
            {{ appName }}
          </h1>
        </div>
        
        <div class="flex items-center gap-4">
          <div v-if="tenantStore.currentTenant" class="text-sm text-gray-600">
            {{ tenantStore.tenantName }}
          </div>
          
          <div class="relative">
            <button
              @click="showUserMenu = !showUserMenu"
              class="flex items-center gap-2 text-sm text-gray-700 hover:text-gray-900 focus:outline-none"
            >
              <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                {{ userInitials }}
              </div>
              <span class="hidden sm:block">{{ authStore.user?.name || authStore.user?.email }}</span>
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            
            <div
              v-if="showUserMenu"
              class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5"
              @click.stop
            >
              <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
              <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
              <hr class="my-1">
              <button
                @click="handleLogout"
                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@stores/auth'
import { useUiStore } from '@stores/ui'
import { useTenantStore } from '@stores/tenant'

const authStore = useAuthStore()
const uiStore = useUiStore()
const tenantStore = useTenantStore()

const showUserMenu = ref(false)
const appName = import.meta.env.VITE_APP_NAME || 'AutoERP'

const userInitials = computed(() => {
  const user = authStore.user
  if (user?.name) {
    return user.name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)
  }
  if (user?.email) {
    return user.email.substring(0, 2).toUpperCase()
  }
  return 'U'
})

const handleLogout = () => {
  authStore.logout()
}

const closeMenuOnClickOutside = (event) => {
  if (!event.target.closest('.relative')) {
    showUserMenu.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', closeMenuOnClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', closeMenuOnClickOutside)
})
</script>
