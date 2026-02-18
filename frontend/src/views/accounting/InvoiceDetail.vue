<template>
  <div class="invoice-detail">
    <!-- Loading State -->
    <div
      v-if="loading"
      class="text-center py-12"
    >
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent" />
      <p class="mt-4 text-gray-600">
        Loading invoice details...
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

    <!-- Invoice Details -->
    <div v-else-if="invoice">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div class="flex-1">
          <div class="flex items-center space-x-4">
            <h1 class="text-2xl font-bold text-gray-900">
              Invoice {{ invoice.invoice_number }}
            </h1>
            <span
              :class="getStatusClass(invoice.status)"
              class="rounded-full px-3 py-1 text-sm font-medium"
            >
              {{ formatStatus(invoice.status) }}
            </span>
          </div>
          <p class="mt-1 text-sm text-gray-600">
            Customer: {{ invoice.customer_name }}
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
            v-if="invoice.status === 'draft' && hasPermission('accounting.invoices.update')"
            class="btn-primary"
            @click="editInvoice"
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
            v-if="['draft', 'viewed'].includes(invoice.status) && hasPermission('accounting.invoices.send')"
            class="btn-primary"
            @click="confirmSend"
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
                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
              />
            </svg>
            Send
          </button>
          <button
            v-if="['sent', 'partial', 'overdue'].includes(invoice.status) && hasPermission('accounting.invoices.mark-paid')"
            class="btn-success"
            @click="confirmMarkPaid"
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
            Mark as Paid
          </button>
          <button
            v-if="invoice.status === 'draft' && hasPermission('accounting.invoices.delete')"
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
          <!-- Invoice Information Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Invoice Information
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Invoice Number
                </dt>
                <dd class="mt-1 text-sm text-gray-900 font-mono">
                  {{ invoice.invoice_number }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Customer
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ invoice.customer_name }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Invoice Date
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDate(invoice.invoice_date) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Due Date
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDate(invoice.due_date) }}
                </dd>
              </div>
              <div v-if="invoice.payment_terms">
                <dt class="text-sm font-medium text-gray-500">
                  Payment Terms
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ invoice.payment_terms }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Currency
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ invoice.currency }}
                </dd>
              </div>
              <div
                v-if="invoice.sent_at"
                class="sm:col-span-2"
              >
                <dt class="text-sm font-medium text-gray-500">
                  Sent At
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDateTime(invoice.sent_at) }}
                </dd>
              </div>
              <div
                v-if="invoice.paid_at"
                class="sm:col-span-2"
              >
                <dt class="text-sm font-medium text-gray-500">
                  Paid At
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDateTime(invoice.paid_at) }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Invoice Items Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Invoice Items
            </h2>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      Product / Description
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                      Quantity
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                      Unit Price
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                      Discount
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                      Tax
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                      Line Total
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr
                    v-for="item in invoice.items"
                    :key="item.id"
                  >
                    <td class="px-4 py-3 text-sm text-gray-900">
                      <div class="font-medium">
                        {{ item.description }}
                      </div>
                      <div
                        v-if="item.product_name"
                        class="text-gray-500 text-xs"
                      >
                        {{ item.product_name }}
                      </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-right text-gray-900">
                      {{ item.quantity }}
                    </td>
                    <td class="px-4 py-3 text-sm text-right text-gray-900">
                      {{ formatCurrency(item.unit_price) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-right text-gray-600">
                      {{ item.discount_percent > 0 ? `${item.discount_percent}%` : '-' }}
                      <span
                        v-if="item.discount_amount > 0"
                        class="block text-xs"
                      >
                        {{ formatCurrency(item.discount_amount) }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-right text-gray-600">
                      {{ item.tax_percent > 0 ? `${item.tax_percent}%` : '-' }}
                      <span
                        v-if="item.tax_amount > 0"
                        class="block text-xs"
                      >
                        {{ formatCurrency(item.tax_amount) }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-right text-gray-900 font-medium">
                      {{ formatCurrency(item.line_total) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Amounts Summary Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Amounts
            </h2>
            <dl class="space-y-3">
              <div class="flex justify-between">
                <dt class="text-sm text-gray-600">
                  Subtotal
                </dt>
                <dd class="text-sm font-medium text-gray-900">
                  {{ formatCurrency(invoice.subtotal) }}
                </dd>
              </div>
              <div
                v-if="invoice.discount_amount > 0"
                class="flex justify-between"
              >
                <dt class="text-sm text-gray-600">
                  Discount
                </dt>
                <dd class="text-sm font-medium text-red-600">
                  -{{ formatCurrency(invoice.discount_amount) }}
                </dd>
              </div>
              <div
                v-if="invoice.tax_amount > 0"
                class="flex justify-between"
              >
                <dt class="text-sm text-gray-600">
                  Tax
                </dt>
                <dd class="text-sm font-medium text-gray-900">
                  {{ formatCurrency(invoice.tax_amount) }}
                </dd>
              </div>
              <div class="flex justify-between pt-3 border-t border-gray-200">
                <dt class="text-base font-semibold text-gray-900">
                  Total Amount
                </dt>
                <dd class="text-base font-semibold text-gray-900">
                  {{ formatCurrency(invoice.total_amount) }}
                </dd>
              </div>
              <div
                v-if="invoice.paid_amount > 0"
                class="flex justify-between"
              >
                <dt class="text-sm text-gray-600">
                  Paid Amount
                </dt>
                <dd class="text-sm font-medium text-green-600">
                  {{ formatCurrency(invoice.paid_amount) }}
                </dd>
              </div>
              <div class="flex justify-between pt-3 border-t border-gray-200">
                <dt class="text-base font-semibold text-gray-900">
                  Balance Due
                </dt>
                <dd
                  :class="invoice.balance > 0 ? 'text-red-600' : 'text-green-600'"
                  class="text-base font-semibold"
                >
                  {{ formatCurrency(invoice.balance) }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Payment History -->
          <div
            v-if="invoice.payments && invoice.payments.length > 0"
            class="bg-white shadow-sm rounded-lg p-6"
          >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Payment History
            </h2>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      Payment #
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      Date
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      Method
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                      Amount
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      Status
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr
                    v-for="payment in invoice.payments"
                    :key="payment.id"
                  >
                    <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                      {{ payment.payment_number }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                      {{ formatDate(payment.payment_date) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                      {{ formatPaymentMethod(payment.payment_method) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-right text-gray-900 font-medium">
                      {{ formatCurrency(payment.amount) }}
                    </td>
                    <td class="px-4 py-3 text-sm">
                      <span
                        :class="getPaymentStatusClass(payment.status)"
                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                      >
                        {{ formatStatus(payment.status) }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Notes Section -->
          <div
            v-if="invoice.notes"
            class="bg-white shadow-sm rounded-lg p-6"
          >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Notes
            </h2>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">
              {{ invoice.notes }}
            </p>
          </div>

          <!-- Terms & Conditions -->
          <div
            v-if="invoice.terms_conditions"
            class="bg-white shadow-sm rounded-lg p-6"
          >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Terms & Conditions
            </h2>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">
              {{ invoice.terms_conditions }}
            </p>
          </div>
        </div>

        <!-- Right Column - Actions & Timeline -->
        <div class="space-y-6">
          <!-- Quick Actions Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Quick Actions
            </h2>
            <div class="space-y-2">
              <button
                v-if="['draft', 'viewed'].includes(invoice.status) && hasPermission('accounting.invoices.send')"
                class="w-full btn-primary justify-center"
                @click="confirmSend"
              >
                Send Invoice
              </button>
              <button
                v-if="['sent', 'partial', 'overdue'].includes(invoice.status) && hasPermission('accounting.invoices.mark-paid')"
                class="w-full btn-success justify-center"
                @click="confirmMarkPaid"
              >
                Mark as Paid
              </button>
              <button
                v-if="invoice.status === 'draft' && hasPermission('accounting.invoices.delete')"
                class="w-full btn-danger justify-center"
                @click="confirmDelete"
              >
                Delete Invoice
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
                  {{ formatDateTime(invoice.created_at) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Last Updated
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDateTime(invoice.updated_at) }}
                </dd>
              </div>
              <div v-if="invoice.sent_at">
                <dt class="text-sm font-medium text-gray-500">
                  Sent to Customer
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDateTime(invoice.sent_at) }}
                </dd>
              </div>
              <div v-if="invoice.paid_at">
                <dt class="text-sm font-medium text-gray-500">
                  Fully Paid
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDateTime(invoice.paid_at) }}
                </dd>
              </div>
            </dl>
          </div>
        </div>
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
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { accountingApi } from '@/api/accounting'
import type { Invoice } from '@/types/accounting'
import { usePermissions } from '@/composables/usePermissions'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'

const router = useRouter()
const route = useRoute()
const { hasPermission } = usePermissions()

const loading = ref(false)
const error = ref<string | null>(null)
const invoice = ref<Invoice | null>(null)
const showSendDialog = ref(false)
const showMarkPaidDialog = ref(false)
const showDeleteDialog = ref(false)

const invoiceId = route.params.id as string

const fetchInvoice = async () => {
  loading.value = true
  error.value = null
  
  try {
    invoice.value = await accountingApi.getInvoice(invoiceId)
  } catch (err: any) {
    console.error('Failed to fetch invoice:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load invoice details.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'accounting-invoices' })
}

const editInvoice = () => {
  router.push({ name: 'accounting-invoice-edit', params: { id: invoiceId } })
}

const confirmSend = () => {
  showSendDialog.value = true
}

const sendInvoice = async () => {
  try {
    await accountingApi.sendInvoice(invoiceId)
    showSendDialog.value = false
    await fetchInvoice()
  } catch (err: any) {
    console.error('Failed to send invoice:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to send invoice.'
  }
}

const confirmMarkPaid = () => {
  showMarkPaidDialog.value = true
}

const markInvoicePaid = async () => {
  try {
    await accountingApi.markInvoiceAsPaid(invoiceId)
    showMarkPaidDialog.value = false
    await fetchInvoice()
  } catch (err: any) {
    console.error('Failed to mark invoice as paid:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to mark invoice as paid.'
  }
}

const confirmDelete = () => {
  showDeleteDialog.value = true
}

const deleteInvoice = async () => {
  try {
    await accountingApi.deleteInvoice(invoiceId)
    showDeleteDialog.value = false
    router.push({ name: 'accounting-invoices' })
  } catch (err: any) {
    console.error('Failed to delete invoice:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to delete invoice.'
  }
}

const getStatusClass = (status: string) => {
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

const getPaymentStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800',
    refunded: 'bg-purple-100 text-purple-800'
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
    currency: invoice.value?.currency || 'USD'
  }).format(amount)
}

onMounted(() => {
  fetchInvoice()
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

.btn-danger {
  @apply inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2;
}
</style>
