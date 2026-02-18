<template>
  <div class="stat-widget">
    <div class="flex items-center justify-between">
      <!-- Value -->
      <div class="flex-1">
        <div class="text-3xl font-bold text-gray-900 dark:text-white">
          {{ formattedValue }}
        </div>
        <div
          v-if="data?.label"
          class="text-sm text-gray-500 dark:text-gray-400 mt-1"
        >
          {{ data.label }}
        </div>
      </div>

      <!-- Icon -->
      <div
        v-if="data?.icon"
        class="flex-shrink-0 ml-4"
      >
        <div :class="iconClasses">
          <component
            :is="iconComponent"
            v-if="iconComponent"
            class="w-6 h-6"
          />
        </div>
      </div>
    </div>

    <!-- Trend Indicator -->
    <div
      v-if="data?.trend"
      class="mt-4 flex items-center"
    >
      <span
        :class="trendClasses"
        class="text-sm font-medium"
      >
        <svg
          v-if="data.trend.direction === 'up'"
          class="w-4 h-4 inline"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path
            fill-rule="evenodd"
            d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"
            clip-rule="evenodd"
          />
        </svg>
        <svg
          v-else-if="data.trend.direction === 'down'"
          class="w-4 h-4 inline"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path
            fill-rule="evenodd"
            d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z"
            clip-rule="evenodd"
          />
        </svg>
        {{ data.trend.value }}
      </span>
      <span
        v-if="data.trend.label"
        class="ml-2 text-sm text-gray-500 dark:text-gray-400"
      >
        {{ data.trend.label }}
      </span>
    </div>

    <!-- Description -->
    <div
      v-if="data?.description"
      class="mt-2 text-xs text-gray-500 dark:text-gray-400"
    >
      {{ data.description }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { formatNumber, formatCurrency } from '@/utils/formatters';

interface StatData {
  value: number | string;
  label?: string;
  icon?: string;
  format?: 'number' | 'currency' | 'percentage' | 'custom';
  formatter?: (value: any) => string;
  trend?: {
    value: string;
    direction: 'up' | 'down' | 'neutral';
    label?: string;
    positive?: boolean;
  };
  description?: string;
}

interface Props {
  data: StatData;
  config?: any;
}

const props = defineProps<Props>();

const formattedValue = computed(() => {
  if (!props.data) return '-';

  const { value, format, formatter } = props.data;

  if (formatter) {
    return formatter(value);
  }

  switch (format) {
    case 'currency':
      return formatCurrency(Number(value));
    case 'percentage':
      return `${value}%`;
    case 'number':
      return formatNumber(Number(value));
    default:
      return String(value);
  }
});

const iconComponent = computed(() => {
  if (!props.data?.icon) return null;
  
  // Dynamic icon loading from heroicons
  try {
    return () => import(`@heroicons/vue/24/outline/${props.data.icon}.js`);
  } catch {
    return null;
  }
});

const iconClasses = computed(() => {
  const baseClasses = 'w-12 h-12 rounded-full flex items-center justify-center';
  const colorClass = props.data?.trend?.positive !== false
    ? 'bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400'
    : 'bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400';
  
  return `${baseClasses} ${colorClass}`;
});

const trendClasses = computed(() => {
  if (!props.data?.trend) return '';

  const { direction, positive } = props.data.trend;
  
  // Determine if trend is positive based on direction and positive flag
  const isPositive = positive !== false && direction === 'up' || positive === false && direction === 'down';
  
  return isPositive
    ? 'text-green-600 dark:text-green-400'
    : 'text-red-600 dark:text-red-400';
});
</script>
