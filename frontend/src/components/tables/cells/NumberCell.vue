<template>
  <span class="text-gray-900 font-mono">{{ formattedNumber }}</span>
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

const formattedNumber = computed(() => {
  if (props.rawValue === null || props.rawValue === undefined) {
    return '—';
  }

  const numValue = Number(props.rawValue);
  
  if (isNaN(numValue)) {
    return String(props.rawValue);
  }

  if (props.descriptor.formatter) {
    return formatWithType(numValue, props.descriptor.formatter);
  }

  return numValue.toLocaleString();
});

function formatWithType(num: number, formatterType: string): string {
  switch (formatterType) {
    case 'currency':
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
      }).format(num);
    case 'percentage':
      return `${(num * 100).toFixed(2)}%`;
    case 'decimal':
      return num.toFixed(2);
    case 'integer':
      return Math.round(num).toLocaleString();
    default:
      return num.toLocaleString();
  }
}
</script>
