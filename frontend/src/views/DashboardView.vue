<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth'

const { user } = useAuth()

// Mock data for dashboard stats
const stats = ref([
  {
    title: 'Total Customers',
    value: '1,234',
    icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    color: 'blue',
    change: '+12%',
  },
  {
    title: 'Active Vehicles',
    value: '2,456',
    icon: 'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2',
    color: 'green',
    change: '+8%',
  },
  {
    title: 'Appointments Today',
    value: '45',
    icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    color: 'yellow',
    change: '+3',
  },
  {
    title: 'Revenue (This Month)',
    value: '$45,231',
    icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    color: 'purple',
    change: '+18%',
  },
])

const recentActivities = ref([
  { type: 'customer', message: 'New customer John Doe registered', time: '5 min ago' },
  { type: 'appointment', message: 'Appointment scheduled for Vehicle ABC-123', time: '15 min ago' },
  { type: 'job', message: 'Job card #1234 completed', time: '1 hour ago' },
  { type: 'invoice', message: 'Invoice #5678 paid', time: '2 hours ago' },
])
</script>

<template>
  <div class="dashboard-view">
    <!-- Welcome Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
        Welcome back, {{ user?.name || 'User' }}! 👋
      </h1>
      <p class="text-gray-600 dark:text-gray-400 mt-2">
        Here's what's happening with your business today.
      </p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div
        v-for="stat in stats"
        :key="stat.title"
        class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border border-gray-200 dark:border-gray-700"
      >
        <div class="flex items-center justify-between mb-4">
          <div
            :class="[
              'p-3 rounded-lg',
              stat.color === 'blue' && 'bg-blue-100 dark:bg-blue-900',
              stat.color === 'green' && 'bg-green-100 dark:bg-green-900',
              stat.color === 'yellow' && 'bg-yellow-100 dark:bg-yellow-900',
              stat.color === 'purple' && 'bg-purple-100 dark:bg-purple-900',
            ]"
          >
            <svg
              :class="[
                'w-6 h-6',
                stat.color === 'blue' && 'text-blue-600 dark:text-blue-400',
                stat.color === 'green' && 'text-green-600 dark:text-green-400',
                stat.color === 'yellow' && 'text-yellow-600 dark:text-yellow-400',
                stat.color === 'purple' && 'text-purple-600 dark:text-purple-400',
              ]"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon" />
            </svg>
          </div>
          <span class="text-sm font-medium text-green-600 dark:text-green-400">
            {{ stat.change }}
          </span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
          {{ stat.value }}
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ stat.title }}</p>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border border-gray-200 dark:border-gray-700">
      <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Recent Activity</h2>
      <div class="space-y-4">
        <div
          v-for="(activity, index) in recentActivities"
          :key="index"
          class="flex items-start space-x-3 pb-4 border-b border-gray-200 dark:border-gray-700 last:border-0 last:pb-0"
        >
          <div class="flex-shrink-0 w-2 h-2 mt-2 bg-blue-600 rounded-full"></div>
          <div class="flex-1">
            <p class="text-sm text-gray-900 dark:text-white">{{ activity.message }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ activity.time }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
      <router-link
        to="/customers/create"
        class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border border-gray-200 dark:border-gray-700 hover:border-blue-500 transition-colors"
      >
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Add Customer</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">Register a new customer</p>
      </router-link>

      <router-link
        to="/appointments"
        class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border border-gray-200 dark:border-gray-700 hover:border-blue-500 transition-colors"
      >
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Schedule Appointment</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">Book a service appointment</p>
      </router-link>

      <router-link
        to="/job-cards"
        class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border border-gray-200 dark:border-gray-700 hover:border-blue-500 transition-colors"
      >
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Create Job Card</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">Start a new service job</p>
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.dashboard-view {
  animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
