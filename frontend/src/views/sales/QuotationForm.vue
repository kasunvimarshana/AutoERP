<template>
  <div class="quotation-form">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          {{ isEdit ? 'Edit Quotation' : 'Create Quotation' }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          {{ isEdit ? 'Update quotation information' : 'Create a new sales quotation' }}
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

          <!-- Quote Date -->
          <div>
            <label
              for="quote_date"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Quote Date <span class="text-red-500">*</span>
            </label>
            <input
              id="quote_date"
              v-model="form.quote_date"
              type="date"
              required
              class="input"
              :class="{ 'border-red-500': errors.quote_date }"
            >
            <p
              v-if="errors.quote_date"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.quote_date }}
            </p>
          </div>

          <!-- Valid Until -->
          <div>
            <label
              for="valid_until"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Valid Until
            </label>
            <input
              id="valid_until"
              v-model="form.valid_until"
              type="date"
              class="input"
              :min="form.quote_date"
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

        <!-- Terms and Conditions -->
        <div class="mt-6">
          <label
            for="terms_and_conditions"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Terms and Conditions
          </label>
          <textarea
            id="terms_and_conditions"
            v-model="form.terms_and_conditions"
            rows="3"
            class="input"
            placeholder="Enter terms and conditions"
          />
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
                  Description
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
                <!-- Description -->
                <td class="px-3 py-4">
                  <input
                    v-model="item.description"
                    type="text"
                    class="input"
                    placeholder="Description"
                  >
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
                  colspan="8"
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
            {{ isEdit ? 'Update Quotation' : 'Create Quotation' }}
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
const quotationId = computed(() => route.params.id as string)

interface LineItem {
  product_id: number | string
  description: string
  quantity: number
  unit_price: number
  discount_percent: number
  tax_percent: number
}

const form = reactive({
  customer_id: '',
  quote_date: new Date().toISOString().split('T')[0],
  valid_until: '',
  currency: 'USD',
  terms_and_conditions: '',
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

const fetchQuotation = async () => {
  if (!isEdit.value) return

  loading.value = true
  error.value = null
  
  try {
    const quotation = await salesApi.getQuotation(quotationId.value)
    
    form.customer_id = String(quotation.customer_id)
    form.quote_date = quotation.quote_date.split('T')[0]
    form.valid_until = quotation.valid_until ? quotation.valid_until.split('T')[0] : ''
    form.currency = quotation.currency || 'USD'
    form.terms_and_conditions = quotation.terms_and_conditions || ''
    form.notes = quotation.notes || ''
    
    form.items = quotation.items.map(item => ({
      product_id: item.product_id,
      description: item.description || '',
      quantity: item.quantity,
      unit_price: item.unit_price,
      discount_percent: item.discount_percent || 0,
      tax_percent: item.tax_percent || 0,
    }))
  } catch (err: any) {
    console.error('Failed to fetch quotation:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load quotation data.'
  } finally {
    loading.value = false
  }
}

const addLineItem = () => {
  form.items.push({
    product_id: '',
    description: '',
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
      if (!item.description) {
        item.description = product.description || ''
      }
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
    error.value = 'Please add at least one item to the quotation.'
    return
  }
  
  loading.value = true

  try {
    const payload = {
      customer_id: Number(form.customer_id),
      quote_date: form.quote_date,
      valid_until: form.valid_until || undefined,
      currency: form.currency || undefined,
      terms_and_conditions: form.terms_and_conditions || undefined,
      notes: form.notes || undefined,
      items: form.items.map(item => ({
        product_id: Number(item.product_id),
        description: item.description || undefined,
        quantity: item.quantity,
        unit_price: item.unit_price,
        discount_percent: item.discount_percent || 0,
        tax_percent: item.tax_percent || 0,
      })),
    }

    if (isEdit.value) {
      await salesApi.updateQuotation(quotationId.value, payload)
    } else {
      await salesApi.createQuotation(payload)
    }

    router.push({ name: 'sales-quotations' })
  } catch (err: any) {
    console.error('Failed to save quotation:', err)
    
    if (err.response?.data?.errors) {
      Object.assign(errors, err.response.data.errors)
    }
    
    error.value = err.response?.data?.message || err.message || 'Failed to save quotation. Please try again.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'sales-quotations' })
}

onMounted(async () => {
  await Promise.all([
    fetchCustomers(),
    fetchProducts(),
  ])
  
  if (isEdit.value) {
    await fetchQuotation()
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
