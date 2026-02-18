<template>
  <span
    :class="badgeColorClass"
    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
  >
    {{ badgeText }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { TableColumnMetadata } from '@/types/metadata';

interface CellProps {
  descriptor: TableColumnMetadata;
  record: any;
  rawValue: any;
}

const props = defineProps<CellProps>();

const badgeText = computed(() => {
  if (props.rawValue === null || props.rawValue === undefined) {
    return '—';
  }
  
  return String(props.rawValue);
});

const badgeColorClass = computed(() => {
  const value = String(props.rawValue).toLowerCase();
  
  const colorMappings: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-600',
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-blue-100 text-blue-800',
    rejected: 'bg-red-100 text-red-800',
    draft: 'bg-purple-100 text-purple-800',
    published: 'bg-green-100 text-green-800',
    archived: 'bg-gray-100 text-gray-600',
    success: 'bg-green-100 text-green-800',
    warning: 'bg-yellow-100 text-yellow-800',
    error: 'bg-red-100 text-red-800',
    info: 'bg-blue-100 text-blue-800'
  };

  return colorMappings[value] || 'bg-gray-100 text-gray-800';
});
</script>
