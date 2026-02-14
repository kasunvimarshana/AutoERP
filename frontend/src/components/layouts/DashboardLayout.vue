<template>
  <div class="min-h-screen bg-secondary-50">
    <!-- Top Navigation -->
    <nav class="bg-white shadow-sm border-b border-secondary-200 fixed w-full top-0 z-50">
      <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <button @click="toggleSidebar" class="p-2 rounded-md text-secondary-600 hover:bg-secondary-100">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
            <h1 class="ml-4 text-xl font-bold text-primary-600">AutoERP</h1>
          </div>
          
          <div class="flex items-center space-x-4">
            <!-- Language Selector -->
            <select 
              v-model="currentLocale" 
              @change="changeLocale"
              class="border border-secondary-300 rounded-md px-3 py-1 text-sm"
            >
              <option value="en">English</option>
              <option value="es">Español</option>
              <option value="fr">Français</option>
            </select>

            <!-- User Menu -->
            <div class="flex items-center space-x-2">
              <span class="text-sm text-secondary-700">{{ authStore.user?.name }}</span>
              <button @click="logout" class="btn btn-secondary text-sm">
                {{ $t('auth.logout') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <div class="flex pt-16">
      <!-- Sidebar -->
      <aside 
        :class="[
          'fixed left-0 top-16 h-[calc(100vh-4rem)] bg-white border-r border-secondary-200 transition-all duration-300 z-40',
          sidebarOpen ? 'w-64' : 'w-0 -translate-x-full'
        ]"
      >
        <nav class="p-4 space-y-2">
          <router-link
            v-for="item in menuItems"
            :key="item.name"
            :to="item.path"
            class="flex items-center px-4 py-3 text-secondary-700 rounded-lg hover:bg-primary-50 hover:text-primary-600 transition-colors"
            active-class="bg-primary-100 text-primary-700 font-semibold"
          >
            <span class="text-xl mr-3">{{ item.icon }}</span>
            <span>{{ $t(item.label) }}</span>
          </router-link>
        </nav>
      </aside>

      <!-- Main Content -->
      <main 
        :class="[
          'flex-1 p-6 transition-all duration-300',
          sidebarOpen ? 'ml-64' : 'ml-0'
        ]"
      >
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/modules/auth/store/authStore'
import { useAppStore } from '@/stores/app'

const router = useRouter()
const { locale, t } = useI18n()
const authStore = useAuthStore()
const appStore = useAppStore()

const sidebarOpen = computed(() => appStore.sidebarOpen)
const currentLocale = ref(locale.value)

const menuItems = [
  { name: 'dashboard', path: '/', icon: '📊', label: 'dashboard.title' },
  { name: 'tenants', path: '/tenants', icon: '🏢', label: 'tenant.title' },
  { name: 'users', path: '/users', icon: '👥', label: 'user.title' },
  { name: 'customers', path: '/customers', icon: '👤', label: 'customer.title' },
  { name: 'vehicles', path: '/vehicles', icon: '🚗', label: 'vehicle.title' },
  { name: 'inventory', path: '/inventory', icon: '📦', label: 'inventory.title' },
  { name: 'products', path: '/products', icon: '🛍️', label: 'product.title' },
  { name: 'orders', path: '/orders', icon: '🛒', label: 'order.title' },
  { name: 'invoices', path: '/invoices', icon: '🧾', label: 'invoice.title' },
  { name: 'payments', path: '/payments', icon: '💳', label: 'payment.title' },
  { name: 'reports', path: '/reports', icon: '📈', label: 'reports.title' }
]

const toggleSidebar = () => {
  appStore.toggleSidebar()
}

const changeLocale = () => {
  locale.value = currentLocale.value
  localStorage.setItem('locale', currentLocale.value)
}

const logout = () => {
  authStore.logout()
  router.push({ name: 'Login' })
}
</script>
