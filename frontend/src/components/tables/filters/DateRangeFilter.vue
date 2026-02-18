<template>
  <div class="flex flex-col gap-1">
    <label
      v-if="label"
      class="text-xs font-medium text-gray-700"
    >{{ label }}</label>
    <div class="flex gap-2">
      <input
        :value="rangeStart"
        type="date"
        placeholder="From"
        class="flex-1 px-2 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        @input="handleStartChange"
      >
      <input
        :value="rangeEnd"
        type="date"
        placeholder="To"
        class="flex-1 px-2 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        @input="handleEndChange"
      >
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface FilterProps {
  label?: string;
  value: any;
}

const props = defineProps<FilterProps>();

const emit = defineEmits<{
  change: [value: { start: string; end: string }];
}>();

const rangeStart = computed(() => props.value?.start || '');
const rangeEnd = computed(() => props.value?.end || '');

function handleStartChange(event: Event) {
  const target = event.target as HTMLInputElement;
  emit('change', { start: target.value, end: rangeEnd.value });
}

function handleEndChange(event: Event) {
  const target = event.target as HTMLInputElement;
  emit('change', { start: rangeStart.value, end: target.value });
}
</script>
