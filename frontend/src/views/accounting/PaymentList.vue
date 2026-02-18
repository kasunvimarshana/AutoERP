<template>
  <DynamicCRUDView
    :id="recordId"
    module="accounting"
    entity="payments"
    :mode="viewMode"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import DynamicCRUDView from '@/components/crud/DynamicCRUDView.vue';

const route = useRoute();

const viewMode = computed(() => {
  if (route.name === 'accounting-payment-create') return 'create';
  if (route.name === 'accounting-payment-edit') return 'edit';
  if (route.name === 'accounting-payment-detail') return 'view';
  return 'list';
});

const recordId = computed(() => route.params.id as string | undefined);
</script>
