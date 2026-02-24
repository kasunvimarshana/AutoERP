<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">User Management</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage platform users, roles, and access control.</p>
        </div>
      </div>

      <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex gap-6" aria-label="User management tabs">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            :class="[
              activeTab === tab.key
                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600',
              'whitespace-nowrap border-b-2 pb-3 text-sm font-medium transition-colors',
            ]"
            @click="switchTab(tab.key)"
          >
            {{ tab.label }}
            <span
              v-if="tab.count !== null"
              :class="activeTab === tab.key ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
              class="ml-2 rounded-full px-2 py-0.5 text-xs font-semibold"
            >{{ tab.count }}</span>
          </button>
        </nav>
      </div>

      <div
        v-if="userStore.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ userStore.error }}
      </div>

      <!-- Users tab -->
      <div v-if="activeTab === 'users'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Users table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">User</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Email</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Joined</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="userStore.loading">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="userStore.users.length === 0">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No users found.</td>
                </tr>
                <tr
                  v-for="user in userStore.users"
                  v-else
                  :key="user.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                      <div class="flex-shrink-0 h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-xs font-bold text-indigo-600 dark:text-indigo-400">
                        {{ initials(user.name) }}
                      </div>
                      <p class="text-sm font-medium text-gray-900 dark:text-white">{{ user.name }}</p>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ user.email }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="user.status" /></td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(user.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="userStore.meta" @change="userStore.fetchUsers" />
        </div>
      </div>

      <!-- Roles tab -->
      <div v-if="activeTab === 'roles'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Roles table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Role Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Guard</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Created</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="userStore.rolesLoading">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="userStore.roles.length === 0">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No roles found.</td>
                </tr>
                <tr
                  v-for="role in userStore.roles"
                  v-else
                  :key="role.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ role.name }}</td>
                  <td class="px-4 py-3 text-xs font-mono text-indigo-600 dark:text-indigo-400">{{ role.guard_name ?? 'api' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ role.description ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(role.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="userStore.rolesMeta" @change="userStore.fetchRoles" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { useUserStore } from '@/stores/user';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate } = useFormatters();

const userStore = useUserStore();
const activeTab = ref('users');

const tabs = computed(() => [
    { key: 'users', label: 'Users', count: userStore.meta.total || null },
    { key: 'roles', label: 'Roles', count: userStore.rolesMeta.total || null },
]);

function switchTab(key) {
    activeTab.value = key;
    if (key === 'users') userStore.fetchUsers();
    else if (key === 'roles') userStore.fetchRoles();
}

function initials(name) {
    if (!name) return '?';
    return name.split(' ').filter(n => n.length > 0).map(n => n[0]).slice(0, 2).join('').toUpperCase();
}

onMounted(() => userStore.fetchUsers());
</script>
