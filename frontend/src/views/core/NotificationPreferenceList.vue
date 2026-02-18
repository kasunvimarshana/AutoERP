<template>
  <DynamicCRUDView
    :id="recordId"
    module="core"
    entity="notification-preferences"
    :mode="viewMode"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import DynamicCRUDView from '@/components/crud/DynamicCRUDView.vue';

const route = useRoute();

const viewMode = computed(() => {
  if (route.name === 'core-notification-preference-create') return 'create';
  if (route.name === 'core-notification-preference-edit') return 'edit';
  if (route.name === 'core-notification-preference-detail') return 'view';
  return 'list';
});

const recordId = computed(() => route.params.id as string | undefined);
</script>
