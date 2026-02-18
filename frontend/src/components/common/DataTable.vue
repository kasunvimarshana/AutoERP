<template>
  <div class="data-table-container">
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>
    
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th v-for="column in columns" :key="column.key" :style="{ width: column.width }">
              <div class="th-content">
                <span>{{ column.label }}</span>
                <button
                  v-if="column.sortable"
                  @click="$emit('sort', column.key)"
                  class="sort-btn"
                >
                  <ChevronUpDownIcon class="icon" />
                </button>
              </div>
            </th>
            <th v-if="actions" class="actions-col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="data.length === 0">
            <td :colspan="columns.length + (actions ? 1 : 0)" class="empty-state">
              {{ emptyText }}
            </td>
          </tr>
          <tr v-for="row in data" :key="row.id" @click="$emit('row-click', row)">
            <td v-for="column in columns" :key="column.key">
              <slot :name="`cell-${column.key}`" :row="row">
                {{ row[column.key] }}
              </slot>
            </td>
            <td v-if="actions" class="actions-cell">
              <slot name="actions" :row="row"></slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <div v-if="pagination" class="pagination">
      <button
        @click="$emit('page-change', currentPage - 1)"
        :disabled="currentPage === 1"
        class="pagination-btn"
      >
        Previous
      </button>
      <span class="page-info">
        Page {{ currentPage }} of {{ totalPages }}
      </span>
      <button
        @click="$emit('page-change', currentPage + 1)"
        :disabled="currentPage === totalPages"
        class="pagination-btn"
      >
        Next
      </button>
    </div>
  </div>
</template>

<script setup>
import { ChevronUpDownIcon } from '@heroicons/vue/24/outline'

defineProps({
  columns: { type: Array, required: true },
  data: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  actions: { type: Boolean, default: true },
  pagination: { type: Boolean, default: true },
  currentPage: { type: Number, default: 1 },
  totalPages: { type: Number, default: 1 },
  emptyText: { type: String, default: 'No data available' }
})

defineEmits(['row-click', 'sort', 'page-change'])
</script>

<style scoped>
.data-table-container {
  position: relative;
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.table-wrapper {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: #f9fafb;
  border-bottom: 2px solid #e5e7eb;
}

.data-table th {
  padding: 12px 16px;
  text-align: left;
  font-weight: 600;
  color: #374151;
  font-size: 14px;
}

.th-content {
  display: flex;
  align-items: center;
  gap: 8px;
}

.sort-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 2px;
  color: #9ca3af;
}

.sort-btn:hover {
  color: #4b5563;
}

.icon {
  width: 16px;
  height: 16px;
}

.data-table tbody tr {
  border-bottom: 1px solid #f3f4f6;
  transition: background 0.2s;
}

.data-table tbody tr:hover {
  background: #f9fafb;
}

.data-table td {
  padding: 12px 16px;
  font-size: 14px;
  color: #1f2937;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #9ca3af;
}

.actions-col {
  width: 150px;
}

.actions-cell {
  white-space: nowrap;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border-top: 1px solid #e5e7eb;
}

.pagination-btn {
  padding: 8px 16px;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  color: #374151;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  background: #f9fafb;
  border-color: #9ca3af;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  font-size: 14px;
  color: #6b7280;
}
</style>
