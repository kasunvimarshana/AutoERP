<template>
  <div class="flex flex-col">
    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
      <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th
                  v-for="column in columns"
                  :key="column.key"
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  :class="column.sortable ? 'cursor-pointer hover:bg-gray-100' : ''"
                  @click="column.sortable ? handleSort(column.key) : null"
                >
                  <div class="flex items-center space-x-1">
                    <span>{{ column.label }}</span>
                    <span v-if="column.sortable && sortBy === column.key">
                      <svg v-if="sortOrder === 'asc'" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                      </svg>
                      <svg v-else class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    </span>
                  </div>
                </th>
                <th v-if="actions && actions.length" scope="col" class="relative px-6 py-3">
                  <span class="sr-only">Actions</span>
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-if="loading" class="bg-gray-50">
                <td :colspan="columns.length + (actions && actions.length ? 1 : 0)" class="px-6 py-4 text-center text-sm text-gray-500">
                  Loading...
                </td>
              </tr>
              <tr v-else-if="!data || data.length === 0" class="bg-gray-50">
                <td :colspan="columns.length + (actions && actions.length ? 1 : 0)" class="px-6 py-4 text-center text-sm text-gray-500">
                  {{ emptyMessage }}
                </td>
              </tr>
              <tr v-else v-for="(row, index) in data" :key="index" class="hover:bg-gray-50">
                <td
                  v-for="column in columns"
                  :key="column.key"
                  class="px-6 py-4 whitespace-nowrap text-sm"
                  :class="column.class || ''"
                >
                  <slot :name="`cell-${column.key}`" :row="row" :value="getValue(row, column.key)">
                    {{ formatValue(getValue(row, column.key), column.format) }}
                  </slot>
                </td>
                <td v-if="actions && actions.length" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <slot name="actions" :row="row">
                    <button
                      v-for="action in actions"
                      :key="action.name"
                      type="button"
                      :class="action.class || 'text-indigo-600 hover:text-indigo-900'"
                      class="mr-3 last:mr-0"
                      @click="$emit(`action:${action.name}`, row)"
                    >
                      {{ action.label }}
                    </button>
                  </slot>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, defineProps, defineEmits } from 'vue';

const props = defineProps({
  columns: {
    type: Array,
    required: true,
  },
  data: {
    type: Array,
    default: () => [],
  },
  actions: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  emptyMessage: {
    type: String,
    default: 'No data available',
  },
});

const emit = defineEmits(['sort']);

const sortBy = ref(null);
const sortOrder = ref('asc');

function handleSort(key) {
  if (sortBy.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = key;
    sortOrder.value = 'asc';
  }
  
  emit('sort', { key: sortBy.value, order: sortOrder.value });
}

function getValue(row, key) {
  return key.split('.').reduce((obj, k) => obj?.[k], row);
}

function formatValue(value, format) {
  if (value === null || value === undefined) return '-';
  
  if (format === 'currency') {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
  }
  
  if (format === 'date') {
    return new Date(value).toLocaleDateString();
  }
  
  if (format === 'datetime') {
    return new Date(value).toLocaleString();
  }
  
  return value;
}
</script>
