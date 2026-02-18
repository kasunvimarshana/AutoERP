<template>
  <div class="inventory-products">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Products
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          Manage your product catalog and inventory
        </p>
      </div>
      <div class="flex items-center space-x-3">
        <button
          class="btn-secondary flex items-center space-x-2"
          @click="showLowStock"
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
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
            />
          </svg>
          <span>Low Stock</span>
        </button>
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
          <span>Add Product</span>
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search products..."
        class="input"
        @input="debouncedSearch"
      >
      <select
        v-model="filters.product_type"
        class="input"
        @change="fetchProducts"
      >
        <option value="">
          All Types
        </option>
        <option value="inventory">
          Inventory
        </option>
        <option value="service">
          Service
        </option>
      </select>
      <select
        v-model="filters.status"
        class="input"
        @change="fetchProducts"
      >
        <option value="">
          All Statuses
        </option>
        <option value="active">
          Active
        </option>
        <option value="inactive">
          Inactive
        </option>
      </select>
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
        Loading products...
      </p>
    </div>

    <!-- Products Table -->
    <div
      v-else-if="products.length > 0"
      class="overflow-hidden rounded-lg bg-white shadow"
    >
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              SKU
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Name
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Type
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Status
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Stock
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
              Price
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr
            v-for="product in products"
            :key="product.id"
            class="hover:bg-gray-50"
          >
            <td class="px-6 py-4 text-sm font-medium text-gray-900">
              {{ product.sku }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">
              {{ product.name }}
            </td>
            <td class="px-6 py-4 text-sm">
              <span class="rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-800">
                {{ product.product_type }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm">
              <span
                :class="getStatusClass(product.status)"
                class="rounded-full px-2 py-1 text-xs"
              >
                {{ product.status }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              <span
                v-if="product.total_stock !== undefined && product.reorder_point !== undefined && product.reorder_point !== null"
                :class="getStockStatusClass(product.total_stock, product.reorder_point)"
                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
              >
                {{ product.total_stock || 0 }}
                <svg
                  v-if="product.total_stock <= product.reorder_point"
                  class="ml-1 h-3 w-3"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"
                  />
                </svg>
              </span>
              <span v-else>
                {{ product.total_stock || 0 }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">
              ${{ formatPrice(product.selling_price) }}
            </td>
            <td class="px-6 py-4 text-right text-sm">
              <button
                class="text-blue-600 hover:text-blue-900 mr-4"
                @click="viewProduct(product.id)"
              >
                View
              </button>
              <button
                class="text-indigo-600 hover:text-indigo-900 mr-4"
                @click="editProduct(product.id)"
              >
                Edit
              </button>
              <button
                class="text-red-600 hover:text-red-900"
                @click="deleteProduct(product.id)"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
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
          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
        />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        No products found
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        Get started by creating a new product.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { inventoryApi } from '@/api/inventory'
import type { Product } from '@/types/inventory'

const router = useRouter()
const loading = ref(false)
const products = ref<Product[]>([])
const error = ref<string | null>(null)

const filters = reactive({
  search: '',
  product_type: '',
  status: '',
})

let searchTimeout: ReturnType<typeof setTimeout> | null = null

const debouncedSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchProducts()
  }, 300)
}

const fetchProducts = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await inventoryApi.getProducts({
      search: filters.search || undefined,
      product_type: filters.product_type || undefined,
      status: filters.status || undefined,
    })
    products.value = response.data || []
  } catch (err: any) {
    console.error('Failed to fetch products:', err)
    error.value = err.message || 'Failed to fetch products. Please try again.'
    products.value = []
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  filters.search = ''
  filters.product_type = ''
  filters.status = ''
  fetchProducts()
}

const navigateToCreate = () => {
  router.push({ name: 'inventory-product-create' })
}

const showLowStock = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await inventoryApi.getLowStockProducts({
      search: filters.search || undefined,
      product_type: filters.product_type || undefined,
    })
    products.value = response.data || []
  } catch (err: any) {
    console.error('Failed to fetch low stock products:', err)
    error.value = err.message || 'Failed to fetch low stock products. Please try again.'
    products.value = []
  } finally {
    loading.value = false
  }
}

const viewProduct = (id: string) => {
  router.push({ name: 'inventory-product-detail', params: { id } })
}

const editProduct = (id: string) => {
  router.push({ name: 'inventory-product-edit', params: { id } })
}

const deleteProduct = async (id: string) => {
  if (!confirm('Are you sure you want to delete this product?')) return
  
  try {
    await inventoryApi.deleteProduct(id)
    // Refresh the product list after successful deletion
    await fetchProducts()
  } catch (err: any) {
    console.error('Failed to delete product:', err)
    error.value = err.message || 'Failed to delete product. Please try again.'
  }
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
    draft: 'bg-yellow-100 text-yellow-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStockStatusClass = (stock: number, reorderPoint: number) => {
  if (stock === 0) {
    return 'bg-red-100 text-red-800'
  } else if (stock <= reorderPoint) {
    return 'bg-yellow-100 text-yellow-800'
  }
  return 'bg-green-100 text-green-800'
}

const formatPrice = (price: number | null | undefined) => {
  return (price || 0).toFixed(2)
}

onMounted(() => {
  fetchProducts()
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
  @apply inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50;
}
</style>
