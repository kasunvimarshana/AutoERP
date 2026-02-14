<template>
  <div>
    <div class="mb-6 flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900">{{ $t('invoices.title') }}</h1>
      <BaseButton @click="$router.push({ name: 'invoices-create' })">
        {{ $t('invoices.create') }}
      </BaseButton>
    </div>

    <BaseCard>
      <BaseTable :columns="columns" :data="invoices" :loading="loading">
        <template #cell-amount="{ value }">
          {{ formatCurrency(value) }}
        </template>
        
        <template #cell-status="{ value }">
          <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(value)">
            {{ value }}
          </span>
        </template>
        
        <template #cell-date="{ value }">
          {{ formatDate(value) }}
        </template>
        
        <template #actions="{ row }">
          <div class="flex gap-2">
            <button @click="viewInvoice(row.id)" class="text-blue-600 hover:text-blue-800">View</button>
            <button @click="editInvoice(row.id)" class="text-green-600 hover:text-green-800">Edit</button>
            <button @click="deleteInvoice(row.id)" class="text-red-600 hover:text-red-800">Delete</button>
          </div>
        </template>
      </BaseTable>
    </BaseCard>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api'
import { formatCurrency, formatDate } from '@/utils/formatters'
import BaseButton from '@components/common/BaseButton.vue'
import BaseCard from '@components/common/BaseCard.vue'
import BaseTable from '@components/common/BaseTable.vue'

const router = useRouter()
const invoices = ref([])
const loading = ref(false)

const columns = [
  { key: 'number', label: 'Invoice #' },
  { key: 'customer_name', label: 'Customer' },
  { key: 'date', label: 'Date' },
  { key: 'due_date', label: 'Due Date' },
  { key: 'amount', label: 'Amount' },
  { key: 'status', label: 'Status' }
]

const fetchInvoices = async () => {
  loading.value = true
  try {
    const response = await api.invoices.getAll()
    invoices.value = response.data.data || response.data
  } finally {
    loading.value = false
  }
}

const viewInvoice = (id) => router.push({ name: 'invoices-view', params: { id } })
const editInvoice = (id) => router.push({ name: 'invoices-edit', params: { id } })

const deleteInvoice = async (id) => {
  if (!confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) return
  try {
    await api.invoices.delete(id)
    fetchInvoices()
  } catch (error) {
    console.error('Error:', error)
  }
}

const getStatusClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    sent: 'bg-blue-100 text-blue-800',
    paid: 'bg-green-100 text-green-800',
    overdue: 'bg-red-100 text-red-800',
    void: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

onMounted(() => fetchInvoices())
</script>
