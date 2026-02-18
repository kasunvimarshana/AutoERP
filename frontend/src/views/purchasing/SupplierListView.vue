<template>
  <div class="supplier-list">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Suppliers
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          Manage your supplier and vendor information
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
        <span>Add Supplier</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search suppliers..."
        class="input"
        @input="debouncedSearch"
      >
      <select
        v-model="filters.status"
        class="input"
        @change="fetchSuppliers"
      >
        <option value="">
          All Statuses
        </option>
        <option value="active">
          Active
        </option>
        <option value="suspended">
          Suspended
        </option>
        <option value="blocked">
          Blocked
        </option>
      </select>
      <input
        v-model="filters.country"
        type="text"
        placeholder="Filter by country..."
        class="input"
        @input="debouncedSearch"
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
        Loading suppliers...
      </p>
    </div>

    <!-- Suppliers Table -->
    <div
      v-else-if="suppliers.length > 0"
      class="overflow-hidden rounded-lg bg-white shadow"
    >
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Code
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Name
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Contact
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Country
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Status
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Rating
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr
            v-for="supplier in suppliers"
            :key="supplier.id"
            class="hover:bg-gray-50"
          >
            <td class="px-6 py-4 text-sm font-medium text-gray-900">
              {{ supplier.code || 'N/A' }}
            </td>
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">
                {{ supplier.name }}
              </div>
              <div class="text-sm text-gray-500">
                {{ supplier.contact_person || '' }}
              </div>
            </td>
            <td class="px-6 py-4">
              <div class="text-sm text-gray-900">
                {{ supplier.email || 'N/A' }}
              </div>
              <div class="text-sm text-gray-500">
                {{ supplier.phone || '' }}
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ supplier.country || 'N/A' }}
            </td>
            <td class="px-6 py-4 text-sm">
              <span
                :class="getStatusClass(supplier.status)"
                class="rounded-full px-2 py-1 text-xs"
              >
                {{ supplier.status || 'active' }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              <div class="flex items-center">
                <span
                  v-for="n in 5"
                  :key="n"
                  class="text-yellow-400"
                >
                  {{ n <= (supplier.rating || 0) ? '★' : '☆' }}
                </span>
              </div>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button
                class="text-blue-600 hover:text-blue-900"
                @click="viewSupplier(supplier.id)"
              >
                View
              </button>
              <button
                class="text-indigo-600 hover:text-indigo-900"
                @click="editSupplier(supplier.id)"
              >
                Edit
              </button>
              <button
                v-if="supplier.status === 'suspended'"
                class="text-green-600 hover:text-green-900"
                @click="activateSupplier(supplier.id)"
              >
                Activate
              </button>
              <button
                v-else-if="supplier.status === 'active'"
                class="text-orange-600 hover:text-orange-900"
                @click="suspendSupplier(supplier.id)"
              >
                Suspend
              </button>
              <button
                class="text-red-600 hover:text-red-900"
                @click="deleteSupplier(supplier.id)"
              >
                Delete
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
          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
        />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        No suppliers found
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        Get started by creating a new supplier.
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
          Add Supplier
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { purchasingApi } from '@/api/purchasing'
import type { Supplier } from '@/types/purchasing'

const router = useRouter()
const loading = ref(false)
const suppliers = ref<Supplier[]>([])
const error = ref<string | null>(null)
const pagination = ref<any>(null)

const filters = reactive({
  search: '',
  status: '',
  country: '',
  page: 1,
})

let searchTimeout: ReturnType<typeof setTimeout> | null = null

const debouncedSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    filters.page = 1
    fetchSuppliers()
  }, 300)
}

const fetchSuppliers = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await purchasingApi.getSuppliers({
      search: filters.search || undefined,
      status: filters.status || undefined,
      country: filters.country || undefined,
      page: filters.page,
      per_page: 15,
    })
    
    if (response.data) {
      suppliers.value = response.data
      pagination.value = response.meta || response.pagination || null
    } else {
      suppliers.value = []
      pagination.value = null
    }
  } catch (err: any) {
    console.error('Failed to fetch suppliers:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to fetch suppliers. Please try again.'
    suppliers.value = []
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  filters.search = ''
  filters.status = ''
  filters.country = ''
  filters.page = 1
  fetchSuppliers()
}

const navigateToCreate = () => {
  router.push({ name: 'purchasing-supplier-create' })
}

const viewSupplier = (id: number) => {
  router.push({ name: 'purchasing-supplier-detail', params: { id: id.toString() } })
}

const editSupplier = (id: number) => {
  router.push({ name: 'purchasing-supplier-edit', params: { id: id.toString() } })
}

const activateSupplier = async (id: number) => {
  if (!confirm('Are you sure you want to activate this supplier?')) return
  
  try {
    await purchasingApi.activateSupplier(id)
    await fetchSuppliers()
  } catch (err: any) {
    console.error('Failed to activate supplier:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to activate supplier. Please try again.'
  }
}

const suspendSupplier = async (id: number) => {
  if (!confirm('Are you sure you want to suspend this supplier?')) return
  
  try {
    await purchasingApi.suspendSupplier(id)
    await fetchSuppliers()
  } catch (err: any) {
    console.error('Failed to suspend supplier:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to suspend supplier. Please try again.'
  }
}

const deleteSupplier = async (id: number) => {
  if (!confirm('Are you sure you want to delete this supplier? This action cannot be undone.')) return
  
  try {
    await purchasingApi.deleteSupplier(id)
    await fetchSuppliers()
  } catch (err: any) {
    console.error('Failed to delete supplier:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to delete supplier. Please try again.'
  }
}

const goToPage = (page: number) => {
  filters.page = page
  fetchSuppliers()
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    suspended: 'bg-yellow-100 text-yellow-800',
    blocked: 'bg-red-100 text-red-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

onMounted(() => {
  fetchSuppliers()
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
