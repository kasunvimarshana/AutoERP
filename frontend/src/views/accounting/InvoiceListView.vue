<template>
  <div class="invoice-list-view">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Invoices
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          Manage customer invoices and track payments
        </p>
      </div>
      <button
        v-if="hasPermission('accounting.invoices.create')"
        class="btn-primary"
        @click="createInvoice"
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
        New Invoice
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
            placeholder="Search by invoice number, customer..."
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
            @change="fetchInvoices"
          >
            <option value="">
              All Statuses
            </option>
            <option value="draft">
              Draft
            </option>
            <option value="sent">
              Sent
            </option>
            <option value="viewed">
              Viewed
            </option>
            <option value="partial">
              Partial
            </option>
            <option value="paid">
              Paid
            </option>
            <option value="overdue">
              Overdue
            </option>
            <option value="cancelled">
              Cancelled
            </option>
            <option value="refunded">
              Refunded
            </option>
          </select>
        </div>

        <!-- Customer Filter -->
        <div>
          <label
            for="customer"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Customer
          </label>
          <select
            id="customer"
            v-model="filters.customer_id"
            class="input"
            @change="fetchInvoices"
          >
            <option :value="undefined">
              All Customers
            </option>
            <option
              v-for="customer in customers"
              :key="customer.id"
              :value="customer.id"
            >
              {{ customer.customer_name }}
            </option>
          </select>
        </div>

        <!-- Invoice Date From -->
        <div>
          <label
            for="date_from"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Invoice Date From
          </label>
          <input
            id="date_from"
            v-model="filters.date_from"
            type="date"
            class="input"
            @change="fetchInvoices"
          >
        </div>

        <!-- Invoice Date To -->
        <div>
          <label
            for="date_to"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Invoice Date To
          </label>
          <input
            id="date_to"
            v-model="filters.date_to"
            type="date"
            class="input"
            @change="fetchInvoices"
          >
        </div>

        <!-- Due Date From -->
        <div>
          <label
            for="due_date_from"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Due Date From
          </label>
          <input
            id="due_date_from"
            v-model="filters.due_date_from"
            type="date"
            class="input"
            @change="fetchInvoices"
          >
        </div>

        <!-- Due Date To -->
        <div>
          <label
            for="due_date_to"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Due Date To
          </label>
          <input
            id="due_date_to"
            v-model="filters.due_date_to"
            type="date"
            class="input"
            @change="fetchInvoices"
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
        Loading invoices...
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
              Invoice Number
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Customer
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Invoice Date
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Due Date
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Status
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Total Amount
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Paid Amount
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Balance
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="invoice in invoices"
            :key="invoice.id"
            class="hover:bg-gray-50 cursor-pointer"
            @click="viewInvoice(invoice.id)"
          >
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ invoice.invoice_number }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
              {{ invoice.customer_name }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
              {{ formatDate(invoice.invoice_date) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
              {{ formatDate(invoice.due_date) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="getStatusClass(invoice.status)"
                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
              >
                {{ formatStatus(invoice.status) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
              {{ formatCurrency(invoice.total_amount, invoice.currency) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
              {{ formatCurrency(invoice.paid_amount, invoice.currency) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
              {{ formatCurrency(invoice.balance, invoice.currency) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button
                class="text-indigo-600 hover:text-indigo-900 mr-3"
                @click.stop="viewInvoice(invoice.id)"
              >
                View
              </button>
              <button
                v-if="invoice.status === 'draft' && hasPermission('accounting.invoices.update')"
                class="text-indigo-600 hover:text-indigo-900 mr-3"
                @click.stop="editInvoice(invoice.id)"
              >
                Edit
              </button>
              <button
                v-if="['draft', 'viewed'].includes(invoice.status) && hasPermission('accounting.invoices.send')"
                class="text-blue-600 hover:text-blue-900 mr-3"
                @click.stop="confirmSend(invoice.id)"
              >
                Send
              </button>
              <button
                v-if="['sent', 'partial', 'overdue'].includes(invoice.status) && hasPermission('accounting.invoices.mark-paid')"
                class="text-green-600 hover:text-green-900 mr-3"
                @click.stop="confirmMarkPaid(invoice.id)"
              >
                Mark Paid
              </button>
              <button
                v-if="invoice.status === 'draft' && hasPermission('accounting.invoices.delete')"
                class="text-red-600 hover:text-red-900"
                @click.stop="confirmDelete(invoice.id)"
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
        v-if="invoices.length === 0"
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
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
          />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">
          No invoices found
        </h3>
        <p class="mt-1 text-sm text-gray-500">
          Get started by creating a new invoice.
        </p>
      </div>
    </div>

    <!-- Send Confirmation Modal -->
    <ConfirmDialog
      v-if="showSendDialog"
      title="Send Invoice"
      message="Are you sure you want to send this invoice to the customer?"
      confirm-text="Send"
      cancel-text="Cancel"
      @confirm="sendInvoice"
      @cancel="showSendDialog = false"
    />

    <!-- Mark Paid Confirmation Modal -->
    <ConfirmDialog
      v-if="showMarkPaidDialog"
      title="Mark Invoice as Paid"
      message="Are you sure you want to mark this invoice as paid?"
      confirm-text="Mark Paid"
      cancel-text="Cancel"
      @confirm="markInvoicePaid"
      @cancel="showMarkPaidDialog = false"
    />

    <!-- Delete Confirmation Modal -->
    <ConfirmDialog
      v-if="showDeleteDialog"
      title="Delete Invoice"
      message="Are you sure you want to delete this invoice? This action cannot be undone."
      confirm-text="Delete"
      cancel-text="Cancel"
      @confirm="deleteInvoice"
      @cancel="showDeleteDialog = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { accountingApi } from '@/api/accounting'
import { salesApi } from '@/api/sales'
import type { Invoice, InvoiceQueryParams } from '@/types/accounting'
import type { Customer } from '@/api/sales'
import { usePermissions } from '@/composables/usePermissions'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'

const router = useRouter()
const { hasPermission } = usePermissions()

const invoices = ref<Invoice[]>([])
const customers = ref<Customer[]>([])
const loading = ref(false)
const error = ref('')
const showSendDialog = ref(false)
const showMarkPaidDialog = ref(false)
const showDeleteDialog = ref(false)
const invoiceToSend = ref<number | null>(null)
const invoiceToMarkPaid = ref<number | null>(null)
const invoiceToDelete = ref<number | null>(null)

const filters = reactive<InvoiceQueryParams>({
  search: '',
  status: undefined,
  customer_id: undefined,
  date_from: undefined,
  date_to: undefined,
  due_date_from: undefined,
  due_date_to: undefined,
  page: 1,
  per_page: 15
})

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
})

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout>
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    filters.page = 1
    fetchInvoices()
  }, 300)
}

// Fetch customers for filter
const fetchCustomers = async () => {
  try {
    const response = await salesApi.getCustomers({ per_page: 1000 })
    customers.value = response.data
  } catch (err: any) {
    console.error('Error fetching customers:', err)
  }
}

// Fetch invoices
const fetchInvoices = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const response = await accountingApi.getInvoices(filters)
    invoices.value = response.data
    Object.assign(pagination, response.meta)
  } catch (err: any) {
    error.value = err.message || 'Failed to load invoices'
    console.error('Error fetching invoices:', err)
  } finally {
    loading.value = false
  }
}

// Navigation
const createInvoice = () => {
  router.push({ name: 'accounting-invoice-create' })
}

const viewInvoice = (id: number) => {
  router.push({ name: 'accounting-invoice-detail', params: { id } })
}

const editInvoice = (id: number) => {
  router.push({ name: 'accounting-invoice-edit', params: { id } })
}

// Send
const confirmSend = (id: number) => {
  invoiceToSend.value = id
  showSendDialog.value = true
}

const sendInvoice = async () => {
  if (!invoiceToSend.value) return
  
  try {
    await accountingApi.sendInvoice(invoiceToSend.value)
    showSendDialog.value = false
    invoiceToSend.value = null
    await fetchInvoices()
  } catch (err: any) {
    error.value = err.message || 'Failed to send invoice'
    console.error('Error sending invoice:', err)
  }
}

// Mark as Paid
const confirmMarkPaid = (id: number) => {
  invoiceToMarkPaid.value = id
  showMarkPaidDialog.value = true
}

const markInvoicePaid = async () => {
  if (!invoiceToMarkPaid.value) return
  
  try {
    await accountingApi.markInvoiceAsPaid(invoiceToMarkPaid.value)
    showMarkPaidDialog.value = false
    invoiceToMarkPaid.value = null
    await fetchInvoices()
  } catch (err: any) {
    error.value = err.message || 'Failed to mark invoice as paid'
    console.error('Error marking invoice as paid:', err)
  }
}

// Delete
const confirmDelete = (id: number) => {
  invoiceToDelete.value = id
  showDeleteDialog.value = true
}

const deleteInvoice = async () => {
  if (!invoiceToDelete.value) return
  
  try {
    await accountingApi.deleteInvoice(invoiceToDelete.value)
    showDeleteDialog.value = false
    invoiceToDelete.value = null
    await fetchInvoices()
  } catch (err: any) {
    error.value = err.message || 'Failed to delete invoice'
    console.error('Error deleting invoice:', err)
  }
}

// Pagination
const changePage = (page: number) => {
  filters.page = page
  fetchInvoices()
}

// Formatting helpers
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

const formatCurrency = (amount: number, currency: string = 'USD'): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency
  }).format(amount)
}

const getStatusClass = (status: string): string => {
  const classes: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    sent: 'bg-blue-100 text-blue-800',
    viewed: 'bg-cyan-100 text-cyan-800',
    partial: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-green-100 text-green-800',
    overdue: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800',
    refunded: 'bg-purple-100 text-purple-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

// Initialize
onMounted(() => {
  fetchCustomers()
  fetchInvoices()
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
