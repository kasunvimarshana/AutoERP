<template>
  <div>
    <div class="mb-6">
      <button @click="$router.back()" class="text-blue-600 hover:text-blue-800 mb-2">
        ← {{ $t('common.back') }}
      </button>
      <h1 class="text-2xl font-bold text-gray-900">{{ $t('customers.view') }}</h1>
    </div>

    <div v-if="loading" class="text-center py-8">Loading...</div>
    
    <div v-else class="space-y-6">
      <BaseCard :title="customer.name">
        <template #actions>
          <BaseButton @click="$router.push({ name: 'customers-edit', params: { id: customer.id } })">
            {{ $t('common.edit') }}
          </BaseButton>
        </template>
        
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <dt class="text-sm font-medium text-gray-500">Email</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ customer.email }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Phone</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ customer.phone || 'N/A' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Type</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ customer.type }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Status</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ customer.status }}</dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-sm font-medium text-gray-500">Address</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ customer.address || 'N/A' }}</dd>
          </div>
        </dl>
      </BaseCard>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api'
import BaseCard from '@components/common/BaseCard.vue'
import BaseButton from '@components/common/BaseButton.vue'

const route = useRoute()
const customer = ref({})
const loading = ref(false)

const fetchCustomer = async () => {
  loading.value = true
  try {
    const response = await api.customers.get(route.params.id)
    customer.value = response.data
  } catch (error) {
    console.error('Error fetching customer:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchCustomer()
})
</script>
