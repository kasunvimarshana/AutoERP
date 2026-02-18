<template>
  <div class="flex flex-col gap-1">
    <label
      v-if="label"
      class="text-xs font-medium text-gray-700"
    >{{ label }}</label>
    <input
      :value="value"
      type="date"
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
  change: [value: string];
}>();

function handleInput(event: Event) {
  const target = event.target as HTMLInputElement;
  emit('change', target.value);
}
</script>
