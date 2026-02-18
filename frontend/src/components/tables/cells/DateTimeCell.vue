<template>
  <span class="text-gray-900">{{ formattedDateTime }}</span>
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

const formattedDateTime = computed(() => {
  if (!props.rawValue) {
    return '—';
  }

  try {
    const dateTimeObj = new Date(props.rawValue);
    
    if (isNaN(dateTimeObj.getTime())) {
      return String(props.rawValue);
    }

    if (props.descriptor.formatter) {
      return formatDateTimeWithPattern(dateTimeObj, props.descriptor.formatter);
    }

    return new Intl.DateTimeFormat('en-US', {
      dateStyle: 'short',
      timeStyle: 'short'
    }).format(dateTimeObj);
  } catch {
    return String(props.rawValue);
  }
});

function formatDateTimeWithPattern(dateTime: Date, pattern: string): string {
  const formatters: Record<string, Intl.DateTimeFormat> = {
    short: new Intl.DateTimeFormat('en-US', { dateStyle: 'short', timeStyle: 'short' }),
    medium: new Intl.DateTimeFormat('en-US', { dateStyle: 'medium', timeStyle: 'medium' }),
    long: new Intl.DateTimeFormat('en-US', { dateStyle: 'long', timeStyle: 'long' }),
    time: new Intl.DateTimeFormat('en-US', { timeStyle: 'short' }),
    full: new Intl.DateTimeFormat('en-US', { dateStyle: 'full', timeStyle: 'full' })
  };

  const formatter = formatters[pattern];
  return formatter ? formatter.format(dateTime) : dateTime.toLocaleString();
}
</script>
