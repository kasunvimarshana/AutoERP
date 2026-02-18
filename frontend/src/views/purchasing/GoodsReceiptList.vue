<template>
  <component :is="currentComponent" />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import GoodsReceiptListView from './GoodsReceiptListView.vue';
import GoodsReceiptForm from './GoodsReceiptForm.vue';
import GoodsReceiptDetail from './GoodsReceiptDetail.vue';

const route = useRoute();

const currentComponent = computed(() => {
  if (route.name === 'purchasing-goods-receipt-create' || route.name === 'purchasing-goods-receipt-edit') {
    return GoodsReceiptForm;
  }
  if (route.name === 'purchasing-goods-receipt-detail') {
    return GoodsReceiptDetail;
  }
  return GoodsReceiptListView;
});
</script>
