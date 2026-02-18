<template>
  <component :is="currentComponent" />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import SalesOrderListView from './SalesOrderListView.vue'
import SalesOrderForm from './SalesOrderForm.vue'
import SalesOrderDetail from './SalesOrderDetail.vue'

const route = useRoute()

const currentComponent = computed(() => {
  if (route.name === 'sales-order-create') return SalesOrderForm
  if (route.name === 'sales-order-edit') return SalesOrderForm
  if (route.name === 'sales-order-detail') return SalesOrderDetail
  return SalesOrderListView
})
</script>
