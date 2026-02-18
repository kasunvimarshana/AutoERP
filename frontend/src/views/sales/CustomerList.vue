<template>
  <component :is="currentComponent" />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import CustomerListView from './CustomerListView.vue'
import CustomerForm from './CustomerForm.vue'
import CustomerDetail from './CustomerDetail.vue'

const route = useRoute()

const currentComponent = computed(() => {
  if (route.name === 'sales-customer-create') return CustomerForm
  if (route.name === 'sales-customer-edit') return CustomerForm
  if (route.name === 'sales-customer-detail') return CustomerDetail
  return CustomerListView
})
</script>
