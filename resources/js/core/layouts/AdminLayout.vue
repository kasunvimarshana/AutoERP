<template>
  <div class="flex h-screen bg-gray-100 overflow-hidden">
    <!-- Mobile overlay -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-20 bg-black/50 lg:hidden"
      @click="sidebarOpen = false"
    />

    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-30 w-64 bg-gray-900 text-white flex flex-col transition-transform duration-200 lg:static lg:translate-x-0"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    >
      <!-- Branding -->
      <div class="p-5 border-b border-gray-700 shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center shrink-0">
            <span class="text-sm">🏢</span>
          </div>
          <div class="min-w-0">
            <h1 class="text-sm font-bold tracking-wide text-white leading-none">
              AutoERP
            </h1>
            <p class="text-xs text-gray-400 mt-0.5 truncate">{{ tenantLabel }}</p>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto" aria-label="Main navigation">
        <template v-for="group in navGroups" :key="group.label">
          <p
            class="px-3 pt-4 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider first:pt-1"
          >
            {{ group.label }}
          </p>
          <RouterLink
            v-for="item in group.items.filter(
              (i) => !i.permission || auth.hasPermission(i.permission),
            )"
            :key="item.to"
            :to="item.to"
            class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors group"
            :class="
              isActive(item.to)
                ? 'bg-blue-600 text-white font-medium'
                : 'text-gray-300 hover:bg-gray-700/70 hover:text-white'
            "
            :aria-current="isActive(item.to) ? 'page' : undefined"
          >
            <span class="text-base leading-none w-5 text-center shrink-0" aria-hidden="true">{{
              item.icon
            }}</span>
            <span class="truncate">{{ item.label }}</span>
          </RouterLink>
        </template>
      </nav>

      <!-- User footer -->
      <div class="p-4 border-t border-gray-700 shrink-0">
        <div class="flex items-center gap-3 mb-3">
          <div
            class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-sm font-bold text-white shrink-0"
            aria-hidden="true"
          >
            {{ userInitials }}
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-white truncate">{{ displayName }}</p>
            <p class="text-xs text-gray-400 truncate">{{ auth.user?.email }}</p>
          </div>
        </div>
        <button
          :disabled="loggingOut"
          class="w-full flex items-center justify-center gap-2 text-sm text-gray-400 hover:text-white px-3 py-1.5 rounded hover:bg-gray-700 transition-colors disabled:opacity-50"
          @click="handleLogout"
        >
          <AppSpinner v-if="loggingOut" size="sm" />
          <span>{{ loggingOut ? 'Signing out…' : '⬡  Sign Out' }}</span>
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
      <!-- Top bar -->
      <header
        class="bg-white border-b border-gray-200 px-4 lg:px-6 py-3 flex items-center gap-4 shrink-0"
      >
        <!-- Hamburger (mobile) -->
        <button
          class="lg:hidden p-1 rounded text-gray-500 hover:text-gray-700"
          aria-label="Open navigation"
          @click="sidebarOpen = true"
        >
          <span class="text-xl leading-none">☰</span>
        </button>

        <h2 class="text-base font-semibold text-gray-700 truncate flex-1">{{ currentTitle }}</h2>

        <!-- Header actions slot -->
        <div class="flex items-center gap-2">
          <slot name="header-actions" />
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto p-4 lg:p-6">
        <AppErrorBoundary>
          <RouterView />
        </AppErrorBoundary>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { getEnabledModules } from '@/core/registry/moduleRegistry';
import AppSpinner from '@/components/AppSpinner.vue';
import AppErrorBoundary from '@/components/AppErrorBoundary.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const loggingOut = ref(false);
const sidebarOpen = ref(false);

interface NavGroup {
  label: string;
  items: { to: string; label: string; icon: string; permission?: string }[];
}

// ── Build nav groups from module registry ──────────────────────────────────
// Static "Overview" group always first; module groups follow in registration order.
const navGroups = computed<NavGroup[]>(() => {
  const overviewGroup: NavGroup = {
    label: 'Overview',
    items: [{ to: '/dashboard', label: 'Dashboard', icon: '🏠' }],
  };

  const groupMap = new Map<string, NavGroup>();
  for (const mod of getEnabledModules()) {
    for (const item of mod.navItems) {
      if (!groupMap.has(item.group)) {
        groupMap.set(item.group, { label: item.group, items: [] });
      }
      groupMap.get(item.group)!.items.push({
        to: item.to,
        label: item.label,
        icon: item.icon,
        permission: item.permission,
      });
    }
  }

  return [overviewGroup, ...groupMap.values()];
});

// All nav items flattened — used for title resolution
const allNavItems = computed(() => navGroups.value.flatMap((g) => g.items));

function isActive(to: string): boolean {
  if (to === '/dashboard') return route.path === '/dashboard' || route.path === '/';
  return route.path.startsWith(to);
}

const currentTitle = computed<string>(() => {
  const item = allNavItems.value.find((n) => isActive(n.to));
  return item?.label ?? 'AutoERP';
});

const displayName = computed<string>(() => auth.user?.name ?? auth.user?.email ?? 'User');

const userInitials = computed<string>(() => {
  const name = auth.user?.name ?? auth.user?.email ?? '?';
  return name.slice(0, 2).toUpperCase();
});

const tenantLabel = computed<string>(() => {
  const roles = auth.user?.roles?.map((r) => r.name).join(', ');
  return roles ? `Role: ${roles}` : 'Enterprise Suite';
});

async function handleLogout(): Promise<void> {
  loggingOut.value = true;
  try {
    await auth.logout();
    await router.push('/login');
  } finally {
    loggingOut.value = false;
  }
}
</script>
