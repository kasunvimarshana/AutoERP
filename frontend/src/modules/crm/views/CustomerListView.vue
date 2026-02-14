<template>
  <div>
    <div class="mb-6 flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900">{{ $t('customers.title') }}</h1>
      <BaseButton @click="$router.push({ name: 'customers-create' })">
        {{ $t('customers.create') }}
      </BaseButton>
    </div>

    <BaseCard>
      <div class="mb-4">
        <BaseInput
          v-model="search"
          type="text"
          :placeholder="$t('common.search')"
          @input="fetchCustomers"
        />
      </div>

      <BaseTable
        :columns="columns"
        :data="customers"
        :loading="loading"
      >
        <template #cell-type="{ value }">
          <span class="px-2 py-1 text-xs rounded-full" :class="getTypeClass(value)">
            {{ value }}
          </span>
        </template>
        
        <template #cell-status="{ value }">
          <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(value)">
            {{ value }}
          </span>
        </template>
        
        <template #actions="{ row }">
          <div class="flex gap-2">
            <button @click="viewCustomer(row.id)" class="text-blue-600 hover:text-blue-800">
              {{ $t('common.view') }}
            </button>
            <button @click="editCustomer(row.id)" class="text-green-600 hover:text-green-800">
              {{ $t('common.edit') }}
            </button>
            <button @click="deleteCustomer(row.id)" class="text-red-600 hover:text-red-800">
              {{ $t('common.delete') }}
            </button>
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
import BaseButton from '@components/common/BaseButton.vue'
import BaseCard from '@components/common/BaseCard.vue'
import BaseTable from '@components/common/BaseTable.vue'
import BaseInput from '@components/common/BaseInput.vue'
import { debounce } from '@/utils/formatters'

const router = useRouter()
const customers = ref([])
const loading = ref(false)
const search = ref('')

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'email', label: 'Email' },
  { key: 'phone', label: 'Phone' },
  { key: 'type', label: 'Type' },
  { key: 'status', label: 'Status' }
]

const fetchCustomers = debounce(async () => {
  loading.value = true
  try {
    const response = await api.customers.getAll({ search: search.value })
    customers.value = response.data.data || response.data
  } catch (error) {
    console.error('Error fetching customers:', error)
  } finally {
    loading.value = false
  }
}, 300)

const viewCustomer = (id) => {
  router.push({ name: 'customers-view', params: { id } })
}

const editCustomer = (id) => {
  router.push({ name: 'customers-edit', params: { id } })
}

const deleteCustomer = async (id) => {
  if (!confirm('Are you sure you want to delete this customer?')) return
  
  try {
    await api.customers.delete(id)
    fetchCustomers()
  } catch (error) {
    console.error('Error deleting customer:', error)
  }
}

const getTypeClass = (type) => {
  const classes = {
    individual: 'bg-blue-100 text-blue-800',
    business: 'bg-purple-100 text-purple-800'
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

onMounted(() => {
  fetchCustomers()
})
</script>
