<template>
  <div>
    <div class="mb-6">
      <button @click="$router.back()" class="text-blue-600 hover:text-blue-800 mb-2">← Back</button>
      <h1 class="text-2xl font-bold text-gray-900">Product Details</h1>
    </div>

    <div v-if="loading" class="text-center py-8">Loading...</div>
    
    <div v-else>
      <BaseCard :title="product.name">
        <template #actions>
          <BaseButton @click="$router.push({ name: 'products-edit', params: { id: product.id } })">
            Edit
          </BaseButton>
        </template>
        
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div><dt class="text-sm font-medium text-gray-500">SKU</dt><dd class="mt-1 text-sm text-gray-900">{{ product.sku }}</dd></div>
          <div><dt class="text-sm font-medium text-gray-500">Category</dt><dd class="mt-1 text-sm text-gray-900">{{ product.category || 'N/A' }}</dd></div>
          <div><dt class="text-sm font-medium text-gray-500">Price</dt><dd class="mt-1 text-sm text-gray-900">{{ formatCurrency(product.price) }}</dd></div>
          <div><dt class="text-sm font-medium text-gray-500">Cost</dt><dd class="mt-1 text-sm text-gray-900">{{ formatCurrency(product.cost) }}</dd></div>
          <div><dt class="text-sm font-medium text-gray-500">Stock</dt><dd class="mt-1 text-sm text-gray-900">{{ product.stock }}</dd></div>
          <div class="sm:col-span-2"><dt class="text-sm font-medium text-gray-500">Description</dt><dd class="mt-1 text-sm text-gray-900">{{ product.description || 'N/A' }}</dd></div>
        </dl>
      </BaseCard>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api'
import { formatCurrency } from '@/utils/formatters'
import BaseCard from '@components/common/BaseCard.vue'
import BaseButton from '@components/common/BaseButton.vue'

const route = useRoute()
const product = ref({})
const loading = ref(false)

const fetchProduct = async () => {
  loading.value = true
  try {
    const response = await api.products.get(route.params.id)
    product.value = response.data
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchProduct())
</script>
