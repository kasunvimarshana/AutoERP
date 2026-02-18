<template>
  <DynamicCRUDView
    module-id="iam"
    entity-id="roles"
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
  if (route.name === 'iam-role-create') return 'create';
  if (route.name === 'iam-role-edit') return 'edit';
  if (route.name === 'iam-role-detail') return 'view';
  return 'list';
});

const recordId = computed(() => route.params.id as string | undefined);
</script>
