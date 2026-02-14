<template>
  <div>
    <div class="mb-6">
      <button @click="$router.back()" class="text-blue-600 hover:text-blue-800 mb-2">← Back</button>
      <h1 class="text-2xl font-bold text-gray-900">Invoice Details</h1>
    </div>

    <div v-if="loading" class="text-center py-8">Loading...</div>
    
    <div v-else>
      <BaseCard :title="`Invoice ${invoice.number}`">
        <template #actions>
          <div class="flex gap-2">
            <BaseButton @click="downloadPdf">Download PDF</BaseButton>
            <BaseButton @click="$router.push({ name: 'invoices-edit', params: { id: invoice.id } })">
              Edit
            </BaseButton>
          </div>
        </template>
        
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div><dt class="text-sm font-medium text-gray-500">Customer</dt><dd class="mt-1 text-sm text-gray-900">{{ invoice.customer_name }}</dd></div>
          <div><dt class="text-sm font-medium text-gray-500">Status</dt><dd class="mt-1 text-sm text-gray-900">{{ invoice.status }}</dd></div>
          <div><dt class="text-sm font-medium text-gray-500">Date</dt><dd class="mt-1 text-sm text-gray-900">{{ formatDate(invoice.date) }}</dd></div>
          <div><dt class="text-sm font-medium text-gray-500">Due Date</dt><dd class="mt-1 text-sm text-gray-900">{{ formatDate(invoice.due_date) }}</dd></div>
          <div><dt class="text-sm font-medium text-gray-500">Amount</dt><dd class="mt-1 text-sm text-gray-900">{{ formatCurrency(invoice.amount) }}</dd></div>
        </dl>
      </BaseCard>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api'
import { formatCurrency, formatDate } from '@/utils/formatters'
import BaseCard from '@components/common/BaseCard.vue'
import BaseButton from '@components/common/BaseButton.vue'

const route = useRoute()
const invoice = ref({})
const loading = ref(false)

const fetchInvoice = async () => {
  loading.value = true
  try {
    const response = await api.invoices.get(route.params.id)
    invoice.value = response.data
  } finally {
    loading.value = false
  }
}

const downloadPdf = async () => {
  try {
    const response = await api.invoices.downloadPdf(route.params.id)
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `invoice-${invoice.value.number}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error('Error downloading PDF:', error)
  }
}

onMounted(() => fetchInvoice())
</script>
