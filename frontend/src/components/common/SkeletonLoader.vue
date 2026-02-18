<template>
  <div
    class="skeleton-loader"
    :class="[
      `skeleton-${type}`,
      { 'skeleton-animated': animated }
    ]"
    :style="computedStyle"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  type?: 'text' | 'circle' | 'rect' | 'card' | 'table' | 'avatar';
  width?: string | number;
  height?: string | number;
  animated?: boolean;
  rounded?: boolean;
  count?: number;
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  animated: true,
  rounded: false,
  count: 1,
});

const computedStyle = computed(() => {
  const style: Record<string, string> = {};
  
  // Type-specific defaults
  switch (props.type) {
    case 'text':
      style.height = props.height ? `${props.height}px` : '1rem';
      style.width = props.width ? `${props.width}px` : '100%';
      break;
    case 'circle':
    case 'avatar':
      const size = props.width || props.height || 40;
      style.width = `${size}px`;
      style.height = `${size}px`;
      style.borderRadius = '50%';
      break;
    case 'rect':
      style.height = props.height ? `${props.height}px` : '200px';
      style.width = props.width ? `${props.width}px` : '100%';
      if (props.rounded) {
        style.borderRadius = '0.5rem';
      }
      break;
    case 'card':
      style.height = props.height ? `${props.height}px` : '300px';
      style.width = props.width ? `${props.width}px` : '100%';
      style.borderRadius = '0.5rem';
      break;
    case 'table':
      style.height = props.height ? `${props.height}px` : '50px';
      style.width = props.width ? `${props.width}px` : '100%';
      break;
  }

  return style;
});
</script>

<style scoped>
.skeleton-loader {
  background: linear-gradient(
    90deg,
    #f0f0f0 0%,
    #f8f8f8 50%,
    #f0f0f0 100%
  );
  background-size: 200% 100%;
}

.skeleton-animated {
  animation: skeleton-loading 1.5s ease-in-out infinite;
}

@keyframes skeleton-loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

.skeleton-text {
  border-radius: 0.25rem;
  margin-bottom: 0.5rem;
}

.skeleton-rect {
  border-radius: 0.25rem;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .skeleton-loader {
    background: linear-gradient(
      90deg,
      #2d3748 0%,
      #4a5568 50%,
      #2d3748 100%
    );
  }
}
</style>
