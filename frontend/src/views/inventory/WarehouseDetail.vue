<template>
  <div class="warehouse-detail">
    <!-- Loading State -->
    <div
      v-if="loading"
      class="text-center py-12"
    >
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent" />
      <p class="mt-4 text-gray-600">
        Loading warehouse details...
      </p>
    </div>

    <!-- Error State -->
    <div
      v-else-if="error"
      class="rounded-md bg-red-50 p-4"
    >
      <div class="flex">
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">
            {{ error }}
          </h3>
        </div>
      </div>
    </div>

    <!-- Warehouse Details -->
    <div v-else-if="warehouse">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div class="flex-1">
          <div class="flex items-center space-x-4">
            <h1 class="text-2xl font-bold text-gray-900">
              {{ warehouse.name }}
            </h1>
            <span
              :class="warehouse.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
              class="rounded-full px-3 py-1 text-sm font-medium"
            >
              {{ warehouse.is_active ? 'Active' : 'Inactive' }}
            </span>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800">
              {{ warehouse.type.replace('_', ' ').toUpperCase() }}
            </span>
          </div>
          <p class="mt-1 text-sm text-gray-600">
            Warehouse Code: {{ warehouse.code }}
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            class="btn-secondary"
            @click="goBack"
          >
            <svg
              class="h-5 w-5 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10 19l-7-7m0 0l7-7m-7 7h18"
              />
            </svg>
            Back
          </button>
          <button
            class="btn-primary"
            @click="editWarehouse"
          >
            <svg
              class="h-5 w-5 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
              />
            </svg>
            Edit
          </button>
          <button
            class="btn-danger"
            @click="deleteWarehouse"
          >
            <svg
              class="h-5 w-5 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
              />
            </svg>
            Delete
          </button>
        </div>
      </div>

      <!-- Stock Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white shadow-sm rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
              <svg
                class="h-6 w-6 text-white"
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
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">
                Total Products
              </p>
              <p class="text-2xl font-semibold text-gray-900">
                {{ stockSummary?.total_products || 0 }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
              <svg
                class="h-6 w-6 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">
                Total Stock
              </p>
              <p class="text-2xl font-semibold text-gray-900">
                {{ stockSummary?.total_stock || 0 }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
              <svg
                class="h-6 w-6 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">
                Low Stock
              </p>
              <p class="text-2xl font-semibold text-gray-900">
                {{ stockSummary?.low_stock_products || 0 }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
              <svg
                class="h-6 w-6 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">
                Out of Stock
              </p>
              <p class="text-2xl font-semibold text-gray-900">
                {{ stockSummary?.out_of_stock_products || 0 }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Information -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Basic Information Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Basic Information
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Warehouse Name
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ warehouse.name }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Warehouse Code
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ warehouse.code }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Type
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ warehouse.type.replace('_', ' ') }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Capacity
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ warehouse.capacity || 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Status
                </dt>
                <dd class="mt-1">
                  <span
                    :class="warehouse.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                    class="inline-flex rounded-full px-2 text-xs font-semibold"
                  >
                    {{ warehouse.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </dd>
              </div>
            </dl>
          </div>

          <!-- Address Information Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Address Information
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500">
                  Address
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ warehouse.address || 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  City
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ warehouse.city || 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  State/Province
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ warehouse.state || 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Postal Code
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ warehouse.postal_code || 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Country
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ warehouse.country || 'N/A' }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Contact Information Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Contact Information
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Contact Person
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ warehouse.contact_person || 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Contact Phone
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ warehouse.contact_phone || 'N/A' }}
                </dd>
              </div>
              <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500">
                  Email
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  <a
                    v-if="warehouse.email"
                    :href="`mailto:${warehouse.email}`"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    {{ warehouse.email }}
                  </a>
                  <span v-else>N/A</span>
                </dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Right Column - Metadata -->
        <div class="space-y-6">
          <!-- Metadata Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Metadata
            </h2>
            <dl class="space-y-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Created At
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDate(warehouse.created_at) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Updated At
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDate(warehouse.updated_at) }}
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { inventoryApi } from '@/api/inventory'
import type { Warehouse, WarehouseStockSummary } from '@/types/inventory'

const route = useRoute()
const router = useRouter()

const warehouse = ref<Warehouse | null>(null)
const stockSummary = ref<WarehouseStockSummary | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

const fetchWarehouse = async () => {
  try {
    loading.value = true
    const id = route.params.id as string
    warehouse.value = await inventoryApi.getWarehouse(id)
    
    // Fetch stock summary
    try {
      stockSummary.value = await inventoryApi.getWarehouseStockSummary(id)
    } catch (err) {
      console.error('Failed to fetch stock summary:', err)
      // Don't fail the whole page if stock summary fails
    }
  } catch (err: any) {
    console.error('Failed to fetch warehouse:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load warehouse details'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'inventory-warehouses' })
}

const editWarehouse = () => {
  router.push({ name: 'inventory-warehouse-edit', params: { id: warehouse.value?.id } })
}

const deleteWarehouse = async () => {
  if (!warehouse.value) return
  
  if (!confirm(`Are you sure you want to delete warehouse "${warehouse.value.name}"? This action cannot be undone.`)) {
    return
  }

  try {
    await inventoryApi.deleteWarehouse(warehouse.value.id.toString())
    router.push({ name: 'inventory-warehouses' })
  } catch (err: any) {
    console.error('Failed to delete warehouse:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to delete warehouse'
  }
}

const formatDate = (dateString: string) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

onMounted(() => {
  fetchWarehouse()
})
</script>

<style scoped>
.btn-primary {
  @apply inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700;
}

.btn-secondary {
  @apply inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50;
}

.btn-danger {
  @apply inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700;
}
</style>
