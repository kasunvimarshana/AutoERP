<template>
  <div class="dynamic-table-container">
    <!-- Actions Bar -->
    <div v-if="!hideActions" class="table-actions">
      <slot name="actions">
        <button
          v-if="canCreate"
          @click="handleCreate"
          class="btn btn-primary"
        >
          <span class="icon">+</span>
          Add New
        </button>
      </slot>
      
      <div class="table-filters">
        <input
          v-if="searchable"
          v-model="searchQuery"
          type="text"
          placeholder="Search..."
          class="search-input"
          @input="handleSearch"
        />
        <slot name="filters" />
      </div>
    </div>

    <!-- Table -->
    <div class="table-wrapper">
      <table class="dynamic-table">
        <thead>
          <tr>
            <th v-if="selectable" class="checkbox-cell">
              <input
                type="checkbox"
                :checked="allSelected"
                @change="toggleSelectAll"
              />
            </th>
            <th
              v-for="field in visibleFields"
              :key="field.name"
              :style="{ width: field.ui_config?.width }"
              :class="{ sortable: field.is_sortable }"
              @click="field.is_sortable && handleSort(field.name)"
            >
              {{ field.label }}
              <span v-if="field.is_sortable" class="sort-icon">
                <span v-if="sortBy === field.name">
                  {{ sortOrder === 'asc' ? '↑' : '↓' }}
                </span>
                <span v-else class="sort-default">⇅</span>
              </span>
            </th>
            <th v-if="!hideRowActions" class="actions-cell">Actions</th>
          </tr>
        </thead>
        <tbody v-if="!loading && filteredData.length > 0">
          <tr
            v-for="(row, index) in paginatedData"
            :key="row.id || index"
            @click="handleRowClick(row)"
            :class="{ clickable: !!rowClickHandler }"
          >
            <td v-if="selectable" class="checkbox-cell">
              <input
                type="checkbox"
                :checked="selectedRows.includes(row.id)"
                @change="toggleSelectRow(row.id)"
                @click.stop
              />
            </td>
            <td
              v-for="field in visibleFields"
              :key="field.name"
            >
              <slot :name="`cell-${field.name}`" :row="row" :field="field">
                {{ formatCellValue(row[field.name], field) }}
              </slot>
            </td>
            <td v-if="!hideRowActions" class="actions-cell">
              <slot name="row-actions" :row="row">
                <button
                  v-if="canEdit"
                  @click.stop="handleEdit(row)"
                  class="action-btn edit"
                  title="Edit"
                >
                  ✎
                </button>
                <button
                  v-if="canDelete"
                  @click.stop="handleDelete(row)"
                  class="action-btn delete"
                  title="Delete"
                >
                  🗑
                </button>
              </slot>
            </td>
          </tr>
        </tbody>
        <tbody v-else-if="loading">
          <tr>
            <td :colspan="totalColumns" class="loading-cell">
              <div class="loading-spinner">Loading...</div>
            </td>
          </tr>
        </tbody>
        <tbody v-else>
          <tr>
            <td :colspan="totalColumns" class="empty-cell">
              <slot name="empty">
                <div class="empty-state">
                  <p>No data available</p>
                </div>
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="paginated && !loading" class="pagination">
      <div class="pagination-info">
        Showing {{ paginationStart }} to {{ paginationEnd }} of {{ filteredData.length }} entries
      </div>
      <div class="pagination-controls">
        <button
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="pagination-btn"
        >
          Previous
        </button>
        <span class="pagination-pages">
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="goToPage(page)"
            :class="{ active: currentPage === page }"
            class="pagination-btn"
          >
            {{ page }}
          </button>
        </span>
        <button
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="pagination-btn"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  fields: {
    type: Array,
    required: true
  },
  data: {
    type: Array,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  },
  searchable: {
    type: Boolean,
    default: true
  },
  selectable: {
    type: Boolean,
    default: false
  },
  paginated: {
    type: Boolean,
    default: true
  },
  perPage: {
    type: Number,
    default: 15
  },
  canCreate: {
    type: Boolean,
    default: true
  },
  canEdit: {
    type: Boolean,
    default: true
  },
  canDelete: {
    type: Boolean,
    default: true
  },
  hideActions: {
    type: Boolean,
    default: false
  },
  hideRowActions: {
    type: Boolean,
    default: false
  },
  rowClickHandler: {
    type: Function,
    default: null
  }
});

const emit = defineEmits(['create', 'edit', 'delete', 'row-click', 'selection-change', 'sort']);

const searchQuery = ref('');
const sortBy = ref('');
const sortOrder = ref('asc');
const currentPage = ref(1);
const selectedRows = ref([]);

const visibleFields = computed(() => {
  return props.fields.filter(field => field.is_visible_list !== false);
});

const totalColumns = computed(() => {
  let count = visibleFields.value.length;
  if (props.selectable) count++;
  if (!props.hideRowActions) count++;
  return count;
});

const filteredData = computed(() => {
  let data = [...props.data];

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    data = data.filter(row => {
      return props.fields.some(field => {
        if (!field.is_searchable) return false;
        const value = row[field.name];
        return value && value.toString().toLowerCase().includes(query);
      });
    });
  }

  // Sorting
  if (sortBy.value) {
    data.sort((a, b) => {
      const aVal = a[sortBy.value];
      const bVal = b[sortBy.value];
      
      if (aVal === bVal) return 0;
      
      const comparison = aVal > bVal ? 1 : -1;
      return sortOrder.value === 'asc' ? comparison : -comparison;
    });
  }

  return data;
});

const totalPages = computed(() => {
  if (!props.paginated) return 1;
  return Math.ceil(filteredData.value.length / props.perPage);
});

const paginatedData = computed(() => {
  if (!props.paginated) return filteredData.value;
  
  const start = (currentPage.value - 1) * props.perPage;
  const end = start + props.perPage;
  return filteredData.value.slice(start, end);
});

const paginationStart = computed(() => {
  if (filteredData.value.length === 0) return 0;
  return (currentPage.value - 1) * props.perPage + 1;
});

const paginationEnd = computed(() => {
  const end = currentPage.value * props.perPage;
  return Math.min(end, filteredData.value.length);
});

const visiblePages = computed(() => {
  const pages = [];
  const maxVisible = 5;
  let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2));
  let end = Math.min(totalPages.value, start + maxVisible - 1);
  
  if (end - start + 1 < maxVisible) {
    start = Math.max(1, end - maxVisible + 1);
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  
  return pages;
});

const allSelected = computed(() => {
  return paginatedData.value.length > 0 &&
    paginatedData.value.every(row => selectedRows.value.includes(row.id));
});

const formatCellValue = (value, field) => {
  if (value === null || value === undefined) return '-';
  
  // Date formatting
  if (field.type === 'date' || field.type === 'datetime') {
    return new Date(value).toLocaleDateString();
  }
  
  // Number formatting
  if (field.type === 'number' || field.type === 'currency') {
    return typeof value === 'number' ? value.toLocaleString() : value;
  }
  
  // Boolean formatting
  if (field.type === 'checkbox' || field.type === 'boolean') {
    return value ? 'Yes' : 'No';
  }
  
  return value;
};

const handleSearch = () => {
  currentPage.value = 1; // Reset to first page on search
};

const handleSort = (fieldName) => {
  if (sortBy.value === fieldName) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = fieldName;
    sortOrder.value = 'asc';
  }
  emit('sort', { field: sortBy.value, order: sortOrder.value });
};

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
  }
};

const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedRows.value = [];
  } else {
    selectedRows.value = paginatedData.value.map(row => row.id);
  }
  emit('selection-change', selectedRows.value);
};

const toggleSelectRow = (id) => {
  const index = selectedRows.value.indexOf(id);
  if (index > -1) {
    selectedRows.value.splice(index, 1);
  } else {
    selectedRows.value.push(id);
  }
  emit('selection-change', selectedRows.value);
};

const handleCreate = () => {
  emit('create');
};

const handleEdit = (row) => {
  emit('edit', row);
};

const handleDelete = (row) => {
  emit('delete', row);
};

const handleRowClick = (row) => {
  if (props.rowClickHandler) {
    props.rowClickHandler(row);
  }
  emit('row-click', row);
};

// Reset page when data changes
watch(() => props.data, () => {
  currentPage.value = 1;
});

defineExpose({
  selectedRows,
  clearSelection: () => { selectedRows.value = []; },
  refresh: () => { currentPage.value = 1; searchQuery.value = ''; }
});
</script>

<style scoped>
.dynamic-table-container {
  width: 100%;
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.table-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.table-filters {
  display: flex;
  gap: 0.5rem;
}

.search-input {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
}

.table-wrapper {
  overflow-x: auto;
}

.dynamic-table {
  width: 100%;
  border-collapse: collapse;
}

.dynamic-table thead {
  background-color: #f9fafb;
  border-bottom: 2px solid #e5e7eb;
}

.dynamic-table th {
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
  font-size: 0.875rem;
  color: #374151;
  user-select: none;
}

.dynamic-table th.sortable {
  cursor: pointer;
}

.dynamic-table th.sortable:hover {
  background-color: #f3f4f6;
}

.sort-icon {
  margin-left: 0.25rem;
  font-size: 0.75rem;
}

.sort-default {
  color: #9ca3af;
}

.dynamic-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e5e7eb;
  font-size: 0.875rem;
  color: #374151;
}

.dynamic-table tbody tr:hover {
  background-color: #f9fafb;
}

.dynamic-table tbody tr.clickable {
  cursor: pointer;
}

.checkbox-cell {
  width: 40px;
  text-align: center;
}

.actions-cell {
  width: 100px;
  text-align: right;
}

.action-btn {
  padding: 0.25rem 0.5rem;
  margin-left: 0.25rem;
  border: none;
  background: none;
  cursor: pointer;
  font-size: 1rem;
  transition: opacity 0.15s;
}

.action-btn:hover {
  opacity: 0.7;
}

.loading-cell,
.empty-cell {
  padding: 2rem;
  text-align: center;
  color: #6b7280;
}

.loading-spinner {
  display: inline-block;
}

.empty-state p {
  margin: 0;
}

.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-top: 1px solid #e5e7eb;
}

.pagination-info {
  font-size: 0.875rem;
  color: #6b7280;
}

.pagination-controls {
  display: flex;
  gap: 0.25rem;
}

.pagination-pages {
  display: flex;
  gap: 0.25rem;
}

.pagination-btn {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  background: white;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.15s;
}

.pagination-btn:hover:not(:disabled) {
  background: #f3f4f6;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-btn.active {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.btn {
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s;
  border: none;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background-color: #2563eb;
}

.icon {
  margin-right: 0.25rem;
}
</style>
