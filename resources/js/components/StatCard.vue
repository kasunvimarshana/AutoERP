<template>
  <div
    class="bg-white rounded-lg shadow p-5 border-l-4"
    :class="`border-${color}-500`"
  >
    <p class="text-sm text-gray-500">{{ label }}</p>
    <p class="text-2xl font-bold text-gray-800 mt-1">{{ value }}</p>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  label: string;
  value: string | number;
  color?: string;
}>();
</script>
