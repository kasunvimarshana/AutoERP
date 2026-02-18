<template>
  <div class="table-widget">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th
              v-for="column in data?.columns || []"
              :key="column.key"
              class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
            >
              {{ column.label }}
            </th>
            <th
              v-if="hasActions"
              class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          <tr
            v-for="(row, index) in data?.rows || []"
            :key="index"
            class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            <td
              v-for="column in data?.columns || []"
              :key="column.key"
              class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100"
            >
              {{ getCellValue(row, column) }}
            </td>
            <td
              v-if="hasActions"
              class="px-4 py-3 text-sm text-right space-x-2"
            >
              <button
                v-for="action in data?.actions || []"
                :key="action.id"
                class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                @click="handleAction(action, row)"
              >
                {{ action.label }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div
      v-if="!data?.rows || data.rows.length === 0"
      class="text-center py-8 text-gray-500"
    >
      No data available
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { formatDate, formatNumber, formatCurrency } from '@/utils/formatters';

interface Column {
  key: string;
  label: string;
  type?: 'text' | 'number' | 'date' | 'currency' | 'boolean';
  formatter?: (value: any) => string;
}

interface Action {
  id: string;
  label: string;
  permissions?: string[];
}

interface TableData {
  columns: Column[];
  rows: Record<string, any>[];
  actions?: Action[];
}

interface Props {
  data: TableData;
  config?: any;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  action: [{ action: string; data: any }];
}>();

const hasActions = computed(() => {
  return props.data?.actions && props.data.actions.length > 0;
});

const getCellValue = (row: Record<string, any>, column: Column) => {
  const value = row[column.key];

  if (value === null || value === undefined) {
    return '-';
  }

  if (column.formatter) {
    return column.formatter(value);
  }

  switch (column.type) {
    case 'date':
      return formatDate(value);
    case 'number':
      return formatNumber(value);
    case 'currency':
      return formatCurrency(value);
    case 'boolean':
      return value ? 'Yes' : 'No';
    default:
      return String(value);
  }
};

const handleAction = (action: Action, row: Record<string, any>) => {
  emit('action', {
    action: action.id,
    data: row,
  });
};
</script>
