<template>
  <span
    :class="indicatorClass"
    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
  >
    {{ displayText }}
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

const booleanValue = computed(() => {
  if (typeof props.rawValue === 'boolean') {
    return props.rawValue;
  }
  
  if (typeof props.rawValue === 'string') {
    return props.rawValue.toLowerCase() === 'true' || props.rawValue === '1';
  }
  
  if (typeof props.rawValue === 'number') {
    return props.rawValue === 1;
  }
  
  return false;
});

const displayText = computed(() => {
  return booleanValue.value ? 'Yes' : 'No';
});

const indicatorClass = computed(() => {
  return booleanValue.value
    ? 'bg-green-100 text-green-800'
    : 'bg-gray-100 text-gray-800';
});
</script>
