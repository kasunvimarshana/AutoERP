<template>
  <div class="flex flex-col items-center justify-center py-16 text-center">
    <div class="text-5xl mb-4">{{ icon }}</div>
    <p class="text-lg font-semibold text-gray-700">{{ title }}</p>
    <p v-if="message" class="mt-1 text-sm text-gray-500">{{ message }}</p>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{ icon?: string; title: string; message?: string }>(),
  { icon: '📭' },
);
</script>
