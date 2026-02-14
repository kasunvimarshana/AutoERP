<template>
  <div>
    <div class="mb-6 flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900">{{ $t('products.title') }}</h1>
      <BaseButton @click="$router.push({ name: 'products-create' })">
        {{ $t('products.create') }}
      </BaseButton>
    </div>

    <BaseCard>
      <BaseTable :columns="columns" :data="products" :loading="loading">
        <template #cell-price="{ value }">
          {{ formatCurrency(value) }}
        </template>
        
        <template #cell-stock="{ value }">
          <span :class="value < 10 ? 'text-red-600' : 'text-green-600'">
            {{ value }}
          </span>
        </template>
        
        <template #actions="{ row }">
          <div class="flex gap-2">
            <button @click="viewProduct(row.id)" class="text-blue-600 hover:text-blue-800">View</button>
            <button @click="editProduct(row.id)" class="text-green-600 hover:text-green-800">Edit</button>
            <button @click="deleteProduct(row.id)" class="text-red-600 hover:text-red-800">Delete</button>
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
import { formatCurrency } from '@/utils/formatters'
import BaseButton from '@components/common/BaseButton.vue'
import BaseCard from '@components/common/BaseCard.vue'
import BaseTable from '@components/common/BaseTable.vue'

const router = useRouter()
const products = ref([])
const loading = ref(false)

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'sku', label: 'SKU' },
  { key: 'category', label: 'Category' },
  { key: 'price', label: 'Price' },
  { key: 'stock', label: 'Stock' }
]

const fetchProducts = async () => {
  loading.value = true
  try {
    const response = await api.products.getAll()
    products.value = response.data.data || response.data
  } finally {
    loading.value = false
  }
}

const viewProduct = (id) => router.push({ name: 'products-view', params: { id } })
const editProduct = (id) => router.push({ name: 'products-edit', params: { id } })

const deleteProduct = async (id) => {
  if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) return
  try {
    await api.products.delete(id)
    fetchProducts()
  } catch (error) {
    console.error('Error:', error)
  }
}

onMounted(() => fetchProducts())
</script>
