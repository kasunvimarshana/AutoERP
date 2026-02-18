<template>
  <div
    class="fixed inset-0 z-50 overflow-y-auto"
    @click.self="$emit('close')"
  >
    <div class="flex min-h-screen items-center justify-center p-4">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      
      <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full">
        <!-- Modal Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">
            Record Stock Transaction
          </h3>
          <button
            class="text-gray-400 hover:text-gray-500"
            @click="$emit('close')"
          >
            <svg
              class="h-6 w-6"
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
          </button>
        </div>

        <!-- Modal Body -->
        <form
          class="p-6 space-y-6"
          @submit.prevent="handleSubmit"
        >
          <!-- Error Alert -->
          <div
            v-if="error"
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

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Product Selection -->
            <div class="md:col-span-2">
              <label
                for="product_id"
                class="block text-sm font-medium text-gray-700 mb-1"
              >
                Product <span class="text-red-500">*</span>
              </label>
              <select
                id="product_id"
                v-model="form.product_id"
                required
                class="input"
                :class="{ 'border-red-500': errors.product_id }"
              >
                <option value="">
                  Select Product
                </option>
                <option
                  v-for="product in products"
                  :key="product.id"
                  :value="product.id"
                >
                  {{ product.name }} ({{ product.sku }})
                </option>
              </select>
              <p
                v-if="errors.product_id"
                class="mt-1 text-sm text-red-600"
              >
                {{ errors.product_id }}
              </p>
            </div>

            <!-- Warehouse Selection -->
            <div>
              <label
                for="warehouse_id"
                class="block text-sm font-medium text-gray-700 mb-1"
              >
                Warehouse <span class="text-red-500">*</span>
              </label>
              <select
                id="warehouse_id"
                v-model="form.warehouse_id"
                required
                class="input"
                :class="{ 'border-red-500': errors.warehouse_id }"
              >
                <option value="">
                  Select Warehouse
                </option>
                <option
                  v-for="warehouse in warehouses"
                  :key="warehouse.id"
                  :value="warehouse.id"
                >
                  {{ warehouse.name }}
                </option>
              </select>
              <p
                v-if="errors.warehouse_id"
                class="mt-1 text-sm text-red-600"
              >
                {{ errors.warehouse_id }}
              </p>
            </div>

            <!-- Transaction Type -->
            <div>
              <label
                for="transaction_type"
                class="block text-sm font-medium text-gray-700 mb-1"
              >
                Transaction Type <span class="text-red-500">*</span>
              </label>
              <select
                id="transaction_type"
                v-model="form.transaction_type"
                required
                class="input"
                :class="{ 'border-red-500': errors.transaction_type }"
              >
                <option value="">
                  Select Type
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
              <p
                v-if="errors.transaction_type"
                class="mt-1 text-sm text-red-600"
              >
                {{ errors.transaction_type }}
              </p>
            </div>

            <!-- Quantity -->
            <div>
              <label
                for="quantity"
                class="block text-sm font-medium text-gray-700 mb-1"
              >
                Quantity <span class="text-red-500">*</span>
              </label>
              <input
                id="quantity"
                v-model.number="form.quantity"
                type="number"
                required
                class="input"
                :class="{ 'border-red-500': errors.quantity }"
                placeholder="Enter quantity"
              >
              <p
                v-if="errors.quantity"
                class="mt-1 text-sm text-red-600"
              >
                {{ errors.quantity }}
              </p>
            </div>

            <!-- Cost Per Unit -->
            <div>
              <label
                for="cost_per_unit"
                class="block text-sm font-medium text-gray-700 mb-1"
              >
                Cost Per Unit
              </label>
              <input
                id="cost_per_unit"
                v-model.number="form.cost_per_unit"
                type="number"
                step="0.01"
                min="0"
                class="input"
                placeholder="Enter cost per unit"
              >
            </div>

            <!-- Reference -->
            <div class="md:col-span-2">
              <label
                for="reference"
                class="block text-sm font-medium text-gray-700 mb-1"
              >
                Reference
              </label>
              <input
                id="reference"
                v-model="form.reference"
                type="text"
                class="input"
                placeholder="Enter reference number or document ID"
              >
            </div>

            <!-- Notes -->
            <div class="md:col-span-2">
              <label
                for="notes"
                class="block text-sm font-medium text-gray-700 mb-1"
              >
                Notes
              </label>
              <textarea
                id="notes"
                v-model="form.notes"
                rows="3"
                class="input"
                placeholder="Enter any additional notes"
              />
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
            <button
              type="button"
              class="btn-secondary"
              @click="$emit('close')"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="btn-primary"
              :disabled="submitting"
            >
              <span v-if="submitting">
                <svg
                  class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline"
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
                Recording...
              </span>
              <span v-else>
                Record Transaction
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { inventoryApi } from '@/api/inventory'
import type { StockTransactionRequest, Product, Warehouse } from '@/types/inventory'

const emit = defineEmits<{
  close: []
  saved: []
}>()

const products = ref<Product[]>([])
const warehouses = ref<Warehouse[]>([])
const submitting = ref(false)
const error = ref<string | null>(null)

const form = reactive<StockTransactionRequest>({
  product_id: 0,
  warehouse_id: 0,
  quantity: 0,
  transaction_type: 'PURCHASE',
  cost_per_unit: undefined,
  reference: '',
  notes: '',
})

const errors = reactive<Record<string, string>>({})

const fetchProducts = async () => {
  try {
    // Fetch a reasonable number of products. For large inventories, 
    // consider implementing search/autocomplete in the select field.
    const response = await inventoryApi.getProducts({ per_page: 100 })
    products.value = response.data || []
    
    // TODO: For inventories with >100 products, implement:
    // 1. Search/autocomplete functionality
    // 2. Lazy loading/pagination in the dropdown
    // 3. Or allow manual SKU/ID entry
  } catch (err) {
    console.error('Failed to fetch products:', err)
  }
}

const fetchWarehouses = async () => {
  try {
    // Fetch all warehouses. Most systems won't have >100 warehouses,
    // but if needed, implement search/filter functionality.
    const response = await inventoryApi.getWarehouses({ per_page: 100 })
    warehouses.value = response.data || []
    
    // TODO: For systems with >100 warehouses, implement:
    // 1. Search/filter functionality
    // 2. Pagination in the dropdown
  } catch (err) {
    console.error('Failed to fetch warehouses:', err)
  }
}

const validateForm = (): boolean => {
  Object.keys(errors).forEach(key => delete errors[key])
  
  let isValid = true
  
  if (!form.product_id) {
    errors.product_id = 'Product is required'
    isValid = false
  }
  
  if (!form.warehouse_id) {
    errors.warehouse_id = 'Warehouse is required'
    isValid = false
  }
  
  if (!form.transaction_type) {
    errors.transaction_type = 'Transaction type is required'
    isValid = false
  }
  
  if (!form.quantity || form.quantity === 0) {
    errors.quantity = 'Quantity must be non-zero'
    isValid = false
  }
  
  return isValid
}

const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }
  
  submitting.value = true
  error.value = null
  
  try {
    await inventoryApi.recordTransaction(form)
    emit('saved')
  } catch (err: any) {
    console.error('Failed to record transaction:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to record transaction'
    
    if (err.response?.data?.errors) {
      Object.assign(errors, err.response.data.errors)
    }
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchProducts()
  fetchWarehouses()
})
</script>

<style scoped>
.input {
  @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm;
}

.btn-primary {
  @apply inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50;
}
</style>
