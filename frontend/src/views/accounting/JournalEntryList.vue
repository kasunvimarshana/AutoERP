<template>
  <DynamicCRUDView
    :id="recordId"
    module="accounting"
    entity="journal-entries"
    :mode="viewMode"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import DynamicCRUDView from '@/components/crud/DynamicCRUDView.vue';

const route = useRoute();

const viewMode = computed(() => {
  if (route.name === 'accounting-journal-entry-create') return 'create';
  if (route.name === 'accounting-journal-entry-edit') return 'edit';
  if (route.name === 'accounting-journal-entry-detail') return 'view';
  return 'list';
});

const recordId = computed(() => route.params.id as string | undefined);
</script>
