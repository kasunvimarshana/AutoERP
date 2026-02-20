<template>
  <div class="animate-pulse space-y-3" :aria-label="label" aria-busy="true">
    <!-- Table skeleton -->
    <template v-if="variant === 'table'">
      <div class="h-8 bg-gray-200 rounded w-full" />
      <div v-for="n in rows" :key="n" class="h-12 bg-gray-100 rounded w-full" />
    </template>

    <!-- Card skeleton -->
    <template v-else-if="variant === 'card'">
      <div class="h-5 bg-gray-200 rounded w-1/3" />
      <div class="h-4 bg-gray-100 rounded w-2/3" />
      <div class="h-4 bg-gray-100 rounded w-1/2" />
    </template>

    <!-- Form skeleton -->
    <template v-else-if="variant === 'form'">
      <div v-for="n in rows" :key="n" class="space-y-1.5">
        <div class="h-4 bg-gray-200 rounded w-24" />
        <div class="h-9 bg-gray-100 rounded w-full" />
      </div>
    </template>

    <!-- Generic / text -->
    <template v-else>
      <div
        v-for="n in rows"
        :key="n"
        class="h-4 bg-gray-200 rounded"
        :style="{ width: `${80 - (n % 3) * 15}%` }"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    variant?: 'table' | 'card' | 'form' | 'text';
    rows?: number;
    label?: string;
  }>(),
  {
    variant: 'text',
    rows: 5,
    label: 'Loading…',
  },
);
</script>
