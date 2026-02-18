<template>
  <div class="flex flex-col gap-1">
    <label
      v-if="label"
      class="text-xs font-medium text-gray-700"
    >{{ label }}</label>
    <select
      :value="value"
      multiple
      size="3"
      class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
      @change="handleChange"
    >
      <option
        v-for="opt in options"
        :key="opt.value"
        :value="opt.value"
        :disabled="opt.disabled"
      >
        {{ opt.label }}
      </option>
    </select>
  </div>
</template>

<script setup lang="ts">
import type { FieldOptionMetadata } from '@/types/metadata';

interface FilterProps {
  label?: string;
  value: any;
  options?: FieldOptionMetadata[];
}

defineProps<FilterProps>();

const emit = defineEmits<{
  change: [value: any[]];
}>();

function handleChange(event: Event) {
  const target = event.target as HTMLSelectElement;
  const selectedValues = Array.from(target.selectedOptions).map(opt => opt.value);
  emit('change', selectedValues);
}
</script>
