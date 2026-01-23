<script setup lang="ts">
import { useAuth } from '@/composables/useAuth'
import { useUiStore } from '@/stores/ui'
import { useRouter } from 'vue-router'

const { user, logout } = useAuth()
const uiStore = useUiStore()
const router = useRouter()

const handleLogout = async () => {
  try {
    await logout()
    router.push({ name: 'login' })
  } catch (error) {
    console.error('Logout error:', error)
  }
}
</script>

<template>
  <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-40 shadow-sm">
    <div class="flex items-center justify-between px-6 py-4">
      <!-- Left: Menu toggle and brand -->
      <div class="flex items-center space-x-4">
        <button
          @click="uiStore.toggleSidebar"
          class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          aria-label="Toggle sidebar"
        >
          <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white hidden md:block">
          AutoERP
        </h2>
      </div>

      <!-- Right: User menu and theme toggle -->
      <div class="flex items-center space-x-4">
        <!-- Theme toggle -->
        <button
          @click="uiStore.toggleTheme"
          class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          aria-label="Toggle theme"
        >
          <svg v-if="uiStore.theme === 'light'" class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
          </svg>
          <svg v-else class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </button>

        <!-- User dropdown -->
        <div class="relative group">
          <button class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold text-sm">
              {{ user?.name?.charAt(0).toUpperCase() || 'U' }}
            </div>
            <div class="hidden md:block text-left">
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ user?.name || 'User' }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ user?.email || '' }}</p>
            </div>
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <!-- Dropdown menu -->
          <div class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-all duration-200">
            <router-link 
              to="/settings" 
              class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-t-lg"
            >
              Settings
            </router-link>
            <button
              @click="handleLogout"
              class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-b-lg"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>
