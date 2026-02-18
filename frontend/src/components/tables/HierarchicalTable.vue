<template>
  <div class="hierarchical-table">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th class="w-12 px-4 py-3" />
            <th
              v-for="column in columns"
              :key="column.key"
              class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              :style="{ width: column.width }"
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
          <template
            v-for="item in rootItems"
            :key="getItemKey(item)"
          >
            <HierarchicalTableRow
              :item="item"
              :columns="columns"
              :level="0"
              :expanded="isExpanded(item)"
              :children-key="childrenKey"
              :has-actions="hasActions"
              :actions="actions"
              @toggle="toggleExpand(item)"
              @action="handleAction"
            />
          </template>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div
      v-if="!data || data.length === 0"
      class="text-center py-8 text-gray-500"
    >
      No data available
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import HierarchicalTableRow from './HierarchicalTableRow.vue';
import type { TableColumnMetadata, TableActionMetadata } from '@/types/metadata';

interface Props {
  data: any[];
  columns: TableColumnMetadata[];
  actions?: TableActionMetadata[];
  childrenKey?: string;
  keyField?: string;
  defaultExpanded?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  childrenKey: 'children',
  keyField: 'id',
  defaultExpanded: false,
});

const emit = defineEmits<{
  action: [{ action: string; item: any }];
}>();

const expandedItems = ref<Set<string | number>>(new Set());

const hasActions = computed(() => {
  return props.actions && props.actions.length > 0;
});

const rootItems = computed(() => {
  // Items without parent or at root level
  return props.data.filter(item => !item.parentId || item.level === 0);
});

const getItemKey = (item: any) => {
  return item[props.keyField];
};

const isExpanded = (item: any) => {
  return expandedItems.value.has(getItemKey(item));
};

const toggleExpand = (item: any) => {
  const key = getItemKey(item);
  if (expandedItems.value.has(key)) {
    expandedItems.value.delete(key);
  } else {
    expandedItems.value.add(key);
  }
};

const handleAction = (payload: { action: string; item: any }) => {
  emit('action', payload);
};

// Initialize expanded items if defaultExpanded
if (props.defaultExpanded) {
  props.data.forEach(item => {
    if (item[props.childrenKey] && item[props.childrenKey].length > 0) {
      expandedItems.value.add(getItemKey(item));
    }
  });
}

defineExpose({
  expandAll: () => {
    props.data.forEach(item => {
      if (item[props.childrenKey] && item[props.childrenKey].length > 0) {
        expandedItems.value.add(getItemKey(item));
      }
    });
  },
  collapseAll: () => {
    expandedItems.value.clear();
  },
});
</script>
