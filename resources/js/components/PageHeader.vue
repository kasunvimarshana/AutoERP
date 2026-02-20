<template>
  <div class="flex items-center justify-between mb-4">
    <div>
      <h2 class="text-xl font-bold text-gray-800">{{ title }}</h2>
      <p v-if="subtitle" class="text-sm text-gray-500 mt-0.5">{{ subtitle }}</p>
    </div>
    <div v-if="$slots.actions" class="flex items-center gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ title: string; subtitle?: string }>();
</script>
