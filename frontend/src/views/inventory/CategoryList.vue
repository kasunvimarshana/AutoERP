<template>
  <DynamicCRUDView
    :id="recordId"
    module="inventory"
    entity="categories"
    :mode="viewMode"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import DynamicCRUDView from '@/components/crud/DynamicCRUDView.vue';

const route = useRoute();

const viewMode = computed(() => {
  if (route.name === 'inventory-category-create') return 'create';
  if (route.name === 'inventory-category-edit') return 'edit';
  if (route.name === 'inventory-category-detail') return 'view';
  return 'list';
});

const recordId = computed(() => route.params.id as string | undefined);
</script>
