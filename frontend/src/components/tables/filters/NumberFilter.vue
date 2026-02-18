<template>
  <div class="flex flex-col gap-1">
    <label
      v-if="label"
      class="text-xs font-medium text-gray-700"
    >{{ label }}</label>
    <input
      :value="value"
      type="number"
      placeholder="Enter number..."
      class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
      @input="handleInput"
    >
  </div>
</template>

<script setup lang="ts">
interface FilterProps {
  label?: string;
  value: any;
}

defineProps<FilterProps>();

const emit = defineEmits<{
  change: [value: number | null];
}>();

function handleInput(event: Event) {
  const target = event.target as HTMLInputElement;
  const numValue = target.value ? parseFloat(target.value) : null;
  emit('change', numValue);
}
</script>
