<template>
  <div class="purchase-order-list">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Purchase Orders
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          Manage your purchase orders and track deliveries
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
        <span>Create Purchase Order</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-5">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search orders..."
        class="input"
        @input="debouncedSearch"
      >
      <select
        v-model="filters.status"
        class="input"
        @change="fetchOrders"
      >
        <option value="">
          All Statuses
        </option>
        <option value="draft">
          Draft
        </option>
        <option value="submitted">
          Submitted
        </option>
        <option value="approved">
          Approved
        </option>
        <option value="received">
          Received
        </option>
        <option value="cancelled">
          Cancelled
        </option>
      </select>
      <input
        v-model="filters.from_date"
        type="date"
        class="input"
        placeholder="From date"
        @change="fetchOrders"
      >
      <input
        v-model="filters.to_date"
        type="date"
        class="input"
        placeholder="To date"
        @change="fetchOrders"
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
        Loading purchase orders...
      </p>
    </div>

    <!-- Orders Table -->
    <div
      v-else-if="orders.length > 0"
      class="overflow-hidden rounded-lg bg-white shadow"
    >
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              PO Number
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Supplier
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Order Date
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Expected Delivery
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Total
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
            v-for="order in orders"
            :key="order.id"
            class="hover:bg-gray-50"
          >
            <td class="px-6 py-4 text-sm font-medium text-gray-900">
              {{ order.order_number || order.po_number || `PO-${order.id}` }}
            </td>
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">
                {{ order.supplier_name || order.supplier?.name || 'N/A' }}
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ formatDate(order.order_date) }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ order.expected_delivery_date ? formatDate(order.expected_delivery_date) : 'N/A' }}
            </td>
            <td class="px-6 py-4 text-sm font-medium text-gray-900">
              {{ formatCurrency(order.total_amount) }}
            </td>
            <td class="px-6 py-4 text-sm">
              <span
                :class="getStatusClass(order.status)"
                class="rounded-full px-2 py-1 text-xs font-medium"
              >
                {{ formatStatus(order.status) }}
              </span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button
                class="text-blue-600 hover:text-blue-900"
                @click="viewOrder(order.id)"
              >
                View
              </button>
              <button
                v-if="order.status === 'draft'"
                class="text-indigo-600 hover:text-indigo-900"
                @click="editOrder(order.id)"
              >
                Edit
              </button>
              <button
                v-if="order.status === 'draft'"
                class="text-green-600 hover:text-green-900"
                @click="submitOrder(order.id)"
              >
                Submit
              </button>
              <button
                v-if="order.status === 'submitted'"
                class="text-green-600 hover:text-green-900"
                @click="approveOrder(order.id)"
              >
                Approve
              </button>
              <button
                v-if="['draft', 'submitted', 'approved'].includes(order.status)"
                class="text-red-600 hover:text-red-900"
                @click="cancelOrder(order.id)"
              >
                Cancel
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
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
        />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        No purchase orders found
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        Get started by creating a new purchase order.
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
          Create Purchase Order
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { purchasingApi } from '@/api/purchasing'
import type { PurchaseOrder } from '@/api/purchasing'

const router = useRouter()
const loading = ref(false)
const orders = ref<PurchaseOrder[]>([])
const error = ref<string | null>(null)
const pagination = ref<any>(null)

const filters = reactive({
  search: '',
  status: '',
  from_date: '',
  to_date: '',
  page: 1,
})

let searchTimeout: ReturnType<typeof setTimeout> | null = null

const debouncedSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    filters.page = 1
    fetchOrders()
  }, 300)
}

const fetchOrders = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await purchasingApi.getPurchaseOrders({
      search: filters.search || undefined,
      status: filters.status || undefined,
      date_from: filters.from_date || undefined,
      date_to: filters.to_date || undefined,
      page: filters.page,
      per_page: 15,
    })
    
    if (response.data) {
      orders.value = response.data
      pagination.value = response.meta || response.pagination || null
    } else {
      orders.value = []
      pagination.value = null
    }
  } catch (err: any) {
    console.error('Failed to fetch purchase orders:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to fetch purchase orders. Please try again.'
    orders.value = []
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  filters.search = ''
  filters.status = ''
  filters.from_date = ''
  filters.to_date = ''
  filters.page = 1
  fetchOrders()
}

const navigateToCreate = () => {
  router.push({ name: 'purchasing-order-create' })
}

const viewOrder = (id: number) => {
  router.push({ name: 'purchasing-order-detail', params: { id: id.toString() } })
}

const editOrder = (id: number) => {
  router.push({ name: 'purchasing-order-edit', params: { id: id.toString() } })
}

const submitOrder = async (id: number) => {
  if (!confirm('Are you sure you want to submit this purchase order?')) return
  
  try {
    await purchasingApi.sendPurchaseOrder(id)
    await fetchOrders()
  } catch (err: any) {
    console.error('Failed to submit order:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to submit purchase order.'
  }
}

const approveOrder = async (id: number) => {
  if (!confirm('Are you sure you want to approve this purchase order?')) return
  
  try {
    await purchasingApi.confirmPurchaseOrder(id)
    await fetchOrders()
  } catch (err: any) {
    console.error('Failed to approve order:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to approve purchase order.'
  }
}

const cancelOrder = async (id: number) => {
  if (!confirm('Are you sure you want to cancel this purchase order? This action cannot be undone.')) return
  
  try {
    await purchasingApi.cancelPurchaseOrder(id)
    await fetchOrders()
  } catch (err: any) {
    console.error('Failed to cancel order:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to cancel purchase order.'
  }
}

const goToPage = (page: number) => {
  filters.page = page
  fetchOrders()
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    submitted: 'bg-blue-100 text-blue-800',
    approved: 'bg-green-100 text-green-800',
    sent: 'bg-indigo-100 text-indigo-800',
    confirmed: 'bg-purple-100 text-purple-800',
    received: 'bg-teal-100 text-teal-800',
    cancelled: 'bg-red-100 text-red-800',
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

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

onMounted(() => {
  fetchOrders()
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
