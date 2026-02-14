<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
      <p class="mt-1 text-sm text-gray-600">
        Welcome back, {{ authStore.currentUser?.name }}!
      </p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="stat in stats" :key="stat.name" class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-12 w-12 rounded-md flex items-center justify-center text-2xl" :class="stat.bgColor">
                {{ stat.icon }}
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">
                  {{ stat.name }}
                </dt>
                <dd class="text-2xl font-semibold text-gray-900">
                  {{ stat.value }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modules Grid -->
    <div class="mt-8">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Access</h2>
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <router-link
          v-for="module in modules"
          :key="module.name"
          :to="module.to"
          class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow cursor-pointer"
        >
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="h-12 w-12 rounded-md flex items-center justify-center text-2xl" :class="module.bgColor">
                  {{ module.icon }}
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <h3 class="text-lg font-medium text-gray-900">
                  {{ module.name }}
                </h3>
                <p class="text-sm text-gray-500">
                  {{ module.description }}
                </p>
              </div>
            </div>
          </div>
        </router-link>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="mt-8">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h2>
      <div class="bg-white shadow rounded-lg">
        <div class="p-6">
          <p class="text-gray-500 text-center py-4">No recent activity</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../../../stores/auth';

const authStore = useAuthStore();

const stats = ref([
  { name: 'Total Customers', value: '0', icon: '👥', bgColor: 'bg-blue-100 text-blue-600' },
  { name: 'Total Products', value: '0', icon: '📦', bgColor: 'bg-green-100 text-green-600' },
  { name: 'Total Sales', value: '$0', icon: '💰', bgColor: 'bg-yellow-100 text-yellow-600' },
  { name: 'Active Orders', value: '0', icon: '🛒', bgColor: 'bg-purple-100 text-purple-600' },
]);

const modules = ref([
  { name: 'Customers', icon: '👥', description: 'Manage customers', to: '/customers', bgColor: 'bg-blue-100 text-blue-600' },
  { name: 'Products', icon: '📦', description: 'Manage products', to: '/products', bgColor: 'bg-green-100 text-green-600' },
  { name: 'Inventory', icon: '🏭', description: 'Stock management', to: '/inventory', bgColor: 'bg-indigo-100 text-indigo-600' },
  { name: 'POS', icon: '🛒', description: 'Point of sale', to: '/pos', bgColor: 'bg-purple-100 text-purple-600' },
  { name: 'Billing', icon: '💰', description: 'Invoicing & payments', to: '/billing', bgColor: 'bg-yellow-100 text-yellow-600' },
  { name: 'Branches', icon: '🏢', description: 'Branch management', to: '/branches', bgColor: 'bg-pink-100 text-pink-600' },
  { name: 'Fleet', icon: '🚗', description: 'Fleet management', to: '/fleet', bgColor: 'bg-red-100 text-red-600' },
  { name: 'CRM', icon: '📞', description: 'Customer relations', to: '/crm', bgColor: 'bg-teal-100 text-teal-600' },
  { name: 'Analytics', icon: '📊', description: 'Reports & insights', to: '/analytics', bgColor: 'bg-gray-100 text-gray-600' },
]);

onMounted(() => {
  // Load dashboard stats
});
</script>
