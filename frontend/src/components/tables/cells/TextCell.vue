<template>
  <span class="text-gray-900">{{ displayValue }}</span>
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

const displayValue = computed(() => {
  if (props.rawValue === null || props.rawValue === undefined) {
    return '—';
  }

  if (props.descriptor.formatter) {
    return applyCustomFormatter(props.rawValue, props.descriptor.formatter);
  }

  return String(props.rawValue);
});

function applyCustomFormatter(value: any, formatterType: string): string {
  switch (formatterType) {
    case 'uppercase':
      return String(value).toUpperCase();
    case 'lowercase':
      return String(value).toLowerCase();
    case 'capitalize':
      return String(value).charAt(0).toUpperCase() + String(value).slice(1).toLowerCase();
    case 'truncate':
      return String(value).length > 50 ? String(value).substring(0, 50) + '...' : String(value);
    default:
      return String(value);
  }
}
</script>
