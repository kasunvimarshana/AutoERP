<template>
  <div class="payment-list-view">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Payments
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          Manage customer payments and allocations
        </p>
      </div>
      <button
        v-if="hasPermission('accounting.payments.create')"
        class="btn-primary"
        @click="createPayment"
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
        New Payment
      </button>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white rounded-lg shadow-sm p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Search -->
        <div class="md:col-span-2">
          <label
            for="search"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Search
          </label>
          <input
            id="search"
            v-model="filters.search"
            type="text"
            class="input"
            placeholder="Search by payment number, reference..."
            @input="debouncedSearch"
          >
        </div>

        <!-- Status Filter -->
        <div>
          <label
            for="status"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Status
          </label>
          <select
            id="status"
            v-model="filters.status"
            class="input"
            @change="fetchPayments"
          >
            <option value="">
              All Statuses
            </option>
            <option value="pending">
              Pending
            </option>
            <option value="processing">
              Processing
            </option>
            <option value="completed">
              Completed
            </option>
            <option value="failed">
              Failed
            </option>
            <option value="cancelled">
              Cancelled
            </option>
            <option value="refunded">
              Refunded
            </option>
          </select>
        </div>

        <!-- Payment Method Filter -->
        <div>
          <label
            for="payment_method"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Payment Method
          </label>
          <select
            id="payment_method"
            v-model="filters.payment_method"
            class="input"
            @change="fetchPayments"
          >
            <option value="">
              All Methods
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
        </div>

        <!-- Payment Date From -->
        <div>
          <label
            for="date_from"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Payment Date From
          </label>
          <input
            id="date_from"
            v-model="filters.date_from"
            type="date"
            class="input"
            @change="fetchPayments"
          >
        </div>

        <!-- Payment Date To -->
        <div>
          <label
            for="date_to"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Payment Date To
          </label>
          <input
            id="date_to"
            v-model="filters.date_to"
            type="date"
            class="input"
            @change="fetchPayments"
          >
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div
      v-if="loading"
      class="text-center py-12"
    >
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent" />
      <p class="mt-4 text-gray-600">
        Loading payments...
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

    <!-- Table View -->
    <div
      v-else
      class="bg-white shadow-sm rounded-lg overflow-hidden"
    >
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Payment Number
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Payment Date
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Amount
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Payment Method
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Reference
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Status
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="payment in payments"
            :key="payment.id"
            class="hover:bg-gray-50 cursor-pointer"
            @click="viewPayment(payment.id)"
          >
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ payment.payment_number }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
              {{ formatDate(payment.payment_date) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
              {{ formatCurrency(payment.amount) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
              {{ formatPaymentMethod(payment.payment_method) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
              {{ payment.reference || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="getStatusClass(payment.status)"
                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
              >
                {{ formatStatus(payment.status) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button
                class="text-indigo-600 hover:text-indigo-900 mr-3"
                @click.stop="viewPayment(payment.id)"
              >
                View
              </button>
              <button
                v-if="payment.status === 'pending' && hasPermission('accounting.payments.update')"
                class="text-indigo-600 hover:text-indigo-900 mr-3"
                @click.stop="editPayment(payment.id)"
              >
                Edit
              </button>
              <button
                v-if="['pending', 'completed'].includes(payment.status) && hasPermission('accounting.payments.allocate')"
                class="text-blue-600 hover:text-blue-900 mr-3"
                @click.stop="allocatePayment(payment.id)"
              >
                Allocate
              </button>
              <button
                v-if="payment.status === 'pending' && hasPermission('accounting.payments.complete')"
                class="text-green-600 hover:text-green-900 mr-3"
                @click.stop="confirmComplete(payment.id)"
              >
                Complete
              </button>
              <button
                v-if="['pending', 'processing'].includes(payment.status) && hasPermission('accounting.payments.cancel')"
                class="text-orange-600 hover:text-orange-900 mr-3"
                @click.stop="confirmCancel(payment.id)"
              >
                Cancel
              </button>
              <button
                v-if="payment.status === 'pending' && hasPermission('accounting.payments.delete')"
                class="text-red-600 hover:text-red-900"
                @click.stop="confirmDelete(payment.id)"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div
        v-if="pagination.total > pagination.per_page"
        class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6"
      >
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Showing {{ (pagination.current_page - 1) * pagination.per_page + 1 }} to
            {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} of
            {{ pagination.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              :disabled="pagination.current_page === 1"
              class="btn-secondary disabled:opacity-50 disabled:cursor-not-allowed"
              @click="changePage(pagination.current_page - 1)"
            >
              Previous
            </button>
            <button
              :disabled="pagination.current_page === pagination.last_page"
              class="btn-secondary disabled:opacity-50 disabled:cursor-not-allowed"
              @click="changePage(pagination.current_page + 1)"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-if="payments.length === 0"
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
            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
          />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">
          No payments found
        </h3>
        <p class="mt-1 text-sm text-gray-500">
          Get started by creating a new payment.
        </p>
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
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { accountingApi } from '@/api/accounting'
import type { Payment, PaymentQueryParams } from '@/types/accounting'
import { usePermissions } from '@/composables/usePermissions'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'

const router = useRouter()
const { hasPermission } = usePermissions()

const payments = ref<Payment[]>([])
const loading = ref(false)
const error = ref('')
const showCompleteDialog = ref(false)
const showCancelDialog = ref(false)
const showDeleteDialog = ref(false)
const paymentToComplete = ref<number | null>(null)
const paymentToCancel = ref<number | null>(null)
const paymentToDelete = ref<number | null>(null)

const filters = reactive<PaymentQueryParams>({
  search: '',
  status: undefined,
  payment_method: undefined,
  date_from: undefined,
  date_to: undefined,
  page: 1,
  per_page: 15
})

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
})

let searchTimeout: ReturnType<typeof setTimeout>
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    filters.page = 1
    fetchPayments()
  }, 300)
}

const fetchPayments = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const response = await accountingApi.getPayments(filters)
    payments.value = response.data
    Object.assign(pagination, response.meta)
  } catch (err: any) {
    error.value = err.message || 'Failed to load payments'
    console.error('Error fetching payments:', err)
  } finally {
    loading.value = false
  }
}

const createPayment = () => {
  router.push({ name: 'accounting-payment-create' })
}

const viewPayment = (id: number) => {
  router.push({ name: 'accounting-payment-detail', params: { id } })
}

const editPayment = (id: number) => {
  router.push({ name: 'accounting-payment-edit', params: { id } })
}

const allocatePayment = (id: number) => {
  router.push({ name: 'accounting-payment-detail', params: { id }, query: { action: 'allocate' } })
}

const confirmComplete = (id: number) => {
  paymentToComplete.value = id
  showCompleteDialog.value = true
}

const completePayment = async () => {
  if (!paymentToComplete.value) return
  
  try {
    await accountingApi.completePayment(paymentToComplete.value)
    showCompleteDialog.value = false
    paymentToComplete.value = null
    await fetchPayments()
  } catch (err: any) {
    error.value = err.message || 'Failed to complete payment'
    console.error('Error completing payment:', err)
  }
}

const confirmCancel = (id: number) => {
  paymentToCancel.value = id
  showCancelDialog.value = true
}

const cancelPayment = async () => {
  if (!paymentToCancel.value) return
  
  try {
    await accountingApi.cancelPayment(paymentToCancel.value)
    showCancelDialog.value = false
    paymentToCancel.value = null
    await fetchPayments()
  } catch (err: any) {
    error.value = err.message || 'Failed to cancel payment'
    console.error('Error cancelling payment:', err)
  }
}

const confirmDelete = (id: number) => {
  paymentToDelete.value = id
  showDeleteDialog.value = true
}

const deletePayment = async () => {
  if (!paymentToDelete.value) return
  
  try {
    await accountingApi.deletePayment(paymentToDelete.value)
    showDeleteDialog.value = false
    paymentToDelete.value = null
    await fetchPayments()
  } catch (err: any) {
    error.value = err.message || 'Failed to delete payment'
    console.error('Error deleting payment:', err)
  }
}

const changePage = (page: number) => {
  filters.page = page
  fetchPayments()
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatStatus = (status: string): string => {
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const formatPaymentMethod = (method: string): string => {
  return method.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

const getStatusClass = (status: string): string => {
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

onMounted(() => {
  fetchPayments()
})
</script>

<style scoped>
.btn-primary {
  @apply inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500;
}

.btn-secondary {
  @apply inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500;
}

.input {
  @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm;
}
</style>
