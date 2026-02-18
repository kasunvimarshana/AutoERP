<template>
  <div class="product-form">
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">
        {{ isEdit ? 'Edit Product' : 'Create Product' }}
      </h1>
      <p class="mt-1 text-sm text-gray-600">
        {{ isEdit ? 'Update product information' : 'Add a new product to your inventory' }}
      </p>
    </div>

    <!-- Form -->
    <form
      class="space-y-6"
      @submit.prevent="handleSubmit"
    >
      <!-- Basic Information Card -->
      <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Basic Information
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Name -->
          <div class="md:col-span-2">
            <label
              for="name"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Product Name <span class="text-red-500">*</span>
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="input"
              :class="{ 'border-red-500': errors.name }"
              placeholder="Enter product name"
            >
            <p
              v-if="errors.name"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.name }}
            </p>
          </div>

          <!-- SKU -->
          <div>
            <label
              for="sku"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              SKU <span class="text-red-500">*</span>
            </label>
            <input
              id="sku"
              v-model="form.sku"
              type="text"
              required
              class="input"
              :class="{ 'border-red-500': errors.sku }"
              placeholder="Enter SKU"
            >
            <p
              v-if="errors.sku"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.sku }}
            </p>
          </div>

          <!-- Barcode -->
          <div>
            <label
              for="barcode"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Barcode
            </label>
            <input
              id="barcode"
              v-model="form.barcode"
              type="text"
              class="input"
              placeholder="Enter barcode"
            >
          </div>

          <!-- Product Type -->
          <div>
            <label
              for="type"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Product Type <span class="text-red-500">*</span>
            </label>
            <select
              id="type"
              v-model="form.type"
              required
              class="input"
              :class="{ 'border-red-500': errors.type }"
            >
              <option value="">
                Select type
              </option>
              <option value="inventory">
                Inventory
              </option>
              <option value="service">
                Service
              </option>
              <option value="bundle">
                Bundle
              </option>
              <option value="composite">
                Composite
              </option>
            </select>
            <p
              v-if="errors.type"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.type }}
            </p>
          </div>

          <!-- Status -->
          <div>
            <label
              for="status"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Status <span class="text-red-500">*</span>
            </label>
            <select
              id="status"
              v-model="form.status"
              required
              class="input"
            >
              <option value="active">
                Active
              </option>
              <option value="inactive">
                Inactive
              </option>
              <option value="draft">
                Draft
              </option>
              <option value="discontinued">
                Discontinued
              </option>
            </select>
          </div>

          <!-- Description -->
          <div class="md:col-span-2">
            <label
              for="description"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Description
            </label>
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              class="input"
              placeholder="Enter product description"
            />
          </div>
        </div>
      </div>

      <!-- Pricing Information Card -->
      <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Pricing Information
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Cost Price -->
          <div>
            <label
              for="cost_price"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Cost Price <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
              <input
                id="cost_price"
                v-model.number="form.cost_price"
                type="number"
                step="0.01"
                min="0"
                required
                class="input pl-8"
                :class="{ 'border-red-500': errors.cost_price }"
                placeholder="0.00"
              >
            </div>
            <p
              v-if="errors.cost_price"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.cost_price }}
            </p>
          </div>

          <!-- Selling Price -->
          <div>
            <label
              for="selling_price"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Selling Price <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
              <input
                id="selling_price"
                v-model.number="form.selling_price"
                type="number"
                step="0.01"
                min="0"
                required
                class="input pl-8"
                :class="{ 'border-red-500': errors.selling_price }"
                placeholder="0.00"
              >
            </div>
            <p
              v-if="errors.selling_price"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.selling_price }}
            </p>
          </div>

          <!-- Calculated Margin -->
          <div
            v-if="form.cost_price && form.selling_price"
            class="md:col-span-2"
          >
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="flex justify-between items-center">
                <div>
                  <span class="text-sm font-medium text-gray-700">Profit Margin:</span>
                  <span class="ml-2 text-lg font-semibold text-blue-600">
                    {{ calculateProfitMargin }}%
                  </span>
                </div>
                <div>
                  <span class="text-sm font-medium text-gray-700">Markup:</span>
                  <span class="ml-2 text-lg font-semibold text-blue-600">
                    {{ calculateMarkup }}%
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Inventory Information Card -->
      <div
        v-if="form.type === 'inventory'"
        class="bg-white shadow-sm rounded-lg p-6"
      >
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Inventory Information
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Reorder Point -->
          <div>
            <label
              for="reorder_point"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Reorder Point
            </label>
            <input
              id="reorder_point"
              v-model.number="form.reorder_point"
              type="number"
              min="0"
              class="input"
              placeholder="Enter minimum quantity"
            >
            <p class="mt-1 text-xs text-gray-500">
              Minimum quantity before reorder alert
            </p>
          </div>

          <!-- Category -->
          <div>
            <label
              for="category_id"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Category
            </label>
            <select
              id="category_id"
              v-model="form.category_id"
              class="input"
            >
              <option value="">
                Select category
              </option>
              <!-- Add categories dynamically -->
            </select>
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="flex items-center justify-end space-x-4 pt-6 border-t">
        <button
          type="button"
          class="btn-secondary"
          :disabled="loading"
          @click="handleCancel"
        >
          Cancel
        </button>
        <button
          type="submit"
          class="btn-primary"
          :disabled="loading"
        >
          <span
            v-if="loading"
            class="flex items-center"
          >
            <svg
              class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              />
            </svg>
            {{ isEdit ? 'Updating...' : 'Creating...' }}
          </span>
          <span v-else>
            {{ isEdit ? 'Update Product' : 'Create Product' }}
          </span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { inventoryApi } from '@/api/inventory'
import type { Product, ProductType, ProductStatus } from '@/types/inventory'

const router = useRouter()
const route = useRoute()

const isEdit = computed(() => !!route.params.id)
const productId = computed(() => route.params.id as string)

const loading = ref(false)
const errors = ref<Record<string, string>>({})

const form = ref({
  name: '',
  sku: '',
  barcode: '',
  type: 'inventory' as ProductType,
  description: '',
  cost_price: 0,
  selling_price: 0,
  reorder_point: 0,
  status: 'active' as ProductStatus,
  category_id: undefined as number | undefined,
})

const calculateProfitMargin = computed(() => {
  if (!form.value.cost_price || !form.value.selling_price) return '0.00'
  const margin = ((form.value.selling_price - form.value.cost_price) / form.value.selling_price) * 100
  return margin.toFixed(2)
})

const calculateMarkup = computed(() => {
  if (!form.value.cost_price || !form.value.selling_price) return '0.00'
  const markup = ((form.value.selling_price - form.value.cost_price) / form.value.cost_price) * 100
  return markup.toFixed(2)
})

onMounted(async () => {
  if (isEdit.value) {
    await loadProduct()
  }
})

async function loadProduct() {
  try {
    loading.value = true
    const product = await inventoryApi.getProduct(productId.value)
    
    form.value = {
      name: product.name,
      sku: product.sku,
      barcode: product.barcode || '',
      type: product.type,
      description: product.description || '',
      cost_price: product.cost_price,
      selling_price: product.selling_price,
      reorder_point: product.reorder_point || 0,
      status: product.status,
      category_id: product.category_id,
    }
  } catch (error: any) {
    console.error('Error loading product:', error)
    alert('Failed to load product. Please try again.')
    router.push({ name: 'inventory-products' })
  } finally {
    loading.value = false
  }
}

async function handleSubmit() {
  try {
    loading.value = true
    errors.value = {}

    // Validate form
    if (!form.value.name) {
      errors.value.name = 'Product name is required'
      return
    }
    if (!form.value.sku) {
      errors.value.sku = 'SKU is required'
      return
    }
    if (!form.value.type) {
      errors.value.type = 'Product type is required'
      return
    }
    if (form.value.cost_price <= 0) {
      errors.value.cost_price = 'Cost price must be greater than 0'
      return
    }
    if (form.value.selling_price <= 0) {
      errors.value.selling_price = 'Selling price must be greater than 0'
      return
    }

    if (isEdit.value) {
      await inventoryApi.updateProduct(productId.value, form.value)
      alert('Product updated successfully!')
    } else {
      await inventoryApi.createProduct(form.value)
      alert('Product created successfully!')
    }

    router.push({ name: 'inventory-products' })
  } catch (error: any) {
    console.error('Error saving product:', error)
    
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      alert(error.response?.data?.message || 'Failed to save product. Please try again.')
    }
  } finally {
    loading.value = false
  }
}

function handleCancel() {
  router.push({ name: 'inventory-products' })
}
</script>

<style scoped>
.input {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.btn-primary {
  @apply px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply px-4 py-2 bg-white text-gray-700 font-medium border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed;
}
</style>
