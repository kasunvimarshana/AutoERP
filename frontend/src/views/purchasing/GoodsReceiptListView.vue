<template>
  <div class="goods-receipt-list">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Goods Receipts
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          Manage incoming goods and track receipt status
        </p>
      </div>
      <button
        class="btn-primary flex items-center space-x-2"
        @click="navigateToCreate"
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
        <span>Create Goods Receipt</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-5">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search receipts..."
        class="input"
        @input="debouncedSearch"
      >
      <select
        v-model="filters.status"
        class="input"
        @change="fetchReceipts"
      >
        <option value="">
          All Statuses
        </option>
        <option value="pending">
          Pending
        </option>
        <option value="inspected">
          Inspected
        </option>
        <option value="accepted">
          Accepted
        </option>
        <option value="rejected">
          Rejected
        </option>
      </select>
      <select
        v-model="filters.purchase_order_id"
        class="input"
        @change="fetchReceipts"
      >
        <option value="">
          All Purchase Orders
        </option>
        <option
          v-for="po in purchaseOrders"
          :key="po.id"
          :value="po.id"
        >
          {{ po.po_number }}
        </option>
      </select>
      <select
        v-model="filters.warehouse_id"
        class="input"
        @change="fetchReceipts"
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
      <button
        class="btn-secondary"
        @click="clearFilters"
      >
        Clear Filters
      </button>
    </div>

    <!-- Date Range Filters -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
      <input
        v-model="filters.from_date"
        type="date"
        class="input"
        placeholder="From date"
        @change="fetchReceipts"
      >
      <input
        v-model="filters.to_date"
        type="date"
        class="input"
        placeholder="To date"
        @change="fetchReceipts"
      >
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
        Loading goods receipts...
      </p>
    </div>

    <!-- Receipts Table -->
    <div
      v-else-if="receipts.length > 0"
      class="overflow-hidden rounded-lg bg-white shadow"
    >
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Receipt Number
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              PO Number
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Receipt Date
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Warehouse
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Status
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr
            v-for="receipt in receipts"
            :key="receipt.id"
            class="hover:bg-gray-50"
          >
            <td class="px-6 py-4 text-sm font-medium text-gray-900">
              {{ receipt.receipt_number }}
            </td>
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">
                {{ receipt.po_number || 'N/A' }}
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ formatDate(receipt.receipt_date) }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ receipt.warehouse_name || 'N/A' }}
            </td>
            <td class="px-6 py-4 text-sm">
              <span
                :class="getStatusClass(receipt.status)"
                class="rounded-full px-2 py-1 text-xs font-medium"
              >
                {{ formatStatus(receipt.status) }}
              </span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button
                class="text-blue-600 hover:text-blue-900"
                @click="viewReceipt(receipt.id)"
              >
                View
              </button>
              <button
                v-if="receipt.status === 'pending'"
                class="text-green-600 hover:text-green-900"
                @click="inspectReceipt(receipt.id)"
              >
                Inspect
              </button>
              <button
                v-if="receipt.status === 'inspected'"
                class="text-green-600 hover:text-green-900"
                @click="acceptReceipt(receipt.id)"
              >
                Accept
              </button>
              <button
                v-if="receipt.status === 'inspected'"
                class="text-red-600 hover:text-red-900"
                @click="rejectReceipt(receipt.id)"
              >
                Reject
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div
        v-if="pagination"
        class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6"
      >
        <div class="flex-1 flex justify-between sm:hidden">
          <button
            :disabled="!pagination.prev_page_url"
            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            @click="goToPage(pagination.current_page - 1)"
          >
            Previous
          </button>
          <button
            :disabled="!pagination.next_page_url"
            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            @click="goToPage(pagination.current_page + 1)"
          >
            Next
          </button>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Showing
              <span class="font-medium">{{ pagination.from || 0 }}</span>
              to
              <span class="font-medium">{{ pagination.to || 0 }}</span>
              of
              <span class="font-medium">{{ pagination.total || 0 }}</span>
              results
            </p>
          </div>
          <div>
            <nav
              class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
              aria-label="Pagination"
            >
              <button
                :disabled="!pagination.prev_page_url"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                @click="goToPage(pagination.current_page - 1)"
              >
                Previous
              </button>
              <button
                :disabled="!pagination.next_page_url"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                @click="goToPage(pagination.current_page + 1)"
              >
                Next
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
          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
        />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        No goods receipts found
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        Get started by creating a new goods receipt.
      </p>
      <div class="mt-6">
        <button
          class="btn-primary"
          @click="navigateToCreate"
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
              d="M12 4v16m8-8H4"
            />
          </svg>
          Create Goods Receipt
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { purchasingApi } from '@/api/purchasing'
import { inventoryApi } from '@/api/inventory'
import type { GoodsReceipt, PurchaseOrder } from '@/api/purchasing'
import type { Warehouse } from '@/types/inventory'

const router = useRouter()
const loading = ref(false)
const receipts = ref<GoodsReceipt[]>([])
const error = ref<string | null>(null)
const pagination = ref<any>(null)
const purchaseOrders = ref<PurchaseOrder[]>([])
const warehouses = ref<Warehouse[]>([])

const filters = reactive({
  search: '',
  status: '',
  purchase_order_id: '',
  warehouse_id: '',
  from_date: '',
  to_date: '',
  page: 1,
})

let searchTimeout: ReturnType<typeof setTimeout> | null = null

const debouncedSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    filters.page = 1
    fetchReceipts()
  }, 300)
}

const fetchReceipts = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await purchasingApi.getGoodsReceipts({
      search: filters.search || undefined,
      status: filters.status || undefined,
      purchase_order_id: filters.purchase_order_id ? Number(filters.purchase_order_id) : undefined,
      warehouse_id: filters.warehouse_id ? Number(filters.warehouse_id) : undefined,
      page: filters.page,
      per_page: 15,
    })
    
    if (response.data) {
      receipts.value = response.data
      pagination.value = response.meta || response.pagination || null
    } else {
      receipts.value = []
      pagination.value = null
    }
  } catch (err: any) {
    console.error('Failed to fetch goods receipts:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to fetch goods receipts. Please try again.'
    receipts.value = []
  } finally {
    loading.value = false
  }
}

const fetchPurchaseOrders = async () => {
  try {
    const response = await purchasingApi.getPurchaseOrders({
      per_page: 100,
      status: 'confirmed',
    })
    purchaseOrders.value = response.data
  } catch (err: any) {
    console.error('Failed to fetch purchase orders:', err)
  }
}

const fetchWarehouses = async () => {
  try {
    const response = await inventoryApi.getWarehouses({ per_page: 100 })
    warehouses.value = response.data
  } catch (err: any) {
    console.error('Failed to fetch warehouses:', err)
  }
}

const clearFilters = () => {
  filters.search = ''
  filters.status = ''
  filters.purchase_order_id = ''
  filters.warehouse_id = ''
  filters.from_date = ''
  filters.to_date = ''
  filters.page = 1
  fetchReceipts()
}

const navigateToCreate = () => {
  router.push({ name: 'purchasing-goods-receipt-create' })
}

const viewReceipt = (id: number) => {
  router.push({ name: 'purchasing-goods-receipt-detail', params: { id: id.toString() } })
}

const inspectReceipt = async (id: number) => {
  if (!confirm('Are you sure you want to mark this receipt as inspected?')) return
  
  try {
    await purchasingApi.inspectGoodsReceipt(id)
    await fetchReceipts()
  } catch (err: any) {
    console.error('Failed to inspect receipt:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to inspect goods receipt.'
  }
}

const acceptReceipt = async (id: number) => {
  if (!confirm('Are you sure you want to accept this goods receipt? This will update inventory.')) return
  
  try {
    await purchasingApi.acceptGoodsReceipt(id)
    await fetchReceipts()
  } catch (err: any) {
    console.error('Failed to accept receipt:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to accept goods receipt.'
  }
}

const rejectReceipt = async (id: number) => {
  const reason = prompt('Please provide a reason for rejection (minimum 10 characters):')
  if (!reason) return
  
  if (reason.trim().length < 10) {
    error.value = 'Rejection reason must be at least 10 characters long.'
    return
  }
  
  try {
    await purchasingApi.rejectGoodsReceipt(id, reason)
    await fetchReceipts()
  } catch (err: any) {
    console.error('Failed to reject receipt:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to reject goods receipt.'
  }
}

const goToPage = (page: number) => {
  filters.page = page
  fetchReceipts()
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    inspected: 'bg-blue-100 text-blue-800',
    accepted: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatStatus = (status: string) => {
  return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

onMounted(async () => {
  await Promise.all([
    fetchReceipts(),
    fetchPurchaseOrders(),
    fetchWarehouses(),
  ])
})
</script>

<style scoped>
.input {
  @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm;
}

.btn-primary {
  @apply inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2;
}

.btn-secondary {
  @apply inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2;
}
</style>
