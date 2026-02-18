<template>
  <div class="quotation-detail">
    <!-- Loading State -->
    <div
      v-if="loading"
      class="text-center py-12"
    >
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent" />
      <p class="mt-4 text-gray-600">
        Loading quotation details...
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

    <!-- Quotation Details -->
    <div v-else-if="quotation">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div class="flex-1">
          <div class="flex items-center space-x-4">
            <h1 class="text-2xl font-bold text-gray-900">
              {{ quotation.quote_number }}
            </h1>
            <span
              :class="getStatusClass(quotation.status)"
              class="rounded-full px-3 py-1 text-sm font-medium"
            >
              {{ formatStatus(quotation.status) }}
            </span>
          </div>
          <p class="mt-1 text-sm text-gray-600">
            Customer: {{ quotation.customer_name || 'N/A' }}
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
            v-if="quotation.status === 'draft'"
            class="btn-primary"
            @click="editQuotation"
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
        </div>
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Information -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Customer Information Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Customer Information
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Customer Name
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ quotation.customer_name || 'N/A' }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Quote Details Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Quote Details
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Quote Date
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDate(quotation.quote_date) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Valid Until
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ quotation.valid_until ? formatDate(quotation.valid_until) : 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Currency
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ quotation.currency || 'USD' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Exchange Rate
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ quotation.exchange_rate || 1 }}
                </dd>
              </div>
            </dl>

            <!-- Terms and Conditions -->
            <div
              v-if="quotation.terms_and_conditions"
              class="mt-4"
            >
              <dt class="text-sm font-medium text-gray-500 mb-1">
                Terms and Conditions
              </dt>
              <dd class="text-sm text-gray-900 whitespace-pre-wrap">
                {{ quotation.terms_and_conditions }}
              </dd>
            </div>
          </div>

          <!-- Line Items Table -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Line Items
            </h2>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Product
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Description
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Quantity
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Unit Price
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Discount
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Tax
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Line Total
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr
                    v-for="item in quotation.items"
                    :key="item.id"
                  >
                    <td class="px-4 py-4 text-sm text-gray-900">
                      {{ item.product_name || 'N/A' }}
                      <div
                        v-if="item.product_sku"
                        class="text-xs text-gray-500"
                      >
                        {{ item.product_sku }}
                      </div>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-900">
                      {{ item.description || '-' }}
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-900 text-right">
                      {{ item.quantity }}
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-900 text-right">
                      {{ formatCurrency(item.unit_price) }}
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-900 text-right">
                      {{ item.discount_percent }}% ({{ formatCurrency(item.discount_amount) }})
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-900 text-right">
                      {{ item.tax_percent }}% ({{ formatCurrency(item.tax_amount) }})
                    </td>
                    <td class="px-4 py-4 text-sm font-medium text-gray-900 text-right">
                      {{ formatCurrency(item.line_total) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Totals Section -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Totals
            </h2>
            <div class="flex justify-end">
              <div class="w-full max-w-xs space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Subtotal:</span>
                  <span class="font-medium text-gray-900">{{ formatCurrency(quotation.subtotal) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Discount Amount:</span>
                  <span class="font-medium text-gray-900">-{{ formatCurrency(quotation.discount_amount) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Tax Amount:</span>
                  <span class="font-medium text-gray-900">{{ formatCurrency(quotation.tax_amount) }}</span>
                </div>
                <div class="flex justify-between text-base font-semibold border-t pt-2">
                  <span class="text-gray-900">Total Amount:</span>
                  <span class="text-gray-900">{{ formatCurrency(quotation.total_amount) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Notes Card -->
          <div
            v-if="quotation.notes"
            class="bg-white shadow-sm rounded-lg p-6"
          >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Notes
            </h2>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">
              {{ quotation.notes }}
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
                v-if="quotation.status === 'draft'"
                class="w-full btn-secondary justify-center"
                @click="editQuotation"
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
                Edit Quotation
              </button>
              <button
                v-if="quotation.status === 'draft'"
                class="w-full btn-primary justify-center"
                @click="sendQuotation"
                :disabled="actionLoading"
              >
                Send Quotation
              </button>
              <button
                v-if="quotation.status === 'sent'"
                class="w-full btn-success justify-center"
                @click="acceptQuotation"
                :disabled="actionLoading"
              >
                Accept Quotation
              </button>
              <button
                v-if="quotation.status === 'sent'"
                class="w-full btn-warning justify-center"
                @click="rejectQuotation"
                :disabled="actionLoading"
              >
                Reject Quotation
              </button>
              <button
                v-if="quotation.status === 'accepted'"
                class="w-full btn-success justify-center"
                @click="convertQuotation"
                :disabled="actionLoading"
              >
                Convert to Sales Order
              </button>
              <button
                class="w-full btn-danger justify-center"
                @click="deleteQuotation"
              >
                Delete Quotation
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
                  {{ formatDateTime(quotation.created_at) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Last Updated
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDateTime(quotation.updated_at) }}
                </dd>
              </div>
              <div v-if="quotation.converted_at">
                <dt class="text-sm font-medium text-gray-500">
                  Converted At
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDateTime(quotation.converted_at) }}
                </dd>
              </div>
              <div v-if="quotation.converted_to_order_id">
                <dt class="text-sm font-medium text-gray-500">
                  Sales Order
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  <button
                    class="text-indigo-600 hover:text-indigo-900"
                    @click="viewSalesOrder(quotation.converted_to_order_id)"
                  >
                    View Order #{{ quotation.converted_to_order_id }}
                  </button>
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { salesApi } from '@/api/sales'
import type { Quotation } from '@/api/sales'

const router = useRouter()
const route = useRoute()

const loading = ref(false)
const actionLoading = ref(false)
const error = ref<string | null>(null)
const quotation = ref<Quotation | null>(null)

const quotationId = route.params.id as string

const fetchQuotation = async () => {
  loading.value = true
  error.value = null
  
  try {
    quotation.value = await salesApi.getQuotation(quotationId)
  } catch (err: any) {
    console.error('Failed to fetch quotation:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load quotation details.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'sales-quotations' })
}

const editQuotation = () => {
  router.push({ name: 'sales-quotation-edit', params: { id: quotationId } })
}

const viewSalesOrder = (orderId: number) => {
  router.push({ name: 'sales-order-detail', params: { id: orderId } })
}

const sendQuotation = async () => {
  if (!confirm('Are you sure you want to send this quotation to the customer?')) return
  
  actionLoading.value = true
  error.value = null
  
  try {
    await salesApi.sendQuotation(quotationId)
    await fetchQuotation()
  } catch (err: any) {
    console.error('Failed to send quotation:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to send quotation.'
  } finally {
    actionLoading.value = false
  }
}

const acceptQuotation = async () => {
  if (!confirm('Are you sure you want to accept this quotation?')) return
  
  actionLoading.value = true
  error.value = null
  
  try {
    await salesApi.acceptQuotation(quotationId)
    await fetchQuotation()
  } catch (err: any) {
    console.error('Failed to accept quotation:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to accept quotation.'
  } finally {
    actionLoading.value = false
  }
}

const rejectQuotation = async () => {
  if (!confirm('Are you sure you want to reject this quotation?')) return
  
  actionLoading.value = true
  error.value = null
  
  try {
    await salesApi.rejectQuotation(quotationId)
    await fetchQuotation()
  } catch (err: any) {
    console.error('Failed to reject quotation:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to reject quotation.'
  } finally {
    actionLoading.value = false
  }
}

const convertQuotation = async () => {
  if (!confirm('Are you sure you want to convert this quotation to a sales order?')) return
  
  actionLoading.value = true
  error.value = null
  
  try {
    const order = await salesApi.convertQuotation(quotationId)
    router.push({ name: 'sales-order-detail', params: { id: order.id } })
  } catch (err: any) {
    console.error('Failed to convert quotation:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to convert quotation.'
    actionLoading.value = false
  }
}

const deleteQuotation = async () => {
  if (!confirm('Are you sure you want to delete this quotation? This action cannot be undone.')) return
  
  try {
    await salesApi.deleteQuotation(quotationId)
    router.push({ name: 'sales-quotations' })
  } catch (err: any) {
    console.error('Failed to delete quotation:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to delete quotation.'
  }
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    sent: 'bg-blue-100 text-blue-800',
    accepted: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    expired: 'bg-yellow-100 text-yellow-800',
    converted: 'bg-purple-100 text-purple-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatStatus = (status: string) => {
  return status.charAt(0).toUpperCase() + status.slice(1)
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
    currency: quotation.value?.currency || 'USD'
  }).format(amount)
}

onMounted(() => {
  fetchQuotation()
})
</script>

<style scoped>
.btn-primary {
  @apply inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2;
}

.btn-success {
  @apply inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-warning {
  @apply inline-flex items-center rounded-md bg-yellow-600 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-danger {
  @apply inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2;
}
</style>
