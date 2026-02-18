<template>
  <DynamicCRUDView
    :id="recordId"
    module="core"
    entity="tenants"
    :mode="viewMode"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import DynamicCRUDView from '@/components/crud/DynamicCRUDView.vue';

const route = useRoute();

const viewMode = computed(() => {
  if (route.name === 'core-tenant-create') return 'create';
  if (route.name === 'core-tenant-edit') return 'edit';
  if (route.name === 'core-tenant-detail') return 'view';
  return 'list';
});

const recordId = computed(() => route.params.id as string | undefined);
</script>
