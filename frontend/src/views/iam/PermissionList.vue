<template>
  <DynamicCRUDView
    module-id="iam"
    entity-id="permissions"
    :record-id="recordId"
    :mode="viewMode"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import DynamicCRUDView from '@/components/crud/DynamicCRUDView.vue';

const route = useRoute();

const viewMode = computed(() => {
  if (route.name === 'iam-permission-create') return 'create';
  if (route.name === 'iam-permission-edit') return 'edit';
  if (route.name === 'iam-permission-detail') return 'view';
  return 'list';
});

const recordId = computed(() => route.params.id as string | undefined);
</script>
