<template>
  <DynamicCRUDView
    :id="recordId"
    module="core"
    entity="audit-logs"
    :mode="viewMode"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import DynamicCRUDView from '@/components/crud/DynamicCRUDView.vue';

const route = useRoute();

// Audit logs are read-only, so only list and view modes
const viewMode = computed(() => {
  if (route.name === 'core-audit-log-detail') return 'view';
  return 'list';
});

const recordId = computed(() => route.params.id as string | undefined);
</script>
