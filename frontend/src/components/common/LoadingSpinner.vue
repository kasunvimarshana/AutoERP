<template>
  <div
    v-if="loading"
    class="flex items-center justify-center p-8"
  >
    <div :class="['spinner', sizeClass]" />
    <span
      v-if="text"
      class="ml-3 text-gray-600"
    >{{ text }}</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  loading?: boolean;
  size?: 'sm' | 'md' | 'lg';
  text?: string;
}

const props = withDefaults(defineProps<Props>(), {
  loading: true,
  size: 'md',
});

const sizeClass = computed(() => {
  const sizes: Record<string, string> = {
    sm: 'h-4 w-4',
    md: 'h-8 w-8',
    lg: 'h-12 w-12',
  };
  return sizes[props.size];
});
</script>
