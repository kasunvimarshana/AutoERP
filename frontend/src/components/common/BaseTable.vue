<template>
  <div class="overflow-x-auto">
    <table class="table">
      <thead>
        <tr>
          <th v-for="column in columns" :key="column.key" @click="handleSort(column)">
            <div class="flex items-center justify-between">
              <span>{{ column.label }}</span>
              <span v-if="column.sortable" class="ml-2">
                <svg v-if="sortKey === column.key" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path v-if="sortOrder === 'asc'" d="M5 10l5-5 5 5H5z" />
                  <path v-else d="M5 10l5 5 5-5H5z" />
                </svg>
                <svg v-else class="w-4 h-4 text-secondary-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M5 10l5-5 5 5H5z" />
                </svg>
              </span>
            </div>
          </th>
          <th v-if="actions">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td :colspan="columns.length + (actions ? 1 : 0)" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
          </td>
        </tr>
        <tr v-else-if="!data || data.length === 0">
          <td :colspan="columns.length + (actions ? 1 : 0)" class="text-center py-8 text-secondary-500">
            {{ emptyText }}
          </td>
        </tr>
        <tr v-else v-for="(row, index) in sortedData" :key="index">
          <td v-for="column in columns" :key="column.key">
            <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
              {{ row[column.key] }}
            </slot>
          </td>
          <td v-if="actions">
            <slot name="actions" :row="row" />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  columns: {
    type: Array,
    required: true
  },
  data: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  actions: {
    type: Boolean,
    default: false
  },
  emptyText: {
    type: String,
    default: 'No data available'
  }
})

const sortKey = ref('')
const sortOrder = ref('asc')

const handleSort = (column) => {
  if (!column.sortable) return
  
  if (sortKey.value === column.key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = column.key
    sortOrder.value = 'asc'
  }
}

const sortedData = computed(() => {
  if (!sortKey.value) return props.data
  
  return [...props.data].sort((a, b) => {
    const aVal = a[sortKey.value]
    const bVal = b[sortKey.value]
    
    if (sortOrder.value === 'asc') {
      return aVal > bVal ? 1 : -1
    } else {
      return aVal < bVal ? 1 : -1
    }
  })
})
</script>
