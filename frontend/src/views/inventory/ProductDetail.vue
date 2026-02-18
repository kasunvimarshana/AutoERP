<template>
  <div class="container mx-auto px-4 py-8">
    <div
      v-if="loading"
      class="flex justify-center items-center h-64"
    >
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600" />
    </div>

    <div
      v-else-if="error"
      class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded"
    >
      {{ error }}
    </div>

    <div
      v-else-if="product"
      class="space-y-6"
    >
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">
            {{ product.name }}
          </h1>
          <p class="text-gray-600 mt-1">
            SKU: {{ product.sku }}
          </p>
        </div>
        <div class="flex space-x-3">
          <button
            class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition"
            @click="$router.push(`/inventory/products/${product.id}/edit`)"
          >
            Edit Product
          </button>
          <button
            class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition"
            @click="deleteProduct"
          >
            Delete
          </button>
        </div>
      </div>

      <!-- Product Details Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info Card -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-md p-6">
          <h2 class="text-xl font-semibold mb-4">
            Product Information
          </h2>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-sm font-medium text-gray-600">Name</label>
              <p class="text-gray-900">
                {{ product.name }}
              </p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-600">SKU</label>
              <p class="text-gray-900">
                {{ product.sku }}
              </p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-600">Type</label>
              <p class="text-gray-900">
                {{ product.type }}
              </p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-600">Status</label>
              <span
                :class="statusClass(product.status)"
                class="inline-block px-2 py-1 rounded text-sm"
              >
                {{ product.status }}
              </span>
            </div>
            <div class="col-span-2">
              <label class="text-sm font-medium text-gray-600">Description</label>
              <p class="text-gray-900">
                {{ product.description || 'N/A' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Stock Summary Card -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-xl font-semibold mb-4">
            Stock Summary
          </h2>
          <div class="space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-gray-600">Available</span>
              <span class="text-xl font-bold text-green-600">{{ stockSummary.available || 0 }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600">Reserved</span>
              <span class="text-xl font-bold text-yellow-600">{{ stockSummary.reserved || 0 }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600">Total</span>
              <span class="text-xl font-bold text-blue-600">{{ stockSummary.total || 0 }}</span>
            </div>
          </div>
          <button
            class="w-full mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
            @click="showStockAdjustmentModal = true"
          >
            Adjust Stock
          </button>
        </div>
      </div>

      <!-- Pricing Information -->
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">
          Pricing
        </h2>
        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="text-sm font-medium text-gray-600">Cost Price</label>
            <p class="text-2xl font-bold text-gray-900">
              {{ formatCurrency(product.cost_price) }}
            </p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-600">Selling Price</label>
            <p class="text-2xl font-bold text-gray-900">
              {{ formatCurrency(product.selling_price) }}
            </p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-600">Profit Margin</label>
            <p class="text-2xl font-bold text-green-600">
              {{ product.profit_margin }}%
            </p>
          </div>
        </div>
      </div>

      <!-- Stock Movements -->
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">
          Recent Stock Movements
        </h2>
        <div
          v-if="movements.length === 0"
          class="text-gray-500 text-center py-8"
        >
          No stock movements recorded yet
        </div>
        <table
          v-else
          class="min-w-full divide-y divide-gray-200"
        >
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Date
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Type
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Quantity
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Warehouse
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Reference
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="movement in movements"
              :key="movement.id"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDate(movement.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ movement.transaction_type }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm"
                :class="movement.quantity >= 0 ? 'text-green-600' : 'text-red-600'"
              >
                {{ movement.quantity >= 0 ? '+' : '' }}{{ movement.quantity }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ movement.warehouse?.name || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ movement.reference || '-' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Stock Adjustment Modal -->
    <StockAdjustmentModal
      v-if="showStockAdjustmentModal"
      :product="product"
      @close="showStockAdjustmentModal = false"
      @adjusted="onStockAdjusted"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { inventoryApi } from '@/api/inventory'
import StockAdjustmentModal from '@/components/inventory/StockAdjustmentModal.vue'
import type { Product, StockLevel, StockMovement } from '@/types/inventory'

const route = useRoute()
const router = useRouter()

const product = ref<Product | null>(null)
const stockSummary = ref<StockLevel | null>(null)
const movements = ref<StockMovement[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const showStockAdjustmentModal = ref(false)

const statusClass = (status: string) => {
  const classes: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
    discontinued: 'bg-red-100 text-red-800',
    draft: 'bg-yellow-100 text-yellow-800',
  }
  return classes[status.toLowerCase()] || 'bg-gray-100 text-gray-800'
}

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value || 0)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const fetchProduct = async () => {
  try {
    loading.value = true
    const productId = route.params.id as string
    product.value = await inventoryApi.getProduct(productId)
    
    // Fetch stock summary
    stockSummary.value = await inventoryApi.getStockLevel(productId)
    
    // Fetch recent movements
    const movementsResponse = await inventoryApi.getStockMovements(productId, { limit: 10 })
    movements.value = movementsResponse
  } catch (err: any) {
    error.value = err.message || 'Failed to load product details'
  } finally {
    loading.value = false
  }
}

const deleteProduct = async () => {
  if (!confirm('Are you sure you want to delete this product?')) return
  
  try {
    await inventoryApi.deleteProduct(product.value.id)
    router.push('/inventory/products')
  } catch (err: any) {
    error.value = err.message || 'Failed to delete product'
  }
}

const onStockAdjusted = () => {
  showStockAdjustmentModal.value = false
  fetchProduct() // Refresh data
}

onMounted(() => {
  fetchProduct()
})
</script>
