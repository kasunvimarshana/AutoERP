<template>
  <div class="stock-movements">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Stock Movements
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          View and track all inventory stock transactions
        </p>
      </div>
      <button
        class="btn-primary flex items-center space-x-2"
        @click="showTransactionModal = true"
      >
        <svg
          class="h-5 w-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v16m8-8H4"
          />
        </svg>
        <span>Record Transaction</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-5">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search by product or reference..."
        class="input"
        @input="debouncedSearch"
      >
      <select
        v-model="filters.warehouse_id"
        class="input"
        @change="fetchMovements"
      >
        <option value="">
          All Warehouses
        </option>
        <option
          v-for="warehouse in warehouses"
          :key="warehouse.id"
          :value="warehouse.id"
        >
          {{ warehouse.name }}
        </option>
      </select>
      <select
        v-model="filters.transaction_type"
        class="input"
        @change="fetchMovements"
      >
        <option value="">
          All Transaction Types
        </option>
        <option value="PURCHASE">
          Purchase
        </option>
        <option value="SALE">
          Sale
        </option>
        <option value="ADJUSTMENT">
          Adjustment
        </option>
        <option value="TRANSFER_IN">
          Transfer In
        </option>
        <option value="TRANSFER_OUT">
          Transfer Out
        </option>
        <option value="RETURN_IN">
          Return In
        </option>
        <option value="RETURN_OUT">
          Return Out
        </option>
        <option value="DAMAGE">
          Damage
        </option>
      </select>
      <input
        v-model="filters.from_date"
        type="date"
        class="input"
        @change="fetchMovements"
      >
      <button
        class="btn-secondary"
        @click="clearFilters"
      >
        Clear Filters
      </button>
    </div>

    <!-- Error State -->
    <div
      v-if="error"
      class="mb-4 rounded-md bg-red-50 p-4"
    >
      <div class="flex">
        <div class="flex-shrink-0">
          <svg
            class="h-5 w-5 text-red-400"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path
              fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
              clip-rule="evenodd"
            />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">
            {{ error }}
          </h3>
        </div>
        <div class="ml-auto pl-3">
          <button
            class="inline-flex text-red-400 hover:text-red-600"
            @click="error = null"
          >
            <span class="sr-only">Dismiss</span>
            <svg
              class="h-5 w-5"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clip-rule="evenodd"
              />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div
      v-if="loading"
      class="text-center py-8"
    >
      <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent" />
      <p class="mt-2 text-gray-600">
        Loading stock movements...
      </p>
    </div>

    <!-- Movements Table -->
    <div
      v-else-if="movements.length > 0"
      class="overflow-hidden rounded-lg bg-white shadow"
    >
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Date
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Product
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Warehouse
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Type
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">
              Quantity
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">
              Balance After
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Reference
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Notes
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr
            v-for="movement in movements"
            :key="movement.id"
            class="hover:bg-gray-50"
          >
            <td class="px-6 py-4 text-sm text-gray-900">
              {{ formatDate(movement.created_at) }}
            </td>
            <td class="px-6 py-4 text-sm font-medium text-gray-900">
              Product #{{ movement.product_id }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ movement.warehouse?.name || `Warehouse #${movement.warehouse_id}` }}
            </td>
            <td class="px-6 py-4 text-sm">
              <span
                :class="getTransactionTypeClass(movement.transaction_type)"
                class="rounded-full px-2 py-1 text-xs font-medium"
              >
                {{ formatTransactionType(movement.transaction_type) }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-right">
              <span :class="getQuantityClass(movement.quantity)">
                {{ movement.quantity > 0 ? '+' : '' }}{{ movement.quantity }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-right text-gray-900">
              {{ movement.balance_after }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ movement.reference || '-' }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ movement.notes || '-' }}
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div
        v-if="pagination.total > pagination.per_page"
        class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6"
      >
        <div class="flex-1 flex justify-between sm:hidden">
          <button
            :disabled="pagination.current_page === 1"
            class="btn-secondary"
            @click="changePage(pagination.current_page - 1)"
          >
            Previous
          </button>
          <button
            :disabled="pagination.current_page === pagination.last_page"
            class="btn-secondary ml-3"
            @click="changePage(pagination.current_page + 1)"
          >
            Next
          </button>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Showing
              <span class="font-medium">{{ pagination.from }}</span>
              to
              <span class="font-medium">{{ pagination.to }}</span>
              of
              <span class="font-medium">{{ pagination.total }}</span>
              results
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
              <button
                :disabled="pagination.current_page === 1"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                @click="changePage(pagination.current_page - 1)"
              >
                <span class="sr-only">Previous</span>
                <svg
                  class="h-5 w-5"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                    clip-rule="evenodd"
                  />
                </svg>
              </button>
              <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                Page {{ pagination.current_page }} of {{ pagination.last_page }}
              </span>
              <button
                :disabled="pagination.current_page === pagination.last_page"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                @click="changePage(pagination.current_page + 1)"
              >
                <span class="sr-only">Next</span>
                <svg
                  class="h-5 w-5"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                    clip-rule="evenodd"
                  />
                </svg>
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-else
      class="text-center py-12"
    >
      <svg
        class="mx-auto h-12 w-12 text-gray-400"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
        />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        No stock movements found
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        No stock transactions match your current filters.
      </p>
    </div>

    <!-- Transaction Modal -->
    <StockTransactionModal
      v-if="showTransactionModal"
      @close="showTransactionModal = false"
      @saved="onTransactionSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { inventoryApi } from '@/api/inventory'
import type { StockMovement, Warehouse, PaginationMeta } from '@/types/inventory'
import StockTransactionModal from '@/components/inventory/StockTransactionModal.vue'

const loading = ref(false)
const movements = ref<StockMovement[]>([])
const warehouses = ref<Warehouse[]>([])
const error = ref<string | null>(null)
const showTransactionModal = ref(false)

const filters = reactive({
  search: '',
  warehouse_id: '',
  transaction_type: '',
  from_date: '',
})

const pagination = reactive<PaginationMeta>({
  current_page: 1,
  from: 0,
  last_page: 1,
  per_page: 15,
  to: 0,
  total: 0,
})

let searchTimeout: ReturnType<typeof setTimeout> | null = null

const debouncedSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchMovements()
  }, 300)
}

const fetchMovements = async () => {
  loading.value = true
  error.value = null
  try {
    // TODO: Backend needs an endpoint to list all stock movements across all products.
    // Current API requires a product_id: /inventory/stock/{productId}/movements
    // For now, this view shows the UI structure but needs backend support.
    // Possible solutions:
    // 1. Add a new endpoint: GET /inventory/stock/movements (with product_id as optional filter)
    // 2. Or query movements for multiple products and aggregate them
    
    movements.value = []
    
    console.warn('Stock movements list endpoint needs to be implemented in backend')
  } catch (err: any) {
    console.error('Failed to fetch stock movements:', err)
    error.value = err.message || 'Failed to fetch stock movements. Please try again.'
    movements.value = []
  } finally {
    loading.value = false
  }
}

const fetchWarehouses = async () => {
  try {
    const response = await inventoryApi.getWarehouses()
    warehouses.value = response.data || []
  } catch (err) {
    console.error('Failed to fetch warehouses:', err)
  }
}

const clearFilters = () => {
  filters.search = ''
  filters.warehouse_id = ''
  filters.transaction_type = ''
  filters.from_date = ''
  fetchMovements()
}

const changePage = (page: number) => {
  pagination.current_page = page
  fetchMovements()
}

const formatDate = (dateString: string) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatTransactionType = (type: string) => {
  return type.replace(/_/g, ' ')
}

const getTransactionTypeClass = (type: string) => {
  const classes: Record<string, string> = {
    PURCHASE: 'bg-green-100 text-green-800',
    SALE: 'bg-blue-100 text-blue-800',
    ADJUSTMENT: 'bg-yellow-100 text-yellow-800',
    TRANSFER_IN: 'bg-indigo-100 text-indigo-800',
    TRANSFER_OUT: 'bg-purple-100 text-purple-800',
    RETURN_IN: 'bg-teal-100 text-teal-800',
    RETURN_OUT: 'bg-orange-100 text-orange-800',
    DAMAGE: 'bg-red-100 text-red-800',
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

const getQuantityClass = (quantity: number) => {
  if (quantity > 0) return 'text-green-600 font-semibold'
  if (quantity < 0) return 'text-red-600 font-semibold'
  return 'text-gray-600'
}

const onTransactionSaved = () => {
  showTransactionModal.value = false
  fetchMovements()
}

onMounted(() => {
  fetchWarehouses()
  fetchMovements()
})
</script>

<style scoped>
.input {
  @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm;
}

.btn-primary {
  @apply inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700;
}

.btn-secondary {
  @apply inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed;
}
</style>
