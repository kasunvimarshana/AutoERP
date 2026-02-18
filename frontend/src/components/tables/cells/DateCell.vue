<template>
  <span class="text-gray-900">{{ formattedDate }}</span>
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

const formattedDate = computed(() => {
  if (!props.rawValue) {
    return '—';
  }

  try {
    const dateObj = new Date(props.rawValue);
    
    if (isNaN(dateObj.getTime())) {
      return String(props.rawValue);
    }

    if (props.descriptor.formatter) {
      return formatDateWithPattern(dateObj, props.descriptor.formatter);
    }

    return dateObj.toLocaleDateString();
  } catch {
    return String(props.rawValue);
  }
});

function formatDateWithPattern(date: Date, pattern: string): string {
  const formatters: Record<string, Intl.DateTimeFormat> = {
    short: new Intl.DateTimeFormat('en-US', { dateStyle: 'short' }),
    medium: new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }),
    long: new Intl.DateTimeFormat('en-US', { dateStyle: 'long' }),
    full: new Intl.DateTimeFormat('en-US', { dateStyle: 'full' })
  };

  const formatter = formatters[pattern];
  return formatter ? formatter.format(date) : date.toLocaleDateString();
}
</script>
