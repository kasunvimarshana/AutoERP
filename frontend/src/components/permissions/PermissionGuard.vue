<template>
  <component
    :is="tag"
    v-if="hasRequiredPermissions"
  >
    <slot />
  </component>
  <slot
    v-else
    name="fallback"
  >
    <div
      v-if="showFallback"
      class="permission-denied"
    >
      <p class="text-sm text-gray-500">
        You don't have permission to access this feature.
      </p>
    </div>
  </slot>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useMetadataStore } from '@/stores/metadata';

interface Props {
  permissions: string | string[];
  requireAll?: boolean;
  tag?: string;
  showFallback?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  requireAll: false,
  tag: 'div',
  showFallback: false,
});

const metadataStore = useMetadataStore();

const hasRequiredPermissions = computed(() => {
  const perms = Array.isArray(props.permissions) ? props.permissions : [props.permissions];
  
  return props.requireAll
    ? metadataStore.hasAllPermissions(perms)
    : metadataStore.hasAnyPermission(perms);
});
</script>
