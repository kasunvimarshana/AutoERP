<template>
  <DynamicCRUDView
    :id="recordId"
    module="purchasing"
    entity="purchase-orders"
    :mode="viewMode"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import DynamicCRUDView from '@/components/crud/DynamicCRUDView.vue';

const route = useRoute();

const viewMode = computed(() => {
  if (route.name === 'purchasing-order-create') return 'create';
  if (route.name === 'purchasing-order-edit') return 'edit';
  if (route.name === 'purchasing-order-detail') return 'view';
  return 'list';
});

const recordId = computed(() => route.params.id as string | undefined);
</script>
