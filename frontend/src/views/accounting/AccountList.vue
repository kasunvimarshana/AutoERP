<template>
  <DynamicCRUDView
    :id="recordId"
    module="accounting"
    entity="accounts"
    :mode="viewMode"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import DynamicCRUDView from '@/components/crud/DynamicCRUDView.vue';

const route = useRoute();

const viewMode = computed(() => {
  if (route.name === 'accounting-account-create') return 'create';
  if (route.name === 'accounting-account-edit') return 'edit';
  if (route.name === 'accounting-account-detail') return 'view';
  return 'list';
});

const recordId = computed(() => route.params.id as string | undefined);
</script>
