<template>
  <div class="space-y-6">
    <!-- Error State -->
    <div
      v-if="error"
      class="mb-4 rounded-md bg-red-50 p-4"
    >
      <div class="flex">
        <div class="flex-shrink-0">
          <svg
            class="h-5 w-5 text-red-400"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path
              fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
              clip-rule="evenodd"
            />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">
            {{ error }}
          </h3>
        </div>
        <div class="ml-auto pl-3">
          <button
            class="inline-flex text-red-400 hover:text-red-600"
            @click="error = null; loadDashboardData()"
          >
            <span class="sr-only">Retry</span>
            Retry
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div
      v-if="loading"
      class="text-center py-8"
    >
      <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent" />
      <p class="mt-2 text-gray-600">
        Loading dashboard data...
      </p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
      <div
        v-for="stat in stats"
        :key="stat.name"
        class="card p-6"
      >
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <component
              :is="stat.icon"
              class="h-8 w-8 text-blue-600"
            />
          </div>
          <div class="ml-5 w-0 flex-1">
            <dl>
              <dt class="text-sm font-medium text-gray-500 truncate">
                {{ stat.name }}
              </dt>
              <dd class="flex items-baseline">
                <div class="text-2xl font-semibold text-gray-900">
                  {{ stat.value }}
                </div>
                <div
                  v-if="stat.change"
                  :class="[
                    'ml-2 flex items-baseline text-sm font-semibold',
                    stat.changeType === 'increase' ? 'text-green-600' : 'text-red-600',
                  ]"
                >
                  {{ stat.change }}
                </div>
              </dd>
            </dl>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <div class="card p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          Revenue Overview
        </h3>
        <div class="h-64">
          <p class="text-gray-500 text-center py-8">
            Chart will be rendered here
          </p>
        </div>
      </div>

      <div class="card p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          Sales by Category
        </h3>
        <div class="h-64">
          <p class="text-gray-500 text-center py-8">
            Chart will be rendered here
          </p>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="card">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">
          Recent Activity
        </h3>
      </div>
      <div class="divide-y divide-gray-200">
        <div
          v-for="activity in recentActivity"
          :key="activity.id"
          class="px-6 py-4 hover:bg-gray-50"
        >
          <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
              <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                <component
                  :is="activity.icon"
                  class="h-4 w-4 text-blue-600"
                />
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm text-gray-900">
                {{ activity.description }}
              </p>
              <p class="text-xs text-gray-500">
                {{ activity.time }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import {
  UsersIcon,
  ShoppingBagIcon,
  CurrencyDollarIcon,
  ChartBarIcon,
  DocumentTextIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline';
import { useAuthStore } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import { dashboardApi } from '@/api/dashboard';
import type { DashboardStats, Activity } from '@/api/dashboard';

const authStore = useAuthStore();
const tenantStore = useTenantStore();

// Default icons for stats - maintain stable icon assignment
const defaultStatIcons = [UsersIcon, ShoppingBagIcon, CurrencyDollarIcon, ChartBarIcon];

const loading = ref(false);
const error = ref<string | null>(null);
const stats = ref([
  {
    name: 'Total Customers',
    value: '...',
    icon: defaultStatIcons[0],
    change: '...',
    changeType: 'increase' as const,
  },
  {
    name: 'Total Orders',
    value: '...',
    icon: defaultStatIcons[1],
    change: '...',
    changeType: 'increase' as const,
  },
  {
    name: 'Revenue',
    value: '...',
    icon: defaultStatIcons[2],
    change: '...',
    changeType: 'increase' as const,
  },
  {
    name: 'Active Products',
    value: '...',
    icon: defaultStatIcons[3],
    change: '...',
    changeType: 'increase' as const,
  },
]);

const recentActivity = ref<Activity[]>([
  {
    id: 0,
    description: 'Loading...',
    time: '',
    created_at: '',
  },
]);

const loadDashboardData = async () => {
  loading.value = true;
  error.value = null;
  
  try {
    // Load stats
    const statsData = await dashboardApi.getStats();
    if (statsData.stats && statsData.stats.length > 0) {
      // Update stats while preserving icon assignments
      stats.value = statsData.stats.map((stat, index) => ({
        name: stat.name,
        value: stat.value,
        change: stat.change,
        changeType: stat.changeType || ('increase' as const),
        icon: defaultStatIcons[index] || ChartBarIcon,
      }));
    }
    
    // Load activity
    const activityData = await dashboardApi.getActivity(5);
    if (activityData && activityData.length > 0) {
      recentActivity.value = activityData.map(activity => ({
        ...activity,
        icon: DocumentTextIcon, // Default icon for activity items
      }));
    }
  } catch (err: any) {
    console.error('Failed to load dashboard data:', err);
    error.value = err.message || 'Failed to load dashboard data. Please try again.';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadDashboardData();
});
</script>
