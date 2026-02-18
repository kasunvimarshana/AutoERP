<template>
  <component :is="currentComponent" />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import QuotationListView from './QuotationListView.vue'
import QuotationForm from './QuotationForm.vue'
import QuotationDetail from './QuotationDetail.vue'

const route = useRoute()

const currentComponent = computed(() => {
  if (route.name === 'sales-quotation-create') return QuotationForm
  if (route.name === 'sales-quotation-edit') return QuotationForm
  if (route.name === 'sales-quotation-detail') return QuotationDetail
  return QuotationListView
})
</script>
