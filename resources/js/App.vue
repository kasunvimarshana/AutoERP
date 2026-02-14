<template>
  <div id="app" class="min-h-screen bg-gray-50">
    <nav v-if="isAuthenticated" class="bg-white shadow">
      <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center">
            <h1 class="text-xl font-bold text-primary-600">AutoERP</h1>
            <div class="ml-10 flex space-x-4">
              <router-link to="/dashboard" class="nav-link">{{ $t('dashboard') }}</router-link>
              <router-link to="/products" class="nav-link">{{ $t('products') }}</router-link>
              <router-link to="/inventory" class="nav-link">{{ $t('inventory') }}</router-link>
              <router-link to="/customers" class="nav-link">{{ $t('customers') }}</router-link>
              <router-link to="/invoices" class="nav-link">{{ $t('invoices') }}</router-link>
              <router-link to="/analytics" class="nav-link">{{ $t('analytics') }}</router-link>
            </div>
          </div>
          <div>
            <button @click="handleLogout" class="btn-secondary">Logout</button>
          </div>
        </div>
      </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const isAuthenticated = computed(() => authStore.isAuthenticated)

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.nav-link {
  @apply px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-primary-600 hover:bg-gray-50;
}

.btn-secondary {
  @apply px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50;
}
</style>
