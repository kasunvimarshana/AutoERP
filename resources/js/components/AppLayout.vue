<template>
  <div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white flex flex-col shrink-0">
      <div class="p-5 border-b border-gray-700">
        <h1 class="text-base font-bold tracking-wide text-white">AutoERP</h1>
        <p class="text-xs text-gray-400 mt-0.5 truncate">{{ tenantLabel }}</p>
      </div>

      <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto">
        <RouterLink
          v-for="item in visibleNavItems"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors"
          :class="isActive(item.to)
            ? 'bg-blue-600 text-white font-medium'
            : 'text-gray-300 hover:bg-gray-700 hover:text-white'"
        >
          <span class="text-base leading-none">{{ item.icon }}</span>
          <span>{{ item.label }}</span>
        </RouterLink>
      </nav>

      <!-- User footer -->
      <div class="p-4 border-t border-gray-700">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-sm font-bold text-white shrink-0">
            {{ userInitials }}
          </div>
          <div class="min-w-0">
            <p class="text-sm font-medium text-white truncate">{{ displayName }}</p>
            <p class="text-xs text-gray-400 truncate">{{ auth.user?.email }}</p>
          </div>
        </div>
        <button
          @click="handleLogout"
          :disabled="loggingOut"
          class="w-full flex items-center justify-center gap-2 text-sm text-gray-400 hover:text-white px-3 py-1.5 rounded hover:bg-gray-700 transition-colors disabled:opacity-50"
        >
          <AppSpinner v-if="loggingOut" size="sm" />
          <span>{{ loggingOut ? 'Signing out…' : 'Sign Out' }}</span>
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between shrink-0">
        <h2 class="text-base font-semibold text-gray-700">{{ currentTitle }}</h2>
      </header>

      <main class="flex-1 overflow-y-auto p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppSpinner from '@/components/AppSpinner.vue';
import type { NavItem } from '@/types/index';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const loggingOut = ref(false);

const DASHBOARD_ROUTE = '/dashboard';

const allNavItems: NavItem[] = [
  { to: DASHBOARD_ROUTE, label: 'Dashboard', icon: '🏠' },
  { to: '/products', label: 'Products', icon: '📦', permission: 'product.view' },
  { to: '/inventory', label: 'Inventory', icon: '🏭', permission: 'inventory.view' },
  { to: '/orders', label: 'Orders', icon: '🛒', permission: 'order.view' },
  { to: '/pos', label: 'Point of Sale', icon: '🖥️', permission: 'pos.view' },
  { to: '/invoices', label: 'Invoices', icon: '🧾', permission: 'invoice.view' },
  { to: '/purchases', label: 'Purchases', icon: '🚚', permission: 'purchase.view' },
  { to: '/crm', label: 'CRM', icon: '🤝', permission: 'crm.view' },
  { to: '/accounting', label: 'Accounting', icon: '📊', permission: 'accounting.view' },
  { to: '/reports', label: 'Reports', icon: '📈', permission: 'report.view' },
  { to: '/users', label: 'Users', icon: '👥', permission: 'user.view' },
];

const visibleNavItems = computed<NavItem[]>(() =>
  allNavItems.filter(
    (item) => !item.permission || auth.hasPermission(item.permission),
  ),
);

function isActive(to: string): boolean {
  if (to === DASHBOARD_ROUTE) return route.path === DASHBOARD_ROUTE || route.path === '/';
  return route.path.startsWith(to);
}

const currentTitle = computed<string>(() => {
  const item = allNavItems.find((n) => isActive(n.to));
  return item?.label ?? 'AutoERP';
});

const displayName = computed<string>(
  () => auth.user?.name ?? auth.user?.email ?? 'User',
);

const userInitials = computed<string>(() => {
  const name = auth.user?.name ?? auth.user?.email ?? '?';
  const initials = name.slice(0, 2).toUpperCase();
  return initials.length < 2 ? initials.padEnd(2, initials[0] ?? '?') : initials;
});

const tenantLabel = computed<string>(() => {
  const roles = auth.user?.roles?.map((r) => r.name).join(', ');
  return roles ? `Role: ${roles}` : 'Enterprise Suite';
});

async function handleLogout(): Promise<void> {
  loggingOut.value = true;
  try {
    await auth.logout();
    router.push('/login');
  } finally {
    loggingOut.value = false;
  }
}
</script>
