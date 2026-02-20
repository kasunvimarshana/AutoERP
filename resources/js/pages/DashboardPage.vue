<template>
  <div class="space-y-6">
    <PageHeader title="Dashboard" subtitle="System overview at a glance" />

    <div v-if="loading" class="flex items-center justify-center py-16">
      <AppSpinner size="lg" />
    </div>

    <template v-else>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          v-for="stat in stats"
          :key="stat.label"
          :label="stat.label"
          :value="stat.value"
          :color="stat.color"
        />
      </div>

      <!-- Quick links -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mt-4">
        <RouterLink
          v-for="link in quickLinks"
          :key="link.to"
          :to="link.to"
          class="flex flex-col items-center justify-center bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow text-center gap-2"
        >
          <span class="text-3xl">{{ link.icon }}</span>
          <span class="text-xs font-medium text-gray-600">{{ link.label }}</span>
        </RouterLink>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { orderService } from '@/services/orders';
import { invoiceService } from '@/services/invoices';
import { productService } from '@/services/products';
import { inventoryService } from '@/services/inventory';
import StatCard from '@/components/StatCard.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppSpinner from '@/components/AppSpinner.vue';

interface StatItem {
  label: string;
  value: string | number;
  color: string;
}

const loading = ref(true);
const stats = ref<StatItem[]>([
  { label: 'Total Orders', value: '—', color: 'blue' },
  { label: 'Total Invoices', value: '—', color: 'green' },
  { label: 'Total Products', value: '—', color: 'purple' },
  { label: 'Low Stock Alerts', value: '—', color: 'red' },
]);

const quickLinks = [
  { to: '/products', label: 'Products', icon: '📦' },
  { to: '/orders', label: 'New Order', icon: '🛒' },
  { to: '/invoices', label: 'Invoices', icon: '🧾' },
  { to: '/crm', label: 'CRM', icon: '🤝' },
  { to: '/reports', label: 'Reports', icon: '📈' },
];

onMounted(async () => {
  try {
    const [orders, invoices, products, alerts] = await Promise.allSettled([
      orderService.list({ per_page: 1 }),
      invoiceService.list({ per_page: 1 }),
      productService.list({ per_page: 1 }),
      inventoryService.listLowStock({ per_page: 1 }),
    ]);

    if (orders.status === 'fulfilled') {
      const d = orders.value.data as { total?: number; data?: unknown[] } | unknown[];
      const v = (Array.isArray(d) ? d.length : d.total) ?? '—';
      if (stats.value[0]) stats.value[0].value = v;
    }
    if (invoices.status === 'fulfilled') {
      const d = invoices.value.data as { total?: number; data?: unknown[] } | unknown[];
      const v = (Array.isArray(d) ? d.length : d.total) ?? '—';
      if (stats.value[1]) stats.value[1].value = v;
    }
    if (products.status === 'fulfilled') {
      const d = products.value.data as { total?: number; data?: unknown[] } | unknown[];
      const v = (Array.isArray(d) ? d.length : d.total) ?? '—';
      if (stats.value[2]) stats.value[2].value = v;
    }
    if (alerts.status === 'fulfilled') {
      const d = alerts.value.data as { total?: number; data?: unknown[] } | unknown[];
      const v = (Array.isArray(d) ? d.length : d.total) ?? '—';
      if (stats.value[3]) stats.value[3].value = v;
    }
  } finally {
    loading.value = false;
  }
});
</script>
