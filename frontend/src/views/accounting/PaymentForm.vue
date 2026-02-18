<template>
  <div class="payment-form">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          {{ isEdit ? 'Edit Payment' : 'Create Payment' }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          {{ isEdit ? 'Update payment information' : 'Create a new payment and optionally allocate to invoices' }}
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
          Payment Information
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Payment Date -->
          <div>
            <label
              for="payment_date"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Payment Date <span class="text-red-500">*</span>
            </label>
            <input
              id="payment_date"
              v-model="form.payment_date"
              type="date"
              required
              class="input"
              :class="{ 'border-red-500': errors.payment_date }"
            >
            <p
              v-if="errors.payment_date"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.payment_date }}
            </p>
          </div>

          <!-- Amount -->
          <div>
            <label
              for="amount"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Amount <span class="text-red-500">*</span>
            </label>
            <input
              id="amount"
              v-model.number="form.amount"
              type="number"
              step="0.01"
              min="0"
              required
              class="input"
              :class="{ 'border-red-500': errors.amount }"
              placeholder="0.00"
            >
            <p
              v-if="errors.amount"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.amount }}
            </p>
          </div>

          <!-- Payment Method -->
          <div>
            <label
              for="payment_method"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Payment Method <span class="text-red-500">*</span>
            </label>
            <select
              id="payment_method"
              v-model="form.payment_method"
              required
              class="input"
              :class="{ 'border-red-500': errors.payment_method }"
            >
              <option value="">
                Select payment method
              </option>
              <option value="cash">
                Cash
              </option>
              <option value="check">
                Check
              </option>
              <option value="credit_card">
                Credit Card
              </option>
              <option value="debit_card">
                Debit Card
              </option>
              <option value="bank_transfer">
                Bank Transfer
              </option>
              <option value="online">
                Online
              </option>
              <option value="mobile">
                Mobile
              </option>
              <option value="other">
                Other
              </option>
            </select>
            <p
              v-if="errors.payment_method"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.payment_method }}
            </p>
          </div>

          <!-- Reference -->
          <div>
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
              placeholder="e.g., Check #1234"
            >
          </div>

          <!-- Transaction ID -->
          <div class="md:col-span-2">
            <label
              for="transaction_id"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Transaction ID
            </label>
            <input
              id="transaction_id"
              v-model="form.transaction_id"
              type="text"
              class="input"
              placeholder="External transaction reference"
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
              placeholder="Additional notes about this payment"
            />
          </div>
        </div>
      </div>

      <!-- Allocations Section -->
      <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">
              Payment Allocations (Optional)
            </h2>
            <p class="text-sm text-gray-600 mt-1">
              Allocate this payment to one or more invoices
            </p>
          </div>
          <button
            type="button"
            class="btn-secondary"
            @click="addAllocation"
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
            Add Allocation
          </button>
        </div>

        <!-- Allocations Table -->
        <div
          v-if="form.allocations.length > 0"
          class="overflow-x-auto mb-4"
        >
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Invoice <span class="text-red-500">*</span>
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  Invoice Balance
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  Allocated Amount <span class="text-red-500">*</span>
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Notes
                </th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="(allocation, index) in form.allocations"
                :key="index"
              >
                <!-- Invoice -->
                <td class="px-4 py-3">
                  <select
                    v-model="allocation.invoice_id"
                    required
                    class="input min-w-[200px]"
                    :class="{ 'border-red-500': errors[`allocations.${index}.invoice_id`] }"
                    @change="onInvoiceChange(index)"
                  >
                    <option :value="undefined">
                      Select an invoice
                    </option>
                    <option
                      v-for="invoice in availableInvoices"
                      :key="invoice.id"
                      :value="invoice.id"
                    >
                      {{ invoice.invoice_number }} - {{ invoice.customer_name }}
                    </option>
                  </select>
                  <p
                    v-if="errors[`allocations.${index}.invoice_id`]"
                    class="mt-1 text-xs text-red-600"
                  >
                    {{ errors[`allocations.${index}.invoice_id`] }}
                  </p>
                </td>

                <!-- Invoice Balance -->
                <td class="px-4 py-3 text-sm text-right text-gray-600">
                  {{ getInvoiceBalance(allocation.invoice_id) }}
                </td>

                <!-- Allocated Amount -->
                <td class="px-4 py-3">
                  <input
                    v-model.number="allocation.amount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    :max="getMaxAllocation(allocation.invoice_id)"
                    required
                    class="input w-32 text-right"
                    :class="{ 'border-red-500': errors[`allocations.${index}.amount`] }"
                    placeholder="0.00"
                  >
                  <p
                    v-if="errors[`allocations.${index}.amount`]"
                    class="mt-1 text-xs text-red-600"
                  >
                    {{ errors[`allocations.${index}.amount`] }}
                  </p>
                </td>

                <!-- Notes -->
                <td class="px-4 py-3">
                  <input
                    v-model="allocation.notes"
                    type="text"
                    class="input min-w-[150px]"
                    placeholder="Optional notes"
                  >
                </td>

                <!-- Actions -->
                <td class="px-4 py-3 text-center">
                  <button
                    type="button"
                    class="text-red-600 hover:text-red-900"
                    @click="removeAllocation(index)"
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

        <!-- Allocation Summary -->
        <div
          v-if="form.allocations.length > 0"
          class="mt-4 bg-gray-50 rounded-lg p-4"
        >
          <div class="space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Payment Amount:</span>
              <span class="font-medium text-gray-900">{{ formatCurrency(form.amount || 0) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Total Allocated:</span>
              <span class="font-medium text-gray-900">{{ formatCurrency(totalAllocated) }}</span>
            </div>
            <div
              class="flex justify-between text-sm pt-2 border-t border-gray-300"
              :class="{ 'text-red-600': remainingAmount < 0, 'text-green-600': remainingAmount > 0 }"
            >
              <span class="font-semibold">{{ remainingAmount >= 0 ? 'Unallocated:' : 'Over-allocated:' }}</span>
              <span class="font-semibold">{{ formatCurrency(Math.abs(remainingAmount)) }}</span>
            </div>
          </div>
          <p
            v-if="remainingAmount < 0"
            class="mt-2 text-xs text-red-600"
          >
            Total allocations cannot exceed payment amount
          </p>
        </div>

        <!-- No allocations message -->
        <div
          v-if="form.allocations.length === 0"
          class="text-center py-8 text-gray-500"
        >
          <p class="text-sm">
            No allocations added yet. Click "Add Allocation" to allocate this payment to invoices.
          </p>
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
          :disabled="loading || !isFormValid"
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
            {{ isEdit ? 'Update Payment' : 'Create Payment' }}
          </span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { accountingApi } from '@/api/accounting'
import type { Payment, Invoice, PaymentMethod } from '@/types/accounting'

const router = useRouter()
const route = useRoute()

const loading = ref(false)
const loadingData = ref(false)
const error = ref<string | null>(null)
const errors = reactive<Record<string, string>>({})
const availableInvoices = ref<Invoice[]>([])

const isEdit = computed(() => !!route.params.id)
const paymentId = computed(() => route.params.id as string)

interface AllocationItem {
  invoice_id: number | undefined
  amount: number
  notes?: string
}

const form = reactive<{
  payment_date: string
  amount: number
  payment_method: PaymentMethod | ''
  reference?: string
  transaction_id?: string
  notes?: string
  allocations: AllocationItem[]
}>({
  payment_date: new Date().toISOString().split('T')[0],
  amount: 0,
  payment_method: '',
  reference: '',
  transaction_id: '',
  notes: '',
  allocations: []
})

const totalAllocated = computed(() => {
  return form.allocations.reduce((sum, allocation) => sum + (allocation.amount || 0), 0)
})

const remainingAmount = computed(() => {
  return (form.amount || 0) - totalAllocated.value
})

const isFormValid = computed(() => {
  if (!form.payment_date || !form.amount || !form.payment_method) return false
  if (form.amount < 0) return false
  if (remainingAmount.value < 0) return false
  
  for (const allocation of form.allocations) {
    if (!allocation.invoice_id || !allocation.amount || allocation.amount <= 0) {
      return false
    }
  }
  
  return true
})

const fetchInvoices = async () => {
  try {
    // Note: We start with 'sent' status as an initial filter to reduce dataset size.
    // The client-side filter below then expands to include 'partial' and 'overdue' statuses,
    // and ensures only invoices with outstanding balance (balance > 0) are shown.
    const response = await accountingApi.getInvoices({ 
      per_page: 1000,
      status: 'sent'
    })
    
    // Filter for invoices with outstanding balance (unpaid/partially paid)
    const invoicesWithBalance = response.data.filter(inv => 
      ['sent', 'partial', 'overdue'].includes(inv.status) && inv.balance > 0
    )
    
    availableInvoices.value = invoicesWithBalance
  } catch (err: any) {
    console.error('Failed to fetch invoices:', err)
    error.value = 'Failed to load invoices. Please try again.'
  }
}

const fetchPayment = async () => {
  if (!isEdit.value) return

  loadingData.value = true
  error.value = null
  
  try {
    const payment = await accountingApi.getPayment(paymentId.value)
    
    form.payment_date = payment.payment_date || new Date().toISOString().split('T')[0]
    form.amount = payment.amount || 0
    form.payment_method = payment.payment_method || ''
    form.reference = payment.reference || ''
    form.transaction_id = payment.transaction_id || ''
    form.notes = payment.notes || ''
    
    if (payment.allocations && payment.allocations.length > 0) {
      form.allocations = payment.allocations.map(allocation => ({
        invoice_id: allocation.invoice_id,
        amount: allocation.amount,
        notes: allocation.notes || ''
      }))
    }
  } catch (err: any) {
    console.error('Failed to fetch payment:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load payment data.'
  } finally {
    loadingData.value = false
  }
}

const addAllocation = () => {
  form.allocations.push({
    invoice_id: undefined,
    amount: 0,
    notes: ''
  })
}

const removeAllocation = (index: number) => {
  form.allocations.splice(index, 1)
  delete errors[`allocations.${index}.invoice_id`]
  delete errors[`allocations.${index}.amount`]
}

const onInvoiceChange = (index: number) => {
  const allocation = form.allocations[index]
  if (allocation.invoice_id) {
    const invoice = availableInvoices.value.find(inv => inv.id === allocation.invoice_id)
    if (invoice && allocation.amount === 0) {
      allocation.amount = Math.min(invoice.balance, remainingAmount.value + (allocation.amount || 0))
    }
  }
  delete errors[`allocations.${index}.invoice_id`]
}

const getInvoiceBalance = (invoiceId: number | undefined): string => {
  if (!invoiceId) return '-'
  const invoice = availableInvoices.value.find(inv => inv.id === invoiceId)
  return invoice ? formatCurrency(invoice.balance) : '-'
}

const getMaxAllocation = (invoiceId: number | undefined): number => {
  if (!invoiceId) return 0
  const invoice = availableInvoices.value.find(inv => inv.id === invoiceId)
  return invoice ? invoice.balance : 0
}

const validateForm = (): boolean => {
  Object.keys(errors).forEach(key => delete errors[key])
  let isValid = true

  if (!form.payment_date) {
    errors.payment_date = 'Payment date is required'
    isValid = false
  }

  if (!form.amount || form.amount <= 0) {
    errors.amount = 'Amount must be greater than 0'
    isValid = false
  }

  if (!form.payment_method) {
    errors.payment_method = 'Payment method is required'
    isValid = false
  }

  if (remainingAmount.value < 0) {
    error.value = 'Total allocations cannot exceed payment amount'
    isValid = false
  }

  form.allocations.forEach((allocation, index) => {
    if (!allocation.invoice_id) {
      errors[`allocations.${index}.invoice_id`] = 'Required'
      isValid = false
    }

    if (!allocation.amount || allocation.amount <= 0) {
      errors[`allocations.${index}.amount`] = 'Must be greater than 0'
      isValid = false
    }

    const maxAllocation = getMaxAllocation(allocation.invoice_id)
    if (allocation.amount > maxAllocation) {
      errors[`allocations.${index}.amount`] = `Max: ${formatCurrency(maxAllocation)}`
      isValid = false
    }
  })

  return isValid
}

const handleSubmit = async () => {
  error.value = null

  if (!validateForm()) {
    return
  }

  loading.value = true

  try {
    const payload = {
      payment_date: form.payment_date,
      amount: form.amount,
      payment_method: form.payment_method as PaymentMethod,
      reference: form.reference || undefined,
      transaction_id: form.transaction_id || undefined,
      notes: form.notes || undefined,
      allocations: form.allocations.length > 0 ? form.allocations.map(allocation => ({
        invoice_id: allocation.invoice_id!,
        amount: allocation.amount,
        notes: allocation.notes || undefined
      })) : undefined
    }

    if (isEdit.value) {
      await accountingApi.updatePayment(paymentId.value, payload)
    } else {
      await accountingApi.createPayment(payload)
    }

    router.push({ name: 'accounting-payments' })
  } catch (err: any) {
    console.error('Failed to save payment:', err)
    
    if (err.response?.data?.errors) {
      Object.assign(errors, err.response.data.errors)
    }
    
    error.value = err.response?.data?.message || err.message || 'Failed to save payment. Please try again.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'accounting-payments' })
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)
}

onMounted(async () => {
  await fetchInvoices()
  if (isEdit.value) {
    await fetchPayment()
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
