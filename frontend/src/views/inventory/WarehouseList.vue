<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-gray-900">
        Warehouses
      </h1>
      <button
        class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition"
        @click="createWarehouse"
      >
        + Add Warehouse
      </button>
    </div>

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
      v-else
      class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
    >
      <div
        v-for="warehouse in warehouses"
        :key="warehouse.id"
        class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer"
        @click="viewWarehouse(warehouse.id)"
      >
        <div class="p-6">
          <div class="flex justify-between items-start mb-4">
            <div>
              <h3 class="text-xl font-semibold text-gray-900">
                {{ warehouse.name }}
              </h3>
              <p class="text-sm text-gray-600">
                {{ warehouse.code }}
              </p>
            </div>
            <span
              :class="warehouse.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
              class="px-2 py-1 rounded text-xs font-medium"
            >
              {{ warehouse.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>

          <div class="space-y-2 text-sm">
            <div class="flex items-center text-gray-600">
              <svg
                class="w-4 h-4 mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
              {{ warehouse.address || 'No address' }}
            </div>
            <div class="flex items-center text-gray-600">
              <svg
                class="w-4 h-4 mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                />
              </svg>
              {{ warehouse.stock_summary?.total_products || 0 }} Products
            </div>
            <div class="flex items-center text-gray-600">
              <svg
                class="w-4 h-4 mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                />
              </svg>
              {{ warehouse.stock_summary?.total_stock || 0 }} Items in Stock
            </div>
          </div>

          <div class="mt-4 pt-4 border-t border-gray-200">
            <button
              class="text-sm text-primary-600 hover:text-primary-700 font-medium"
              @click.stop="editWarehouse(warehouse.id)"
            >
              Edit Details
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { inventoryApi } from '@/api/inventory'
import type { Warehouse } from '@/types/inventory'

const router = useRouter()

const warehouses = ref<Warehouse[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

const fetchWarehouses = async () => {
  try {
    loading.value = true
    const response = await inventoryApi.getWarehouses()
    warehouses.value = response.data
  } catch (err: any) {
    error.value = err.message || 'Failed to load warehouses'
  } finally {
    loading.value = false
  }
}

const viewWarehouse = (id: number) => {
  router.push({ name: 'inventory-warehouse-detail', params: { id } })
}

const editWarehouse = (id: number) => {
  router.push({ name: 'inventory-warehouse-edit', params: { id } })
}

const createWarehouse = () => {
  router.push({ name: 'inventory-warehouse-create' })
}

onMounted(() => {
  fetchWarehouses()
})
</script>
