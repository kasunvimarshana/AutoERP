<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
      <p class="mt-1 text-sm text-gray-500">Welcome back, {{ userName }}!</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
      <BaseCard v-for="stat in stats" :key="stat.name" hover>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div :class="['rounded-md p-3', stat.iconBackground]">
              <component :is="stat.icon" :class="['h-6 w-6', stat.iconColor]" />
            </div>
          </div>
          <div class="ml-5 w-0 flex-1">
            <dl>
              <dt class="text-sm font-medium text-gray-500 truncate">{{ stat.name }}</dt>
              <dd class="flex items-baseline">
                <div class="text-2xl font-semibold text-gray-900">{{ stat.value }}</div>
                <div v-if="stat.change" :class="['ml-2 flex items-baseline text-sm font-semibold', stat.changeType === 'increase' ? 'text-green-600' : 'text-red-600']">
                  <svg v-if="stat.changeType === 'increase'" class="self-center flex-shrink-0 h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                  </svg>
                  <svg v-else class="self-center flex-shrink-0 h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                  <span class="ml-1">{{ stat.change }}%</span>
                </div>
              </dd>
            </dl>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 mb-6">
      <!-- Revenue Chart -->
      <BaseCard title="Revenue Overview">
        <div class="h-64 flex items-center justify-center text-gray-400">
          <div class="text-center">
            <ChartBarIcon class="h-12 w-12 mx-auto mb-2" />
            <p>Chart integration coming soon</p>
          </div>
        </div>
      </BaseCard>

      <!-- Recent Activity -->
      <BaseCard title="Recent Activity">
        <div class="flow-root">
          <ul role="list" class="-mb-8">
            <li v-for="(activity, index) in recentActivities" :key="activity.id">
              <div class="relative pb-8">
                <span v-if="index !== recentActivities.length - 1" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                <div class="relative flex space-x-3">
                  <div>
                    <span :class="['h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white', activity.iconBackground]">
                      <component :is="activity.icon" class="h-5 w-5 text-white" />
                    </span>
                  </div>
                  <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                    <div>
                      <p class="text-sm text-gray-500">{{ activity.content }}</p>
                    </div>
                    <div class="whitespace-nowrap text-right text-sm text-gray-500">
                      <time>{{ activity.time }}</time>
                    </div>
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </BaseCard>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3 mb-6">
      <BaseCard title="Quick Actions">
        <div class="space-y-3">
          <BaseButton variant="primary" full-width @click="navigateTo('/products')">
            Add Product
          </BaseButton>
          <BaseButton variant="secondary" full-width @click="navigateTo('/customers')">
            New Customer
          </BaseButton>
          <BaseButton variant="success" full-width @click="navigateTo('/orders')">
            Create Order
          </BaseButton>
        </div>
      </BaseCard>

      <BaseCard title="Recent Orders">
        <div class="space-y-3">
          <div v-for="order in recentOrders" :key="order.id" class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-900">{{ order.number }}</p>
              <p class="text-xs text-gray-500">{{ order.customer }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-semibold text-gray-900">{{ order.total }}</p>
              <BaseBadge :variant="getStatusVariant(order.status)" size="sm">{{ order.status }}</BaseBadge>
            </div>
          </div>
        </div>
      </BaseCard>

      <BaseCard title="Top Products">
        <div class="space-y-3">
          <div v-for="product in topProducts" :key="product.id" class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-900">{{ product.name }}</p>
              <p class="text-xs text-gray-500">{{ product.category }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-semibold text-gray-900">{{ product.sold }} sold</p>
            </div>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Alerts -->
    <div v-if="alerts.length > 0" class="space-y-4">
      <BaseAlert
        v-for="alert in alerts"
        :key="alert.id"
        :variant="alert.variant"
        :title="alert.title"
        :message="alert.message"
        dismissible
        @dismiss="dismissAlert(alert.id)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import {
  CubeIcon,
  UsersIcon,
  ShoppingCartIcon,
  BanknotesIcon,
  ChartBarIcon,
  CheckCircleIcon,
  PlusCircleIcon,
  ShoppingBagIcon
} from '@heroicons/vue/24/outline';
import BaseCard from '@/components/layout/BaseCard.vue';
import BaseButton from '@/components/common/BaseButton.vue';
import BaseBadge from '@/components/common/BaseBadge.vue';
import BaseAlert from '@/components/common/BaseAlert.vue';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const userName = computed(() => authStore.currentUser?.name || 'User');

const stats = ref([
  {
    name: 'Total Revenue',
    value: '$45,231',
    change: '+12.5',
    changeType: 'increase',
    icon: BanknotesIcon,
    iconBackground: 'bg-indigo-500',
    iconColor: 'text-white',
  },
  {
    name: 'Total Orders',
    value: '142',
    change: '+8.3',
    changeType: 'increase',
    icon: ShoppingCartIcon,
    iconBackground: 'bg-green-500',
    iconColor: 'text-white',
  },
  {
    name: 'Total Customers',
    value: '89',
    change: '+4.2',
    changeType: 'increase',
    icon: UsersIcon,
    iconBackground: 'bg-yellow-500',
    iconColor: 'text-white',
  },
  {
    name: 'Products',
    value: '256',
    change: null,
    changeType: null,
    icon: CubeIcon,
    iconBackground: 'bg-purple-500',
    iconColor: 'text-white',
  },
]);

const recentActivities = ref([
  {
    id: 1,
    content: 'New order #ORD-2024-001 created',
    time: '2 minutes ago',
    icon: ShoppingCartIcon,
    iconBackground: 'bg-green-500',
  },
  {
    id: 2,
    content: 'Product "Laptop Pro 15" updated',
    time: '15 minutes ago',
    icon: CubeIcon,
    iconBackground: 'bg-blue-500',
  },
  {
    id: 3,
    content: 'New customer "John Doe" registered',
    time: '1 hour ago',
    icon: PlusCircleIcon,
    iconBackground: 'bg-purple-500',
  },
  {
    id: 4,
    content: 'Invoice #INV-2024-045 paid',
    time: '2 hours ago',
    icon: CheckCircleIcon,
    iconBackground: 'bg-green-500',
  },
  {
    id: 5,
    content: 'Purchase order #PO-2024-023 received',
    time: '5 hours ago',
    icon: ShoppingBagIcon,
    iconBackground: 'bg-indigo-500',
  },
]);

const recentOrders = ref([
  { id: 1, number: 'ORD-2024-001', customer: 'Acme Corp', total: '$1,234.56', status: 'pending' },
  { id: 2, number: 'ORD-2024-002', customer: 'Tech Solutions', total: '$987.65', status: 'processing' },
  { id: 3, number: 'ORD-2024-003', customer: 'Global Inc', total: '$2,456.78', status: 'completed' },
  { id: 4, number: 'ORD-2024-004', customer: 'Startup LLC', total: '$543.21', status: 'pending' },
]);

const topProducts = ref([
  { id: 1, name: 'Laptop Pro 15', category: 'Electronics', sold: 45 },
  { id: 2, name: 'Wireless Mouse', category: 'Accessories', sold: 123 },
  { id: 3, name: 'USB-C Cable', category: 'Cables', sold: 89 },
  { id: 4, name: 'Monitor 27"', category: 'Electronics', sold: 67 },
]);

const alerts = ref([
  {
    id: 1,
    variant: 'warning',
    title: 'Low Stock Alert',
    message: '5 products are running low on stock.',
  },
]);

function navigateTo(path) {
  router.push(path);
}

function dismissAlert(id) {
  const index = alerts.value.findIndex(a => a.id === id);
  if (index > -1) {
    alerts.value.splice(index, 1);
  }
}

function getStatusVariant(status) {
  const variants = {
    pending: 'warning',
    processing: 'info',
    completed: 'success',
    cancelled: 'danger',
  };
  return variants[status] || 'default';
}
</script>
