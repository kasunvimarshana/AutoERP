<template>
  <div class="sales-order-form">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          {{ isEdit ? 'Edit Sales Order' : 'Create Sales Order' }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          {{ isEdit ? 'Update sales order information' : 'Create a new sales order' }}
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
          Basic Information
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Customer -->
          <div>
            <label
              for="customer_id"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Customer <span class="text-red-500">*</span>
            </label>
            <select
              id="customer_id"
              v-model="form.customer_id"
              required
              class="input"
              :class="{ 'border-red-500': errors.customer_id }"
            >
              <option value="">
                Select a customer
              </option>
              <option
                v-for="customer in customers"
                :key="customer.id"
                :value="customer.id"
              >
                {{ customer.customer_name }}
              </option>
            </select>
            <p
              v-if="errors.customer_id"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.customer_id }}
            </p>
          </div>

          <!-- Order Date -->
          <div>
            <label
              for="order_date"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Order Date <span class="text-red-500">*</span>
            </label>
            <input
              id="order_date"
              v-model="form.order_date"
              type="date"
              required
              class="input"
              :class="{ 'border-red-500': errors.order_date }"
            >
            <p
              v-if="errors.order_date"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.order_date }}
            </p>
          </div>

          <!-- Delivery Date -->
          <div>
            <label
              for="delivery_date"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Delivery Date
            </label>
            <input
              id="delivery_date"
              v-model="form.delivery_date"
              type="date"
              class="input"
              :min="form.order_date"
            >
          </div>

          <!-- Currency -->
          <div>
            <label
              for="currency"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Currency
            </label>
            <input
              id="currency"
              v-model="form.currency"
              type="text"
              maxlength="3"
              class="input"
              placeholder="USD"
            >
          </div>
        </div>

        <!-- Notes -->
        <div class="mt-6">
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

      <!-- Line Items -->
      <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">
            Line Items
          </h2>
          <button
            type="button"
            class="btn-primary"
            @click="addLineItem"
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
            Add Item
          </button>
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
                  Quantity
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Unit Price
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Discount (%)
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Tax (%)
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Line Total
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Action
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="(item, index) in form.items"
                :key="index"
              >
                <!-- Product -->
                <td class="px-3 py-4">
                  <select
                    v-model="item.product_id"
                    required
                    class="input"
                    @change="updateLineItem(index)"
                  >
                    <option value="">
                      Select product
                    </option>
                    <option
                      v-for="product in products"
                      :key="product.id"
                      :value="product.id"
                    >
                      {{ product.name }} ({{ product.sku }})
                    </option>
                  </select>
                </td>
                <!-- Quantity -->
                <td class="px-3 py-4">
                  <input
                    v-model.number="item.quantity"
                    type="number"
                    min="0.01"
                    step="0.01"
                    required
                    class="input"
                    @input="updateLineItem(index)"
                  >
                </td>
                <!-- Unit Price -->
                <td class="px-3 py-4">
                  <input
                    v-model.number="item.unit_price"
                    type="number"
                    min="0"
                    step="0.01"
                    required
                    class="input"
                    @input="updateLineItem(index)"
                  >
                </td>
                <!-- Discount Percent -->
                <td class="px-3 py-4">
                  <input
                    v-model.number="item.discount_percent"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    class="input"
                    @input="updateLineItem(index)"
                  >
                </td>
                <!-- Tax Percent -->
                <td class="px-3 py-4">
                  <input
                    v-model.number="item.tax_percent"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    class="input"
                    @input="updateLineItem(index)"
                  >
                </td>
                <!-- Line Total -->
                <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatCurrency(calculateLineTotal(item)) }}
                </td>
                <!-- Action -->
                <td class="px-3 py-4 whitespace-nowrap text-right text-sm">
                  <button
                    type="button"
                    class="text-red-600 hover:text-red-900"
                    @click="removeLineItem(index)"
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
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                      />
                    </svg>
                  </button>
                </td>
              </tr>
              <tr v-if="form.items.length === 0">
                <td
                  colspan="7"
                  class="px-3 py-8 text-center text-sm text-gray-500"
                >
                  No items added. Click "Add Item" to start.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Totals Section -->
        <div class="mt-6 border-t pt-4">
          <div class="flex justify-end">
            <div class="w-full max-w-xs space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Subtotal:</span>
                <span class="font-medium text-gray-900">{{ formatCurrency(calculateSubtotal()) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Discount Amount:</span>
                <span class="font-medium text-gray-900">{{ formatCurrency(calculateDiscountAmount()) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Tax Amount:</span>
                <span class="font-medium text-gray-900">{{ formatCurrency(calculateTaxAmount()) }}</span>
              </div>
              <div class="flex justify-between text-base font-semibold border-t pt-2">
                <span class="text-gray-900">Total Amount:</span>
                <span class="text-gray-900">{{ formatCurrency(calculateTotal()) }}</span>
              </div>
            </div>
          </div>
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
          :disabled="loading || form.items.length === 0"
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
            {{ isEdit ? 'Update Sales Order' : 'Create Sales Order' }}
          </span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { salesApi } from '@/api/sales'
import { inventoryApi } from '@/api/inventory'
import type { Customer } from '@/api/sales'
import type { Product } from '@/types/inventory'

const router = useRouter()
const route = useRoute()

const loading = ref(false)
const error = ref<string | null>(null)
const errors = reactive<Record<string, string>>({})

const customers = ref<Customer[]>([])
const products = ref<Product[]>([])

const isEdit = computed(() => !!route.params.id)
const orderId = computed(() => route.params.id as string)

interface LineItem {
  product_id: number | string
  quantity: number
  unit_price: number
  discount_percent: number
  tax_percent: number
}

const form = reactive({
  customer_id: '',
  order_date: new Date().toISOString().split('T')[0],
  delivery_date: '',
  currency: 'USD',
  notes: '',
  items: [] as LineItem[],
})

const fetchCustomers = async () => {
  try {
    const response = await salesApi.getCustomers({ per_page: 100 })
    customers.value = response.data
    
    const customerIdFromQuery = route.query.customer_id
    if (customerIdFromQuery && !isEdit.value) {
      form.customer_id = String(customerIdFromQuery)
    }
  } catch (err: any) {
    console.error('Failed to fetch customers:', err)
  }
}

const fetchProducts = async () => {
  try {
    const response = await inventoryApi.getProducts({ per_page: 100, status: 'active' })
    products.value = response.data
  } catch (err: any) {
    console.error('Failed to fetch products:', err)
  }
}

const fetchOrder = async () => {
  if (!isEdit.value) return

  loading.value = true
  error.value = null
  
  try {
    const order = await salesApi.getOrder(orderId.value)
    
    form.customer_id = String(order.customer_id)
    form.order_date = order.order_date.split('T')[0]
    form.delivery_date = order.delivery_date ? order.delivery_date.split('T')[0] : ''
    form.currency = order.currency || 'USD'
    form.notes = order.notes || ''
    
    form.items = order.items.map(item => ({
      product_id: item.product_id,
      quantity: item.quantity,
      unit_price: item.unit_price,
      discount_percent: item.discount_percent || 0,
      tax_percent: item.tax_percent || 0,
    }))
  } catch (err: any) {
    console.error('Failed to fetch sales order:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load sales order data.'
  } finally {
    loading.value = false
  }
}

const addLineItem = () => {
  form.items.push({
    product_id: '',
    quantity: 1,
    unit_price: 0,
    discount_percent: 0,
    tax_percent: 0,
  })
}

const removeLineItem = (index: number) => {
  form.items.splice(index, 1)
}

const updateLineItem = (index: number) => {
  const item = form.items[index]
  
  if (item.product_id) {
    const product = products.value.find(p => p.id === Number(item.product_id))
    if (product) {
      if (item.unit_price === 0) {
        item.unit_price = product.selling_price || 0
      }
    }
  }
}

const calculateLineTotal = (item: LineItem) => {
  const quantity = item.quantity || 0
  const unitPrice = item.unit_price || 0
  const discountPercent = item.discount_percent || 0
  const taxPercent = item.tax_percent || 0
  
  const subtotal = quantity * unitPrice
  const discountAmount = subtotal * (discountPercent / 100)
  const afterDiscount = subtotal - discountAmount
  const taxAmount = afterDiscount * (taxPercent / 100)
  
  return afterDiscount + taxAmount
}

const calculateSubtotal = () => {
  return form.items.reduce((sum, item) => {
    const quantity = item.quantity || 0
    const unitPrice = item.unit_price || 0
    return sum + (quantity * unitPrice)
  }, 0)
}

const calculateDiscountAmount = () => {
  return form.items.reduce((sum, item) => {
    const quantity = item.quantity || 0
    const unitPrice = item.unit_price || 0
    const discountPercent = item.discount_percent || 0
    
    const subtotal = quantity * unitPrice
    const discountAmount = subtotal * (discountPercent / 100)
    
    return sum + discountAmount
  }, 0)
}

const calculateTaxAmount = () => {
  return form.items.reduce((sum, item) => {
    const quantity = item.quantity || 0
    const unitPrice = item.unit_price || 0
    const discountPercent = item.discount_percent || 0
    const taxPercent = item.tax_percent || 0
    
    const subtotal = quantity * unitPrice
    const discountAmount = subtotal * (discountPercent / 100)
    const afterDiscount = subtotal - discountAmount
    const taxAmount = afterDiscount * (taxPercent / 100)
    
    return sum + taxAmount
  }, 0)
}

const calculateTotal = () => {
  return calculateSubtotal() - calculateDiscountAmount() + calculateTaxAmount()
}

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: form.currency || 'USD'
  }).format(amount)
}

const handleSubmit = async () => {
  Object.keys(errors).forEach(key => delete errors[key])
  error.value = null
  
  if (form.items.length === 0) {
    error.value = 'Please add at least one item to the sales order.'
    return
  }
  
  loading.value = true

  try {
    const payload = {
      customer_id: Number(form.customer_id),
      order_date: form.order_date,
      delivery_date: form.delivery_date || undefined,
      currency: form.currency || undefined,
      notes: form.notes || undefined,
      items: form.items.map(item => ({
        product_id: Number(item.product_id),
        quantity: item.quantity,
        unit_price: item.unit_price,
        discount_percent: item.discount_percent || 0,
        tax_percent: item.tax_percent || 0,
      })),
    }

    if (isEdit.value) {
      await salesApi.updateOrder(orderId.value, payload)
    } else {
      await salesApi.createOrder(payload)
    }

    router.push({ name: 'sales-orders' })
  } catch (err: any) {
    console.error('Failed to save sales order:', err)
    
    if (err.response?.data?.errors) {
      Object.assign(errors, err.response.data.errors)
    }
    
    error.value = err.response?.data?.message || err.message || 'Failed to save sales order. Please try again.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'sales-orders' })
}

onMounted(async () => {
  await Promise.all([
    fetchCustomers(),
    fetchProducts(),
  ])
  
  if (isEdit.value) {
    await fetchOrder()
  }
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
