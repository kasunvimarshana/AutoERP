<template>
  <div class="table-skeleton">
    <!-- Header -->
    <div class="skeleton-header">
      <div
        v-for="col in columns"
        :key="col"
        class="skeleton-header-cell"
      >
        <SkeletonLoader
          type="text"
          :width="getHeaderWidth(col)"
          height="20"
        />
      </div>
    </div>

    <!-- Rows -->
    <div
      v-for="row in rows"
      :key="row"
      class="skeleton-row"
    >
      <div
        v-for="col in columns"
        :key="col"
        class="skeleton-cell"
      >
        <SkeletonLoader
          type="text"
          :width="getCellWidth(col)"
          height="16"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import SkeletonLoader from './SkeletonLoader.vue';

interface Props {
  rows?: number;
  columns?: number;
  columnWidths?: number[];
}

const props = withDefaults(defineProps<Props>(), {
  rows: 5,
  columns: 4,
});

const getHeaderWidth = (index: number) => {
  if (props.columnWidths && props.columnWidths[index]) {
    return props.columnWidths[index];
  }
  return 80 + Math.random() * 40; // Random width between 80-120
};

const getCellWidth = (index: number) => {
  if (props.columnWidths && props.columnWidths[index]) {
    return props.columnWidths[index] * 0.9; // Slightly smaller than header
  }
  return 60 + Math.random() * 60; // Random width between 60-120
};
</script>

<style scoped>
.table-skeleton {
  width: 100%;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  overflow: hidden;
  background: white;
}

.skeleton-header {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.skeleton-header-cell {
  flex: 1;
}

.skeleton-row {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
}

.skeleton-row:last-child {
  border-bottom: none;
}

.skeleton-cell {
  flex: 1;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .table-skeleton {
    background: #1f2937;
    border-color: #374151;
  }

  .skeleton-header {
    background: #111827;
    border-color: #374151;
  }

  .skeleton-row {
    border-color: #374151;
  }
}
</style>
