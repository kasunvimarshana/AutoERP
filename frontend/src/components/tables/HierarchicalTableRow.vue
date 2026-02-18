<template>
  <template v-if="hasChildren || forceShow">
    <!-- Parent Row -->
    <tr
      class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
      :class="{ 'bg-gray-50 dark:bg-gray-700/50': level > 0 }"
    >
      <!-- Expand/Collapse Button -->
      <td
        class="px-4 py-3"
        :style="{ paddingLeft: `${level * 1.5 + 1}rem` }"
      >
        <button
          v-if="hasChildren"
          type="button"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-transform"
          :class="{ 'rotate-90': expanded }"
          @click="$emit('toggle', item)"
        >
          <svg
            class="w-4 h-4"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path
              fill-rule="evenodd"
              d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
              clip-rule="evenodd"
            />
          </svg>
        </button>
      </td>

      <!-- Data Columns -->
      <td
        v-for="column in columns"
        :key="column.key"
        class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100"
      >
        <component
          :is="loadCustomComponent(column.customComponent)"
          v-if="column.customComponent"
          :value="item[column.key]"
          :row="item"
          v-bind="column.customProps"
        />
        <span v-else>
          {{ getCellValue(item, column) }}
        </span>
      </td>

      <!-- Actions -->
      <td
        v-if="hasActions"
        class="px-4 py-3 text-sm text-right space-x-2"
      >
        <button
          v-for="action in actions"
          :key="action.id"
          class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
          @click="$emit('action', { action: action.id, item })"
        >
          {{ action.label }}
        </button>
      </td>
    </tr>

    <!-- Child Rows (recursively rendered) -->
    <template v-if="expanded && hasChildren">
      <HierarchicalTableRow
        v-for="child in children"
        :key="getChildKey(child)"
        :item="child"
        :columns="columns"
        :level="level + 1"
        :expanded="isChildExpanded(child)"
        :children-key="childrenKey"
        :has-actions="hasActions"
        :actions="actions"
        @toggle="$emit('toggle', $event)"
        @action="$emit('action', $event)"
      />
    </template>
  </template>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue';
import type { TableColumnMetadata, TableActionMetadata } from '@/types/metadata';
import { formatDate, formatNumber, formatCurrency } from '@/utils/formatters';

interface Props {
  item: any;
  columns: TableColumnMetadata[];
  level: number;
  expanded: boolean;
  childrenKey: string;
  hasActions: boolean;
  actions?: TableActionMetadata[];
}

const props = defineProps<Props>();

defineEmits<{
  toggle: [item: any];
  action: [{ action: string; item: any }];
}>();

const hasChildren = computed(() => {
  return props.item[props.childrenKey] && props.item[props.childrenKey].length > 0;
});

const children = computed(() => {
  return props.item[props.childrenKey] || [];
});

const forceShow = computed(() => {
  // Always show the row even if no children
  return true;
});

const getCellValue = (row: Record<string, any>, column: TableColumnMetadata) => {
  const value = row[column.key];

  if (value === null || value === undefined) {
    return '-';
  }

  switch (column.type) {
    case 'date':
      return formatDate(value);
    case 'number':
      return formatNumber(value);
    case 'boolean':
      return value ? 'Yes' : 'No';
    default:
      return String(value);
  }
};

const loadCustomComponent = (componentName: string) => {
  return defineAsyncComponent({
    loader: () => import(`../custom/${componentName}.vue`),
    errorComponent: () => null,
    delay: 200,
    timeout: 3000,
  });
};

const getChildKey = (child: any) => {
  // Use a combination of parent item id and child index for stable keys
  return child.id || `${props.item.id}-child-${children.value.indexOf(child)}`;
};

const isChildExpanded = (child: any) => {
  // This would need to be tracked by parent component
  return false;
};
</script>
