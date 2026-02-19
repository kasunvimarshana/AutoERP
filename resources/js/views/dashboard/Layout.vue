<template>
  <div class="min-h-screen flex bg-gray-50">
    <!-- Sidebar -->
    <Sidebar :menu-items="menuItems" title="ERP/CRM">
      <template #footer>
        <div class="flex w-full items-center">
          <div class="flex-shrink-0">
            <div class="h-10 w-10 rounded-full bg-gray-600 flex items-center justify-center">
              <span class="text-sm font-medium text-white">{{ userInitials }}</span>
            </div>
          </div>
          <div class="ml-3 flex-1">
            <p class="text-sm font-medium text-white">{{ user?.name || 'User' }}</p>
            <p class="text-xs text-gray-400">{{ user?.email }}</p>
          </div>
        </div>
      </template>
    </Sidebar>

    <!-- Main content -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top navbar -->
      <Navbar :app-name="appName" :notification-count="0" />

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto p-6">
        <router-view />
      </main>
    </div>

    <!-- Toast Notifications -->
    <ToastNotifications />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { 
  HomeIcon,
  ShoppingBagIcon,
  UsersIcon,
  ShoppingCartIcon,
  DocumentTextIcon,
  ChartBarIcon,
  CogIcon,
  CubeIcon,
  BuildingOfficeIcon,
  BanknotesIcon,
  ClipboardDocumentListIcon,
  BellIcon,
  DocumentDuplicateIcon,
  AdjustmentsHorizontalIcon
} from '@heroicons/vue/24/outline';
import Sidebar from '@/components/layout/Sidebar.vue';
import Navbar from '@/components/layout/Navbar.vue';
import ToastNotifications from '@/components/layout/ToastNotifications.vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const appName = 'Enterprise ERP/CRM';

const user = computed(() => authStore.currentUser);

const userInitials = computed(() => {
  if (!user.value || !user.value.name) return '??';
  
  const names = user.value.name.split(' ');
  if (names.length >= 2) {
    return `${names[0][0]}${names[1][0]}`.toUpperCase();
  }
  return user.value.name.substring(0, 2).toUpperCase();
});

const menuItems = computed(() => [
  { name: 'Dashboard', to: '/dashboard', icon: HomeIcon },
  { name: 'Products', to: '/products', icon: CubeIcon },
  { name: 'Customers', to: '/customers', icon: UsersIcon },
  { name: 'Leads', to: '/leads', icon: UsersIcon },
  { name: 'Quotations', to: '/quotations', icon: DocumentTextIcon },
  { name: 'Orders', to: '/orders', icon: ShoppingCartIcon },
  { name: 'Invoices', to: '/invoices', icon: DocumentTextIcon },
  { name: 'Vendors', to: '/vendors', icon: BuildingOfficeIcon },
  { name: 'Purchase Orders', to: '/purchase-orders', icon: ShoppingBagIcon },
  { name: 'Inventory', to: '/stock', icon: CubeIcon },
  { name: 'Accounting', to: '/accounts', icon: BanknotesIcon },
  { name: 'Reports', to: '/reports', icon: ChartBarIcon },
  { name: 'Documents', to: '/documents', icon: DocumentDuplicateIcon },
  { name: 'Workflows', to: '/workflows', icon: AdjustmentsHorizontalIcon },
  { name: 'Notifications', to: '/notifications', icon: BellIcon },
  { name: 'Settings', to: '/settings', icon: CogIcon },
]);
</script>
