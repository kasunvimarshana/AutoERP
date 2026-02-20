<template>
  <div class="bg-white shadow rounded-lg overflow-hidden">
    <!-- Table toolbar -->
    <div v-if="$slots.toolbar || searchable" class="px-4 py-3 border-b border-gray-200 flex items-center gap-3">
      <input
        v-if="searchable"
        :value="search"
        type="search"
        :placeholder="searchPlaceholder"
        class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-56"
        @input="onSearchInput"
      />
      <slot name="toolbar" />
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="flex items-center justify-center py-16">
      <AppSpinner size="lg" />
    </div>

    <!-- Error state -->
    <div
      v-else-if="error"
      class="bg-red-50 border-b border-red-200 text-red-700 text-sm px-4 py-3"
    >
      {{ error }}
    </div>

    <!-- Empty state -->
    <AppEmptyState
      v-else-if="!rows.length"
      :icon="emptyIcon"
      :title="emptyTitle"
      :message="emptyMessage"
    />

    <!-- Data table -->
    <template v-else>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                v-for="col in columns"
                :key="col.key"
                class="px-4 py-3 text-xs font-medium text-gray-500 uppercase whitespace-nowrap"
                :class="col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left'"
              >
                {{ col.label }}
              </th>
              <th v-if="$slots.actions" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr
              v-for="(row, idx) in rows"
              :key="rowKey ? (row as Record<string, unknown>)[rowKey] as string : idx"
              class="hover:bg-gray-50"
            >
              <td
                v-for="col in columns"
                :key="col.key"
                class="px-4 py-3 text-sm"
                :class="[
                  col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left',
                  col.mono ? 'font-mono' : '',
                  col.bold ? 'font-medium text-gray-900' : 'text-gray-600',
                ]"
              >
                <slot :name="`cell(${col.key})`" :row="row" :value="getCellValue(row, col.key)">
                  {{ formatCell(getCellValue(row, col.key), col) }}
                </slot>
              </td>
              <td v-if="$slots.actions" class="px-4 py-3 text-right">
                <slot name="actions" :row="row" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <AppPaginator
        v-if="paginate"
        :page="page"
        :last-page="lastPage"
        :per-page="perPage"
        :total="total"
        @prev="$emit('prev')"
        @next="$emit('next')"
        @go-to="(p) => $emit('go-to', p)"
      />
    </template>
  </div>
</template>

<script setup lang="ts" generic="T extends Record<string, unknown>">
import { ref } from 'vue';
import AppSpinner from '@/components/AppSpinner.vue';
import AppEmptyState from '@/components/AppEmptyState.vue';
import AppPaginator from '@/components/AppPaginator.vue';

export interface TableColumn {
  key: string;
  label: string;
  align?: 'left' | 'center' | 'right';
  mono?: boolean;
  bold?: boolean;
  type?: 'date' | 'currency' | 'badge' | 'boolean';
}

const _props = withDefaults(
  defineProps<{
    rows: T[];
    columns: TableColumn[];
    loading?: boolean;
    error?: string | null;
    rowKey?: string;
    searchable?: boolean;
    searchPlaceholder?: string;
    emptyIcon?: string;
    emptyTitle?: string;
    emptyMessage?: string;
    paginate?: boolean;
    page?: number;
    lastPage?: number;
    perPage?: number;
    total?: number;
  }>(),
  {
    loading: false,
    error: null,
    rowKey: 'id',
    searchable: false,
    searchPlaceholder: 'Search…',
    emptyIcon: '📋',
    emptyTitle: 'No records found',
    emptyMessage: undefined,
    paginate: false,
    page: 1,
    lastPage: 1,
    perPage: 15,
    total: 0,
  },
);

const emit = defineEmits<{
  search: [value: string];
  prev: [];
  next: [];
  'go-to': [page: number];
}>();

const search = ref('');
let searchDebounce: ReturnType<typeof setTimeout> | null = null;

function onSearchInput(event: Event): void {
  search.value = (event.target as HTMLInputElement).value;
  if (searchDebounce) clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => emit('search', search.value), 300);
}

function getCellValue(row: T, key: string): unknown {
  return key.split('.').reduce((obj: unknown, k) => {
    if (obj && typeof obj === 'object') return (obj as Record<string, unknown>)[k];
    return undefined;
  }, row as unknown);
}

function formatCell(value: unknown, col: TableColumn): string {
  if (value === null || value === undefined) return '—';
  if (col.type === 'date' && typeof value === 'string') return value.substring(0, 10);
  if (col.type === 'boolean') return value ? 'Yes' : 'No';
  return String(value);
}

// Expose search ref for parent access if needed
defineExpose({ search });
</script>
