<template>
  <div class="quotation-list-view">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Quotations
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          Manage your sales quotations and quotes
        </p>
      </div>
      <button
        class="btn-primary"
        @click="createQuotation"
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
        New Quotation
      </button>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white rounded-lg shadow-sm p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Search -->
        <div class="md:col-span-2">
          <label
            for="search"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Search
          </label>
          <input
            id="search"
            v-model="filters.search"
            type="text"
            class="input"
            placeholder="Search by quote number or customer..."
            @input="debouncedSearch"
          >
        </div>

        <!-- Status Filter -->
        <div>
          <label
            for="status"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Status
          </label>
          <select
            id="status"
            v-model="filters.status"
            class="input"
            @change="fetchQuotations"
          >
            <option value="">
              All Statuses
            </option>
            <option value="draft">
              Draft
            </option>
            <option value="sent">
              Sent
            </option>
            <option value="accepted">
              Accepted
            </option>
            <option value="rejected">
              Rejected
            </option>
            <option value="expired">
              Expired
            </option>
            <option value="converted">
              Converted
            </option>
          </select>
        </div>

        <!-- Customer Filter -->
        <div>
          <label
            for="customer"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Customer
          </label>
          <select
            id="customer"
            v-model="filters.customer_id"
            class="input"
            @change="fetchQuotations"
          >
            <option value="">
              All Customers
            </option>
            <option
              v-for="customer in customers"
              :key="customer.id"
              :value="customer.id"
            >
              {{ customer.customer_name }}
            </option>
          </select>
        </div>

        <!-- Date Range -->
        <div>
          <label
            for="from_date"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            From Date
          </label>
          <input
            id="from_date"
            v-model="filters.from_date"
            type="date"
            class="input"
            @change="fetchQuotations"
          >
        </div>

        <div>
          <label
            for="to_date"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            To Date
          </label>
          <input
            id="to_date"
            v-model="filters.to_date"
            type="date"
            class="input"
            @change="fetchQuotations"
          >
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div
      v-if="loading"
      class="text-center py-12"
    >
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent" />
      <p class="mt-4 text-gray-600">
        Loading quotations...
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

    <!-- Quotations Table -->
    <div
      v-else-if="quotations.length"
      class="bg-white shadow-sm rounded-lg overflow-hidden"
    >
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              scope="col"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Quote Number
            </th>
            <th
              scope="col"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Customer
            </th>
            <th
              scope="col"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Quote Date
            </th>
            <th
              scope="col"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Valid Until
            </th>
            <th
              scope="col"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Status
            </th>
            <th
              scope="col"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Total Amount
            </th>
            <th
              scope="col"
              class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="quotation in quotations"
            :key="quotation.id"
            class="hover:bg-gray-50"
          >
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">
                {{ quotation.quote_number }}
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">
                {{ quotation.customer_name || 'N/A' }}
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">
                {{ formatDate(quotation.quote_date) }}
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">
                {{ quotation.valid_until ? formatDate(quotation.valid_until) : 'N/A' }}
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="getStatusClass(quotation.status)"
                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
              >
                {{ formatStatus(quotation.status) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ formatCurrency(quotation.total_amount, quotation.currency) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button
                class="text-indigo-600 hover:text-indigo-900 mr-4"
                @click="viewQuotation(quotation.id)"
              >
                View
              </button>
              <button
                v-if="quotation.status === 'draft'"
                class="text-indigo-600 hover:text-indigo-900 mr-4"
                @click="editQuotation(quotation.id)"
              >
                Edit
              </button>
              <button
                class="text-red-600 hover:text-red-900"
                @click="confirmDelete(quotation)"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div
        v-if="pagination.last_page > 1"
        class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6"
      >
        <div class="flex-1 flex justify-between sm:hidden">
          <button
            :disabled="pagination.current_page === 1"
            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
            @click="changePage(pagination.current_page - 1)"
          >
            Previous
          </button>
          <button
            :disabled="pagination.current_page === pagination.last_page"
            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
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
            <nav
              class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
              aria-label="Pagination"
            >
              <button
                :disabled="pagination.current_page === 1"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                @click="changePage(pagination.current_page - 1)"
              >
                <span class="sr-only">Previous</span>
                <svg
                  class="h-5 w-5"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                  aria-hidden="true"
                >
                  <path
                    fill-rule="evenodd"
                    d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                    clip-rule="evenodd"
                  />
                </svg>
              </button>
              <button
                v-for="page in visiblePages"
                :key="page"
                :class="[
                  page === pagination.current_page
                    ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                ]"
                @click="changePage(page)"
              >
                {{ page }}
              </button>
              <button
                :disabled="pagination.current_page === pagination.last_page"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                @click="changePage(pagination.current_page + 1)"
              >
                <span class="sr-only">Next</span>
                <svg
                  class="h-5 w-5"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                  aria-hidden="true"
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
      class="text-center py-12 bg-white rounded-lg shadow-sm"
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
        No quotations found
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        Get started by creating a new quotation.
      </p>
      <div class="mt-6">
        <button
          class="btn-primary"
          @click="createQuotation"
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
          New Quotation
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { salesApi, type Quotation, type Customer } from '@/api/sales'

const router = useRouter()

const loading = ref(false)
const error = ref<string | null>(null)
const quotations = ref<Quotation[]>([])
const customers = ref<Customer[]>([])

const filters = reactive({
  search: '',
  status: '',
  customer_id: '',
  from_date: '',
  to_date: '',
  page: 1,
  per_page: 15,
})

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0,
})

const visiblePages = computed(() => {
  const pages = []
  const start = Math.max(1, pagination.current_page - 2)
  const end = Math.min(pagination.last_page, pagination.current_page + 2)
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

let debounceTimeout: ReturnType<typeof setTimeout> | null = null

const debouncedSearch = () => {
  if (debounceTimeout) {
    clearTimeout(debounceTimeout)
  }
  debounceTimeout = setTimeout(() => {
    filters.page = 1
    fetchQuotations()
  }, 300)
}

const fetchQuotations = async () => {
  loading.value = true
  error.value = null

  try {
    const params: any = {
      page: filters.page,
      per_page: filters.per_page,
    }

    if (filters.search) params.search = filters.search
    if (filters.status) params.status = filters.status
    if (filters.customer_id) params.customer_id = filters.customer_id
    if (filters.from_date) params.from_date = filters.from_date
    if (filters.to_date) params.to_date = filters.to_date

    const response = await salesApi.getQuotations(params)
    
    quotations.value = response.data
    pagination.current_page = response.meta.current_page
    pagination.last_page = response.meta.last_page
    pagination.per_page = response.meta.per_page
    pagination.total = response.meta.total
    pagination.from = response.meta.from
    pagination.to = response.meta.to
  } catch (err: any) {
    console.error('Failed to fetch quotations:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load quotations.'
  } finally {
    loading.value = false
  }
}

const fetchCustomers = async () => {
  try {
    const response = await salesApi.getCustomers({ per_page: 100, status: 'active' })
    customers.value = response.data
  } catch (err: any) {
    console.warn('Failed to fetch customers:', err)
  }
}

const changePage = (page: number) => {
  if (page >= 1 && page <= pagination.last_page) {
    filters.page = page
    fetchQuotations()
  }
}

const createQuotation = () => {
  router.push({ name: 'sales-quotation-create' })
}

const viewQuotation = (id: number) => {
  router.push({ name: 'sales-quotation-detail', params: { id } })
}

const editQuotation = (id: number) => {
  router.push({ name: 'sales-quotation-edit', params: { id } })
}

const confirmDelete = async (quotation: Quotation) => {
  if (!confirm(`Are you sure you want to delete quotation "${quotation.quote_number}"? This action cannot be undone.`)) {
    return
  }

  try {
    await salesApi.deleteQuotation(quotation.id)
    await fetchQuotations()
  } catch (err: any) {
    console.error('Failed to delete quotation:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to delete quotation.'
  }
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    sent: 'bg-blue-100 text-blue-800',
    accepted: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    expired: 'bg-yellow-100 text-yellow-800',
    converted: 'bg-purple-100 text-purple-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatStatus = (status: string) => {
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatCurrency = (amount: number, currency: string = 'USD') => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency,
  }).format(amount)
}

onMounted(() => {
  fetchCustomers()
  fetchQuotations()
})
</script>

<style scoped>
.input {
  @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm;
}

.btn-primary {
  @apply inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2;
}
</style>
