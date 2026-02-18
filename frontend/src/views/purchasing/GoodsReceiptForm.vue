<template>
  <div class="goods-receipt-form">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Create Goods Receipt
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          Record incoming goods from a purchase order
        </p>
      </div>
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
    </div>

    <!-- Error Alert -->
    <div
      v-if="error"
      class="mb-4 rounded-md bg-red-50 p-4"
    >
      <div class="flex">
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">
            {{ error }}
          </h3>
        </div>
      </div>
    </div>

    <!-- Form -->
    <form
      class="space-y-6"
      @submit.prevent="handleSubmit"
    >
      <!-- Basic Information -->
      <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Receipt Information
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Purchase Order -->
          <div>
            <label
              for="purchase_order_id"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Purchase Order <span class="text-red-500">*</span>
            </label>
            <select
              id="purchase_order_id"
              v-model="form.purchase_order_id"
              required
              class="input"
              :class="{ 'border-red-500': errors.purchase_order_id }"
              @change="onPurchaseOrderChange"
            >
              <option value="">
                Select a purchase order
              </option>
              <option
                v-for="po in purchaseOrders"
                :key="po.id"
                :value="po.id"
              >
                {{ po.po_number }} - {{ po.supplier_name }}
              </option>
            </select>
            <p
              v-if="errors.purchase_order_id"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.purchase_order_id }}
            </p>
          </div>

          <!-- Receipt Date -->
          <div>
            <label
              for="receipt_date"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Receipt Date <span class="text-red-500">*</span>
            </label>
            <input
              id="receipt_date"
              v-model="form.receipt_date"
              type="date"
              required
              class="input"
              :class="{ 'border-red-500': errors.receipt_date }"
            >
            <p
              v-if="errors.receipt_date"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.receipt_date }}
            </p>
          </div>

          <!-- Warehouse -->
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
                Select a warehouse
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
        </div>
      </div>

      <!-- Line Items -->
      <div
        v-if="form.purchase_order_id && selectedPurchaseOrder"
        class="bg-white shadow-sm rounded-lg p-6"
      >
        <div class="mb-4">
          <h2 class="text-lg font-semibold text-gray-900">
            Line Items
          </h2>
          <p class="mt-1 text-sm text-gray-600">
            Enter received quantities for each item from the purchase order
          </p>
        </div>

        <!-- Line Items Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Product
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  SKU
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Qty Ordered
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Previously Received
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Qty Received <span class="text-red-500">*</span>
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Batch #
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Serial #
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Expiry Date
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Notes
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="(item, index) in form.items"
                :key="index"
              >
                <!-- Product Name -->
                <td class="px-3 py-4 text-sm text-gray-900">
                  {{ item.product_name }}
                </td>
                <!-- SKU -->
                <td class="px-3 py-4 text-sm text-gray-500">
                  {{ item.product_sku }}
                </td>
                <!-- Quantity Ordered -->
                <td class="px-3 py-4 text-sm text-gray-900 text-right">
                  {{ item.quantity_ordered }}
                </td>
                <!-- Previously Received -->
                <td class="px-3 py-4 text-sm text-gray-500 text-right">
                  {{ item.quantity_previously_received }}
                </td>
                <!-- Quantity Received -->
                <td class="px-3 py-4">
                  <input
                    v-model.number="item.quantity_received"
                    type="number"
                    min="0"
                    :max="item.quantity_ordered - item.quantity_previously_received"
                    step="1"
                    required
                    class="input w-24"
                    @input="validateQuantity(index)"
                  >
                </td>
                <!-- Batch Number -->
                <td class="px-3 py-4">
                  <input
                    v-model="item.batch_number"
                    type="text"
                    class="input w-32"
                    placeholder="Optional"
                  >
                </td>
                <!-- Serial Number -->
                <td class="px-3 py-4">
                  <input
                    v-model="item.serial_number"
                    type="text"
                    class="input w-32"
                    placeholder="Optional"
                  >
                </td>
                <!-- Expiry Date -->
                <td class="px-3 py-4">
                  <input
                    v-model="item.expiry_date"
                    type="date"
                    class="input w-36"
                  >
                </td>
                <!-- Notes -->
                <td class="px-3 py-4">
                  <textarea
                    v-model="item.notes"
                    rows="2"
                    class="input w-48"
                    placeholder="Optional notes"
                  />
                </td>
              </tr>
              <tr v-if="form.items.length === 0">
                <td
                  colspan="9"
                  class="px-3 py-8 text-center text-sm text-gray-500"
                >
                  Please select a purchase order to see items
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Overall Notes -->
      <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Additional Information
        </h2>
        
        <div>
          <label
            for="notes"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Notes
          </label>
          <textarea
            id="notes"
            v-model="form.notes"
            rows="4"
            class="input"
            placeholder="Enter any additional notes about this goods receipt"
          />
        </div>
      </div>

      <!-- Form Actions -->
      <div class="flex items-center justify-end space-x-4">
        <button
          type="button"
          class="btn-secondary"
          @click="goBack"
        >
          Cancel
        </button>
        <button
          type="submit"
          class="btn-primary"
          :disabled="loading || !canSubmit"
        >
          <span
            v-if="loading"
            class="flex items-center"
          >
            <svg
              class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
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
            Saving...
          </span>
          <span v-else>
            Create Goods Receipt
          </span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { purchasingApi } from '@/api/purchasing'
import { inventoryApi } from '@/api/inventory'
import type { PurchaseOrder } from '@/api/purchasing'
import type { Warehouse } from '@/types/inventory'

const router = useRouter()
const route = useRoute()

const loading = ref(false)
const error = ref<string | null>(null)
const errors = reactive<Record<string, string>>({})

const purchaseOrders = ref<PurchaseOrder[]>([])
const warehouses = ref<Warehouse[]>([])
const selectedPurchaseOrder = ref<PurchaseOrder | null>(null)

interface LineItem {
  purchase_order_item_id: number
  product_id: number
  product_name: string
  product_sku: string
  quantity_ordered: number
  quantity_previously_received: number
  quantity_received: number
  batch_number: string
  serial_number: string
  expiry_date: string
  notes: string
}

const form = reactive({
  purchase_order_id: '',
  receipt_date: new Date().toISOString().split('T')[0],
  warehouse_id: '',
  notes: '',
  items: [] as LineItem[],
})

const canSubmit = computed(() => {
  return form.purchase_order_id && 
         form.warehouse_id && 
         form.items.length > 0 &&
         form.items.some(item => item.quantity_received > 0)
})

const fetchPurchaseOrders = async () => {
  try {
    const response = await purchasingApi.getPurchaseOrders({
      per_page: 100,
      status: 'confirmed',
    })
    purchaseOrders.value = response.data

    // Pre-select PO from query param if present
    const poIdFromQuery = route.query.purchase_order_id
    if (poIdFromQuery) {
      form.purchase_order_id = String(poIdFromQuery)
      await onPurchaseOrderChange()
    }
  } catch (err: any) {
    console.error('Failed to fetch purchase orders:', err)
    error.value = 'Failed to load purchase orders. Please refresh the page.'
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

const onPurchaseOrderChange = async () => {
  if (!form.purchase_order_id) {
    form.items = []
    selectedPurchaseOrder.value = null
    return
  }

  try {
    const po = await purchasingApi.getPurchaseOrder(form.purchase_order_id)
    selectedPurchaseOrder.value = po
    
    // Initialize line items from PO
    form.items = po.items.map(item => ({
      purchase_order_item_id: item.id,
      product_id: item.product_id,
      product_name: item.product_name || 'N/A',
      product_sku: item.product_sku || 'N/A',
      quantity_ordered: item.quantity,
      quantity_previously_received: item.quantity_received || 0,
      quantity_received: 0,
      batch_number: '',
      serial_number: '',
      expiry_date: '',
      notes: '',
    }))
  } catch (err: any) {
    console.error('Failed to fetch purchase order details:', err)
    error.value = 'Failed to load purchase order details. Please try again.'
  }
}

const validateQuantity = (index: number) => {
  const item = form.items[index]
  const maxQuantity = item.quantity_ordered - item.quantity_previously_received
  
  if (item.quantity_received < 0) {
    item.quantity_received = 0
  } else if (item.quantity_received > maxQuantity) {
    item.quantity_received = maxQuantity
  }
}

const handleSubmit = async () => {
  // Clear previous errors
  Object.keys(errors).forEach(key => delete errors[key])
  error.value = null
  
  // Use canSubmit validation
  if (!canSubmit.value) {
    error.value = 'Please complete all required fields and enter at least one received quantity.'
    return
  }
  
  loading.value = true

  try {
    const itemsWithQuantity = form.items.filter(item => item.quantity_received > 0)
    
    const payload = {
      purchase_order_id: Number(form.purchase_order_id),
      receipt_date: form.receipt_date,
      warehouse_id: Number(form.warehouse_id),
      notes: form.notes || undefined,
      items: itemsWithQuantity.map(item => ({
        purchase_order_item_id: item.purchase_order_item_id,
        product_id: item.product_id,
        quantity_received: item.quantity_received,
        quantity_accepted: item.quantity_received,
        quantity_rejected: 0,
        batch_number: item.batch_number || undefined,
        serial_number: item.serial_number || undefined,
        expiry_date: item.expiry_date || undefined,
        notes: item.notes || undefined,
      })),
    }

    await purchasingApi.createGoodsReceipt(payload)
    
    // Navigate back to goods receipts list
    router.push({ name: 'purchasing-goods-receipts' })
  } catch (err: any) {
    console.error('Failed to create goods receipt:', err)
    
    // Handle validation errors
    if (err.response?.data?.errors) {
      Object.assign(errors, err.response.data.errors)
    }
    
    error.value = err.response?.data?.message || err.message || 'Failed to create goods receipt. Please try again.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.back()
}

onMounted(async () => {
  await Promise.all([
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
  @apply inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2;
}
</style>
