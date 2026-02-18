<template>
  <component :is="currentComponent" />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import SupplierListView from './SupplierListView.vue';
import SupplierForm from './SupplierForm.vue';
import SupplierDetail from './SupplierDetail.vue';

const route = useRoute();

const currentComponent = computed(() => {
  if (route.name === 'purchasing-supplier-create' || route.name === 'purchasing-supplier-edit') {
    return SupplierForm;
  }
  if (route.name === 'purchasing-supplier-detail') {
    return SupplierDetail;
  }
  return SupplierListView;
});
</script>
