<template>
  <div class="invoice-form">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          {{ isEdit ? 'Edit Invoice' : 'Create Invoice' }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          {{ isEdit ? 'Update invoice information' : 'Create a new invoice for your customer' }}
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
          Invoice Information
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
              <option :value="undefined">
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

          <!-- Currency -->
          <div>
            <label
              for="currency"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Currency
            </label>
            <select
              id="currency"
              v-model="form.currency"
              class="input"
            >
              <option value="USD">
                USD
              </option>
              <option value="EUR">
                EUR
              </option>
              <option value="GBP">
                GBP
              </option>
            </select>
          </div>

          <!-- Invoice Date -->
          <div>
            <label
              for="invoice_date"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Invoice Date <span class="text-red-500">*</span>
            </label>
            <input
              id="invoice_date"
              v-model="form.invoice_date"
              type="date"
              required
              class="input"
              :class="{ 'border-red-500': errors.invoice_date }"
            >
            <p
              v-if="errors.invoice_date"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.invoice_date }}
            </p>
          </div>

          <!-- Due Date -->
          <div>
            <label
              for="due_date"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Due Date <span class="text-red-500">*</span>
            </label>
            <input
              id="due_date"
              v-model="form.due_date"
              type="date"
              required
              class="input"
              :class="{ 'border-red-500': errors.due_date }"
            >
            <p
              v-if="errors.due_date"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.due_date }}
            </p>
          </div>

          <!-- Payment Terms -->
          <div class="md:col-span-2">
            <label
              for="payment_terms"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Payment Terms
            </label>
            <input
              id="payment_terms"
              v-model="form.payment_terms"
              type="text"
              class="input"
              placeholder="e.g., Net 30, Due on Receipt"
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
              placeholder="Internal notes (not visible to customer)"
            />
          </div>

          <!-- Terms & Conditions -->
          <div class="md:col-span-2">
            <label
              for="terms_conditions"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Terms & Conditions
            </label>
            <textarea
              id="terms_conditions"
              v-model="form.terms_conditions"
              rows="4"
              class="input"
              placeholder="Terms and conditions (visible on invoice)"
            />
          </div>
        </div>
      </div>

      <!-- Line Items -->
      <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">
            Invoice Items
          </h2>
          <button
            type="button"
            class="btn-secondary"
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

        <!-- Items Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Product (Optional)
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Description <span class="text-red-500">*</span>
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  Qty <span class="text-red-500">*</span>
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  Unit Price <span class="text-red-500">*</span>
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  Discount %
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  Tax %
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  Line Total
                </th>
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="(item, index) in form.items"
                :key="index"
              >
                <!-- Product -->
                <td class="px-3 py-3">
                  <select
                    v-model="item.product_id"
                    class="input w-32"
                    @change="onProductChange(index)"
                  >
                    <option :value="undefined">
                      None
                    </option>
                    <option
                      v-for="product in products"
                      :key="product.id"
                      :value="product.id"
                    >
                      {{ product.name }}
                    </option>
                  </select>
                </td>

                <!-- Description -->
                <td class="px-3 py-3">
                  <input
                    v-model="item.description"
                    type="text"
                    required
                    class="input min-w-[200px]"
                    :class="{ 'border-red-500': errors[`items.${index}.description`] }"
                    placeholder="Item description"
                  >
                  <p
                    v-if="errors[`items.${index}.description`]"
                    class="mt-1 text-xs text-red-600"
                  >
                    {{ errors[`items.${index}.description`] }}
                  </p>
                </td>

                <!-- Quantity -->
                <td class="px-3 py-3">
                  <input
                    v-model.number="item.quantity"
                    type="number"
                    step="1"
                    min="1"
                    required
                    class="input w-20 text-right"
                    :class="{ 'border-red-500': errors[`items.${index}.quantity`] }"
                    @input="calculateLineTotal(index)"
                  >
                  <p
                    v-if="errors[`items.${index}.quantity`]"
                    class="mt-1 text-xs text-red-600"
                  >
                    {{ errors[`items.${index}.quantity`] }}
                  </p>
                </td>

                <!-- Unit Price -->
                <td class="px-3 py-3">
                  <input
                    v-model.number="item.unit_price"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    class="input w-28 text-right"
                    :class="{ 'border-red-500': errors[`items.${index}.unit_price`] }"
                    @input="calculateLineTotal(index)"
                  >
                  <p
                    v-if="errors[`items.${index}.unit_price`]"
                    class="mt-1 text-xs text-red-600"
                  >
                    {{ errors[`items.${index}.unit_price`] }}
                  </p>
                </td>

                <!-- Discount % -->
                <td class="px-3 py-3">
                  <input
                    v-model.number="item.discount_percent"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    class="input w-20 text-right"
                    @input="calculateLineTotal(index)"
                  >
                </td>

                <!-- Tax % -->
                <td class="px-3 py-3">
                  <input
                    v-model.number="item.tax_percent"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    class="input w-20 text-right"
                    @input="calculateLineTotal(index)"
                  >
                </td>

                <!-- Line Total (readonly) -->
                <td class="px-3 py-3">
                  <input
                    :value="formatCurrency(item.line_total)"
                    type="text"
                    readonly
                    class="input w-28 text-right bg-gray-50"
                  >
                </td>

                <!-- Actions -->
                <td class="px-3 py-3 text-center">
                  <button
                    type="button"
                    class="text-red-600 hover:text-red-900"
                    :disabled="form.items.length <= 1"
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
            </tbody>
          </table>
        </div>

        <!-- Totals Section -->
        <div class="mt-6 flex justify-end">
          <div class="w-full max-w-md space-y-3">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Subtotal:</span>
              <span class="font-medium text-gray-900">{{ formatCurrency(totals.subtotal) }}</span>
            </div>
            <div
              v-if="totals.totalDiscount > 0"
              class="flex justify-between text-sm"
            >
              <span class="text-gray-600">Total Discount:</span>
              <span class="font-medium text-red-600">-{{ formatCurrency(totals.totalDiscount) }}</span>
            </div>
            <div
              v-if="totals.totalTax > 0"
              class="flex justify-between text-sm"
            >
              <span class="text-gray-600">Total Tax:</span>
              <span class="font-medium text-gray-900">{{ formatCurrency(totals.totalTax) }}</span>
            </div>
            <div class="flex justify-between text-base font-semibold pt-3 border-t border-gray-200">
              <span class="text-gray-900">Grand Total:</span>
              <span class="text-gray-900">{{ formatCurrency(totals.grandTotal) }}</span>
            </div>
          </div>
        </div>

        <!-- Minimum items message -->
        <p class="mt-4 text-sm text-gray-600 text-right">
          Minimum 1 line item required
        </p>
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
          :disabled="loading || form.items.length < 1"
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
            {{ isEdit ? 'Update Invoice' : 'Create Invoice' }}
          </span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { accountingApi } from '@/api/accounting'
import { salesApi } from '@/api/sales'
import { inventoryApi } from '@/api/inventory'
import type { Invoice } from '@/types/accounting'
import type { Customer } from '@/api/sales'
import type { Product } from '@/types/inventory'

const router = useRouter()
const route = useRoute()

const loading = ref(false)
const loadingData = ref(false)
const error = ref<string | null>(null)
const errors = reactive<Record<string, string>>({})
const customers = ref<Customer[]>([])
const products = ref<Product[]>([])

const isEdit = computed(() => !!route.params.id)
const invoiceId = computed(() => route.params.id as string)

interface LineItem {
  product_id?: number
  description: string
  quantity: number
  unit_price: number
  discount_percent: number
  tax_percent: number
  line_total: number
}

const form = reactive<{
  customer_id: number | undefined
  invoice_date: string
  due_date: string
  currency: string
  payment_terms?: string
  notes?: string
  terms_conditions?: string
  items: LineItem[]
}>({
  customer_id: undefined,
  invoice_date: new Date().toISOString().split('T')[0],
  due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  currency: 'USD',
  payment_terms: '',
  notes: '',
  terms_conditions: '',
  items: [
    { 
      product_id: undefined,
      description: '',
      quantity: 1,
      unit_price: 0,
      discount_percent: 0,
      tax_percent: 0,
      line_total: 0
    }
  ]
})

const totals = computed(() => {
  let subtotal = 0
  let totalDiscount = 0
  let totalTax = 0

  form.items.forEach(item => {
    const itemSubtotal = item.quantity * item.unit_price
    const discountAmount = itemSubtotal * (item.discount_percent / 100)
    const afterDiscount = itemSubtotal - discountAmount
    const taxAmount = afterDiscount * (item.tax_percent / 100)
    
    subtotal += itemSubtotal
    totalDiscount += discountAmount
    totalTax += taxAmount
  })

  const grandTotal = subtotal - totalDiscount + totalTax

  return {
    subtotal,
    totalDiscount,
    totalTax,
    grandTotal
  }
})

const fetchCustomers = async () => {
  try {
    const response = await salesApi.getCustomers({ per_page: 1000 })
    customers.value = response.data
  } catch (err: any) {
    console.error('Failed to fetch customers:', err)
    error.value = 'Failed to load customers. Please try again.'
  }
}

const fetchProducts = async () => {
  try {
    const response = await inventoryApi.getProducts({ 
      per_page: 1000,
      is_active: true 
    })
    products.value = response.data
  } catch (err: any) {
    console.error('Failed to fetch products:', err)
    // Non-critical error - products are optional for invoice items
  }
}

const fetchInvoice = async () => {
  if (!isEdit.value) return

  loadingData.value = true
  error.value = null
  
  try {
    const invoice = await accountingApi.getInvoice(invoiceId.value)
    
    // Populate form with invoice data
    form.customer_id = invoice.customer_id
    form.invoice_date = invoice.invoice_date || new Date().toISOString().split('T')[0]
    form.due_date = invoice.due_date || new Date().toISOString().split('T')[0]
    form.currency = invoice.currency || 'USD'
    form.payment_terms = invoice.payment_terms || ''
    form.notes = invoice.notes || ''
    form.terms_conditions = invoice.terms_conditions || ''
    
    // Populate items
    if (invoice.items && invoice.items.length > 0) {
      form.items = invoice.items.map(item => ({
        product_id: item.product_id,
        description: item.description || '',
        quantity: item.quantity || 1,
        unit_price: item.unit_price || 0,
        discount_percent: item.discount_percent || 0,
        tax_percent: item.tax_percent || 0,
        line_total: item.line_total || 0
      }))
    }
  } catch (err: any) {
    console.error('Failed to fetch invoice:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load invoice data.'
  } finally {
    loadingData.value = false
  }
}

const addLineItem = () => {
  form.items.push({
    product_id: undefined,
    description: '',
    quantity: 1,
    unit_price: 0,
    discount_percent: 0,
    tax_percent: 0,
    line_total: 0
  })
}

const removeLineItem = (index: number) => {
  if (form.items.length > 1) {
    form.items.splice(index, 1)
  }
}

const onProductChange = (index: number) => {
  const item = form.items[index]
  if (item.product_id) {
    const product = products.value.find(p => p.id === item.product_id)
    if (product) {
      // Use product description and standard selling price
      // Note: Special pricing, volume discounts, or customer-specific pricing
      // should be handled by business logic on the backend after submission
      item.description = product.description || product.name
      item.unit_price = product.selling_price || 0
      calculateLineTotal(index)
    }
  }
}

const calculateLineTotal = (index: number) => {
  const item = form.items[index]
  
  // Calculate amounts
  const subtotal = item.quantity * item.unit_price
  const discountAmount = subtotal * (item.discount_percent / 100)
  const afterDiscount = subtotal - discountAmount
  const taxAmount = afterDiscount * (item.tax_percent / 100)
  
  // Line total = subtotal - discount + tax
  item.line_total = afterDiscount + taxAmount
  
  // Clear errors for this item
  delete errors[`items.${index}.quantity`]
  delete errors[`items.${index}.unit_price`]
}

const validateForm = (): boolean => {
  // Clear previous errors
  Object.keys(errors).forEach(key => delete errors[key])
  let isValid = true

  // Validate basic fields
  if (!form.customer_id) {
    errors.customer_id = 'Customer is required'
    isValid = false
  }

  if (!form.invoice_date) {
    errors.invoice_date = 'Invoice date is required'
    isValid = false
  }

  if (!form.due_date) {
    errors.due_date = 'Due date is required'
    isValid = false
  }

  // Validate items
  if (form.items.length < 1) {
    error.value = 'At least 1 line item is required'
    isValid = false
  }

  form.items.forEach((item, index) => {
    if (!item.description) {
      errors[`items.${index}.description`] = 'Required'
      isValid = false
    }

    if (!item.quantity || item.quantity < 1) {
      errors[`items.${index}.quantity`] = 'Must be at least 1'
      isValid = false
    }

    if (item.unit_price === undefined || item.unit_price < 0) {
      errors[`items.${index}.unit_price`] = 'Must be 0 or greater'
      isValid = false
    }
  })

  return isValid
}

const handleSubmit = async () => {
  // Clear previous errors
  error.value = null

  // Validate form
  if (!validateForm()) {
    return
  }

  loading.value = true

  try {
    const payload = {
      customer_id: form.customer_id!,
      invoice_date: form.invoice_date,
      due_date: form.due_date,
      currency: form.currency,
      payment_terms: form.payment_terms || undefined,
      notes: form.notes || undefined,
      terms_conditions: form.terms_conditions || undefined,
      items: form.items.map(item => ({
        product_id: item.product_id || undefined,
        description: item.description,
        quantity: item.quantity,
        unit_price: item.unit_price,
        discount_percent: item.discount_percent || 0,
        tax_percent: item.tax_percent || 0
      }))
    }

    if (isEdit.value) {
      await accountingApi.updateInvoice(invoiceId.value, payload)
    } else {
      await accountingApi.createInvoice(payload)
    }

    // Navigate back to invoices list
    router.push({ name: 'accounting-invoices' })
  } catch (err: any) {
    console.error('Failed to save invoice:', err)
    
    // Handle validation errors
    if (err.response?.data?.errors) {
      Object.assign(errors, err.response.data.errors)
    }
    
    error.value = err.response?.data?.message || err.message || 'Failed to save invoice. Please try again.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'accounting-invoices' })
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: form.currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)
}

onMounted(async () => {
  await fetchCustomers()
  await fetchProducts()
  if (isEdit.value) {
    await fetchInvoice()
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
