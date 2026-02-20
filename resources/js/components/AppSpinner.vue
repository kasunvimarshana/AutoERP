<template>
  <div
    :class="sizeClass"
    class="inline-block animate-spin rounded-full border-2 border-solid border-current border-r-transparent align-[-0.125em] motion-reduce:animate-[spin_1.5s_linear_infinite]"
    role="status"
  >
    <span class="sr-only">Loading…</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{ size?: 'sm' | 'md' | 'lg' }>(),
  { size: 'md' },
);

const sizeClass = computed(() => ({
  sm: 'h-4 w-4',
  md: 'h-6 w-6',
  lg: 'h-10 w-10',
}[props.size]));
</script>
