<template>
  <div>
    <h1 class="text-3xl font-bold text-secondary-900 mb-6">{{ $t('dashboard.title') }}</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <BaseCard v-for="stat in stats" :key="stat.label" class="text-center">
        <div class="text-4xl mb-2">{{ stat.icon }}</div>
        <div class="text-3xl font-bold text-primary-600">{{ stat.value }}</div>
        <div class="text-sm text-secondary-600">{{ stat.label }}</div>
      </BaseCard>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <BaseCard :title="$t('dashboard.recentActivity')">
        <div class="space-y-3">
          <div v-for="activity in recentActivities" :key="activity.id" class="flex items-center justify-between py-2 border-b border-secondary-100 last:border-0">
            <div>
              <p class="font-medium text-secondary-900">{{ activity.title }}</p>
              <p class="text-sm text-secondary-600">{{ activity.time }}</p>
            </div>
            <span class="text-2xl">{{ activity.icon }}</span>
          </div>
        </div>
      </BaseCard>

      <BaseCard :title="$t('dashboard.statistics')">
        <div class="h-64 flex items-center justify-center text-secondary-400">
          Chart Placeholder
        </div>
      </BaseCard>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import BaseCard from '@/components/common/BaseCard.vue'

const stats = ref([
  { icon: '👥', value: '1,234', label: 'Total Users' },
  { icon: '🛒', value: '567', label: 'Orders' },
  { icon: '💰', value: '$45,678', label: 'Revenue' },
  { icon: '📦', value: '890', label: 'Products' }
])

const recentActivities = ref([
  { id: 1, title: 'New order created', time: '5 minutes ago', icon: '🛒' },
  { id: 2, title: 'Payment received', time: '15 minutes ago', icon: '💳' },
  { id: 3, title: 'New customer registered', time: '1 hour ago', icon: '👤' },
  { id: 4, title: 'Invoice generated', time: '2 hours ago', icon: '🧾' }
])
</script>
