<template>
  <div class="payment-detail">
    <!-- Loading State -->
    <div
      v-if="loading"
      class="text-center py-12"
    >
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent" />
      <p class="mt-4 text-gray-600">
        Loading payment details...
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

    <!-- Payment Details -->
    <div v-else-if="payment">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div class="flex-1">
          <div class="flex items-center space-x-4">
            <h1 class="text-2xl font-bold text-gray-900">
              Payment {{ payment.payment_number }}
            </h1>
            <span
              :class="getStatusClass(payment.status)"
              class="rounded-full px-3 py-1 text-sm font-medium"
            >
              {{ formatStatus(payment.status) }}
            </span>
          </div>
          <p class="mt-1 text-sm text-gray-600">
            {{ formatPaymentMethod(payment.payment_method) }} - {{ formatCurrency(payment.amount) }}
          </p>
        </div>
        <div class="flex items-center space-x-3">
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
          <button
            v-if="payment.status === 'pending' && hasPermission('accounting.payments.update')"
            class="btn-primary"
            @click="editPayment"
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
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
              />
            </svg>
            Edit
          </button>
          <button
            v-if="['pending', 'completed'].includes(payment.status) && hasPermission('accounting.payments.allocate')"
            class="btn-primary"
            @click="showAllocationForm = true"
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
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
              />
            </svg>
            Allocate
          </button>
          <button
            v-if="payment.status === 'pending' && hasPermission('accounting.payments.complete')"
            class="btn-success"
            @click="confirmComplete"
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
                d="M5 13l4 4L19 7"
              />
            </svg>
            Complete
          </button>
          <button
            v-if="['pending', 'processing'].includes(payment.status) && hasPermission('accounting.payments.cancel')"
            class="btn-warning"
            @click="confirmCancel"
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
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
            Cancel
          </button>
          <button
            v-if="payment.status === 'pending' && hasPermission('accounting.payments.delete')"
            class="btn-danger"
            @click="confirmDelete"
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
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
              />
            </svg>
            Delete
          </button>
        </div>
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Information -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Payment Information Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Payment Information
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Payment Number
                </dt>
                <dd class="mt-1 text-sm text-gray-900 font-mono">
                  {{ payment.payment_number }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Payment Date
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDate(payment.payment_date) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Amount
                </dt>
                <dd class="mt-1 text-sm text-gray-900 font-semibold">
                  {{ formatCurrency(payment.amount) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Payment Method
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatPaymentMethod(payment.payment_method) }}
                </dd>
              </div>
              <div v-if="payment.reference">
                <dt class="text-sm font-medium text-gray-500">
                  Reference
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ payment.reference }}
                </dd>
              </div>
              <div v-if="payment.transaction_id">
                <dt class="text-sm font-medium text-gray-500">
                  Transaction ID
                </dt>
                <dd class="mt-1 text-sm text-gray-900 font-mono">
                  {{ payment.transaction_id }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Allocations Section -->
          <div
            v-if="payment.allocations && payment.allocations.length > 0"
            class="bg-white shadow-sm rounded-lg p-6"
          >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Payment Allocations
            </h2>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      Invoice Number
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                      Allocated Amount
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      Notes
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr
                    v-for="allocation in payment.allocations"
                    :key="allocation.id"
                  >
                    <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                      {{ allocation.invoice_number }}
                    </td>
                    <td class="px-4 py-3 text-sm text-right text-gray-900">
                      {{ formatCurrency(allocation.amount) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                      {{ allocation.notes || '-' }}
                    </td>
                  </tr>
                </tbody>
                <tfoot class="bg-gray-50">
                  <tr>
                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                      Total Allocated
                    </td>
                    <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">
                      {{ formatCurrency(totalAllocated) }}
                    </td>
                    <td class="px-4 py-3" />
                  </tr>
                  <tr v-if="unallocatedAmount > 0">
                    <td class="px-4 py-3 text-sm font-semibold text-orange-700">
                      Unallocated Amount
                    </td>
                    <td class="px-4 py-3 text-sm text-right font-semibold text-orange-700">
                      {{ formatCurrency(unallocatedAmount) }}
                    </td>
                    <td class="px-4 py-3" />
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <!-- Unallocated Message -->
          <div
            v-else
            class="bg-yellow-50 border-l-4 border-yellow-400 p-4"
          >
            <div class="flex">
              <div class="flex-shrink-0">
                <svg
                  class="h-5 w-5 text-yellow-400"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-yellow-700">
                  This payment has not been allocated to any invoices yet.
                </p>
              </div>
            </div>
          </div>

          <!-- Notes Section -->
          <div
            v-if="payment.notes"
            class="bg-white shadow-sm rounded-lg p-6"
          >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Notes
            </h2>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">
              {{ payment.notes }}
            </p>
          </div>
        </div>

        <!-- Right Column - Timeline & Actions -->
        <div class="space-y-6">
          <!-- Quick Actions Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Quick Actions
            </h2>
            <div class="space-y-2">
              <button
                v-if="['pending', 'completed'].includes(payment.status) && hasPermission('accounting.payments.allocate')"
                class="w-full btn-primary justify-center"
                @click="showAllocationForm = true"
              >
                Allocate to Invoices
              </button>
              <button
                v-if="payment.status === 'pending' && hasPermission('accounting.payments.complete')"
                class="w-full btn-success justify-center"
                @click="confirmComplete"
              >
                Complete Payment
              </button>
              <button
                v-if="['pending', 'processing'].includes(payment.status) && hasPermission('accounting.payments.cancel')"
                class="w-full btn-warning justify-center"
                @click="confirmCancel"
              >
                Cancel Payment
              </button>
              <button
                v-if="payment.status === 'pending' && hasPermission('accounting.payments.delete')"
                class="w-full btn-danger justify-center"
                @click="confirmDelete"
              >
                Delete Payment
              </button>
            </div>
          </div>

          <!-- Timeline Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Timeline
            </h2>
            <dl class="space-y-3">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Created
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDateTime(payment.created_at) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Last Updated
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDateTime(payment.updated_at) }}
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </div>

    <!-- Complete Confirmation Modal -->
    <ConfirmDialog
      v-if="showCompleteDialog"
      title="Complete Payment"
      message="Are you sure you want to mark this payment as completed?"
      confirm-text="Complete"
      cancel-text="Cancel"
      @confirm="completePayment"
      @cancel="showCompleteDialog = false"
    />

    <!-- Cancel Confirmation Modal -->
    <ConfirmDialog
      v-if="showCancelDialog"
      title="Cancel Payment"
      message="Are you sure you want to cancel this payment?"
      confirm-text="Cancel Payment"
      cancel-text="Go Back"
      @confirm="cancelPayment"
      @cancel="showCancelDialog = false"
    />

    <!-- Delete Confirmation Modal -->
    <ConfirmDialog
      v-if="showDeleteDialog"
      title="Delete Payment"
      message="Are you sure you want to delete this payment? This action cannot be undone."
      confirm-text="Delete"
      cancel-text="Cancel"
      @confirm="deletePayment"
      @cancel="showDeleteDialog = false"
    />

    <!-- Allocation Form Modal (placeholder) -->
    <div
      v-if="showAllocationForm"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click.self="showAllocationForm = false"
    >
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
            Allocate Payment
          </h3>
          <p class="text-sm text-gray-500 mb-4">
            Use the edit form to manage payment allocations.
          </p>
          <div class="flex justify-end space-x-3">
            <button
              class="btn-secondary"
              @click="showAllocationForm = false"
            >
              Close
            </button>
            <button
              class="btn-primary"
              @click="editPayment"
            >
              Go to Edit Form
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { accountingApi } from '@/api/accounting'
import type { Payment } from '@/types/accounting'
import { usePermissions } from '@/composables/usePermissions'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'

const router = useRouter()
const route = useRoute()
const { hasPermission } = usePermissions()

const loading = ref(false)
const error = ref<string | null>(null)
const payment = ref<Payment | null>(null)
const showCompleteDialog = ref(false)
const showCancelDialog = ref(false)
const showDeleteDialog = ref(false)
const showAllocationForm = ref(false)

const paymentId = route.params.id as string

const totalAllocated = computed(() => {
  if (!payment.value?.allocations) return 0
  return payment.value.allocations.reduce((sum, allocation) => sum + allocation.amount, 0)
})

const unallocatedAmount = computed(() => {
  if (!payment.value) return 0
  return payment.value.amount - totalAllocated.value
})

const fetchPayment = async () => {
  loading.value = true
  error.value = null
  
  try {
    payment.value = await accountingApi.getPayment(paymentId)
  } catch (err: any) {
    console.error('Failed to fetch payment:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load payment details.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'accounting-payments' })
}

const editPayment = () => {
  router.push({ name: 'accounting-payment-edit', params: { id: paymentId } })
}

const confirmComplete = () => {
  showCompleteDialog.value = true
}

const completePayment = async () => {
  try {
    await accountingApi.completePayment(paymentId)
    showCompleteDialog.value = false
    await fetchPayment()
  } catch (err: any) {
    console.error('Failed to complete payment:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to complete payment.'
  }
}

const confirmCancel = () => {
  showCancelDialog.value = true
}

const cancelPayment = async () => {
  try {
    await accountingApi.cancelPayment(paymentId)
    showCancelDialog.value = false
    await fetchPayment()
  } catch (err: any) {
    console.error('Failed to cancel payment:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to cancel payment.'
  }
}

const confirmDelete = () => {
  showDeleteDialog.value = true
}

const deletePayment = async () => {
  try {
    await accountingApi.deletePayment(paymentId)
    showDeleteDialog.value = false
    router.push({ name: 'accounting-payments' })
  } catch (err: any) {
    console.error('Failed to delete payment:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to delete payment.'
  }
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800',
    refunded: 'bg-orange-100 text-orange-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatStatus = (status: string) => {
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const formatPaymentMethod = (method: string) => {
  return method.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatDateTime = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

onMounted(() => {
  fetchPayment()
})
</script>

<style scoped>
.btn-primary {
  @apply inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2;
}

.btn-secondary {
  @apply inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2;
}

.btn-success {
  @apply inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2;
}

.btn-warning {
  @apply inline-flex items-center rounded-md bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2;
}

.btn-danger {
  @apply inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2;
}
</style>
