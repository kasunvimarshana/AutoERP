<template>
  <div class="goods-receipt-detail">
    <!-- Loading State -->
    <div
      v-if="loading"
      class="text-center py-12"
    >
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent" />
      <p class="mt-4 text-gray-600">
        Loading goods receipt details...
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

    <!-- Goods Receipt Details -->
    <div v-else-if="receipt">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div class="flex-1">
          <div class="flex items-center space-x-4">
            <h1 class="text-2xl font-bold text-gray-900">
              {{ receipt.receipt_number }}
            </h1>
            <span
              :class="getStatusClass(receipt.status)"
              class="rounded-full px-3 py-1 text-sm font-medium"
            >
              {{ formatStatus(receipt.status) }}
            </span>
          </div>
          <p class="mt-1 text-sm text-gray-600">
            Goods Receipt Details
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
        </div>
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Information -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Receipt Header Information -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Receipt Information
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Purchase Order
                </dt>
                <dd class="mt-1 text-sm">
                  <router-link
                    v-if="receipt.purchase_order_id"
                    :to="{ name: 'purchasing-order-detail', params: { id: receipt.purchase_order_id } }"
                    class="text-indigo-600 hover:text-indigo-900 font-medium"
                  >
                    {{ receipt.po_number || 'N/A' }}
                  </router-link>
                  <span
                    v-else
                    class="text-gray-900"
                  >N/A</span>
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Receipt Date
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDate(receipt.receipt_date) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Warehouse
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ receipt.warehouse_name || 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Status
                </dt>
                <dd class="mt-1">
                  <span
                    :class="getStatusClass(receipt.status)"
                    class="rounded-full px-2 py-1 text-xs font-medium"
                  >
                    {{ formatStatus(receipt.status) }}
                  </span>
                </dd>
              </div>
            </dl>
          </div>

          <!-- Line Items Table -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Received Items
            </h2>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Product
                    </th>
                    <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Qty Received
                    </th>
                    <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Qty Accepted
                    </th>
                    <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Qty Rejected
                    </th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Notes
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr
                    v-for="item in receipt.items"
                    :key="item.id"
                  >
                    <td class="px-3 py-4 text-sm text-gray-900">
                      {{ item.product_name || 'N/A' }}
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-900 text-right">
                      {{ item.quantity_received }}
                    </td>
                    <td class="px-3 py-4 text-sm text-green-600 font-medium text-right">
                      {{ item.quantity_accepted }}
                    </td>
                    <td class="px-3 py-4 text-sm text-red-600 font-medium text-right">
                      {{ item.quantity_rejected }}
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-500">
                      {{ item.notes || '-' }}
                    </td>
                  </tr>
                  <tr v-if="!receipt.items || receipt.items.length === 0">
                    <td
                      colspan="5"
                      class="px-3 py-8 text-center text-sm text-gray-500"
                    >
                      No items found
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Notes -->
          <div
            v-if="receipt.notes"
            class="bg-white shadow-sm rounded-lg p-6"
          >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Notes
            </h2>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">
              {{ receipt.notes }}
            </p>
          </div>
        </div>

        <!-- Right Column - Actions & Status -->
        <div class="space-y-6">
          <!-- Actions Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Actions
            </h2>
            <div class="space-y-2">
              <button
                v-if="receipt.status === 'pending'"
                class="w-full btn-primary justify-center"
                @click="inspectReceipt"
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
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                  />
                </svg>
                Mark as Inspected
              </button>
              <button
                v-if="receipt.status === 'inspected'"
                class="w-full btn-success justify-center"
                @click="acceptReceipt"
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
                Accept Receipt
              </button>
              <button
                v-if="receipt.status === 'inspected'"
                class="w-full btn-danger justify-center"
                @click="rejectReceipt"
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
                Reject Receipt
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
                  {{ formatDateTime(receipt.created_at) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Last Updated
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDateTime(receipt.updated_at) }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Status Information -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Status Information
            </h2>
            <div class="space-y-3">
              <div
                v-if="receipt.status === 'pending'"
                class="flex items-start"
              >
                <div class="flex-shrink-0">
                  <svg
                    class="h-5 w-5 text-yellow-400"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm text-gray-700">
                    This receipt is pending inspection. Click "Mark as Inspected" to proceed.
                  </p>
                </div>
              </div>
              <div
                v-else-if="receipt.status === 'inspected'"
                class="flex items-start"
              >
                <div class="flex-shrink-0">
                  <svg
                    class="h-5 w-5 text-blue-400"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm text-gray-700">
                    This receipt has been inspected. Accept or reject to complete the process.
                  </p>
                </div>
              </div>
              <div
                v-else-if="receipt.status === 'accepted'"
                class="flex items-start"
              >
                <div class="flex-shrink-0">
                  <svg
                    class="h-5 w-5 text-green-400"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm text-gray-700">
                    This receipt has been accepted. Inventory has been updated.
                  </p>
                </div>
              </div>
              <div
                v-else-if="receipt.status === 'rejected'"
                class="flex items-start"
              >
                <div class="flex-shrink-0">
                  <svg
                    class="h-5 w-5 text-red-400"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm text-gray-700">
                    This receipt has been rejected. No inventory changes were made.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { purchasingApi } from '@/api/purchasing'
import type { GoodsReceipt } from '@/api/purchasing'

const router = useRouter()
const route = useRoute()

const loading = ref(false)
const error = ref<string | null>(null)
const receipt = ref<GoodsReceipt | null>(null)

const receiptId = route.params.id as string

const fetchGoodsReceipt = async () => {
  loading.value = true
  error.value = null
  
  try {
    receipt.value = await purchasingApi.getGoodsReceipt(receiptId)
  } catch (err: any) {
    console.error('Failed to fetch goods receipt:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load goods receipt details.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'purchasing-goods-receipts' })
}

const inspectReceipt = async () => {
  if (!confirm('Are you sure you want to mark this receipt as inspected?')) return
  
  try {
    await purchasingApi.inspectGoodsReceipt(receiptId)
    await fetchGoodsReceipt()
  } catch (err: any) {
    console.error('Failed to inspect receipt:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to mark receipt as inspected.'
  }
}

const acceptReceipt = async () => {
  if (!confirm('Are you sure you want to accept this goods receipt? This will update inventory levels.')) return
  
  try {
    await purchasingApi.acceptGoodsReceipt(receiptId)
    await fetchGoodsReceipt()
  } catch (err: any) {
    console.error('Failed to accept receipt:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to accept goods receipt.'
  }
}

const rejectReceipt = async () => {
  const reason = prompt('Please provide a reason for rejection (minimum 10 characters):')
  if (!reason) return
  
  if (reason.trim().length < 10) {
    error.value = 'Rejection reason must be at least 10 characters long.'
    return
  }
  
  try {
    await purchasingApi.rejectGoodsReceipt(receiptId, reason)
    await fetchGoodsReceipt()
  } catch (err: any) {
    console.error('Failed to reject receipt:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to reject goods receipt.'
  }
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    inspected: 'bg-blue-100 text-blue-800',
    accepted: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
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
    day: 'numeric',
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

onMounted(() => {
  fetchGoodsReceipt()
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
