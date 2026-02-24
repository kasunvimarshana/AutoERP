<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tenant Management</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage platform tenants, their configuration, and lifecycle status.</p>
        </div>
      </div>

      <div
        v-if="tenantStore.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ tenantStore.error }}
      </div>

      <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Tenants table">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
              <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Slug</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Timezone</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Currency</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Locale</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Created</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              <tr v-if="tenantStore.loading">
                <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
              </tr>
              <tr v-else-if="tenantStore.tenants.length === 0">
                <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No tenants found.</td>
              </tr>
              <tr
                v-for="tenant in tenantStore.tenants"
                v-else
                :key="tenant.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
              >
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 h-8 w-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-xs font-bold text-indigo-600 dark:text-indigo-400">
                      {{ initials(tenant.name) }}
                    </div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ tenant.name }}</p>
                  </div>
                </td>
                <td class="px-4 py-3 text-xs font-mono text-indigo-600 dark:text-indigo-400">{{ tenant.slug }}</td>
                <td class="px-4 py-3"><StatusBadge :status="tenant.status" /></td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ tenant.timezone }}</td>
                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ tenant.default_currency }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ tenant.locale }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(tenant.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="tenantStore.meta" @change="tenantStore.fetchTenants" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { useTenantStore } from '@/stores/tenant';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate } = useFormatters();

const tenantStore = useTenantStore();

function initials(name) {
    if (!name) return '?';
    return name.split(' ').filter(n => n.length > 0).map(n => n[0]).slice(0, 2).join('').toUpperCase();
}

onMounted(() => tenantStore.fetchTenants());
</script>
