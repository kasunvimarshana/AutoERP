<template>
  <div class="purchase-order-detail">
    <!-- Loading State -->
    <div
      v-if="loading"
      class="text-center py-12"
    >
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent" />
      <p class="mt-4 text-gray-600">
        Loading purchase order details...
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

    <!-- Purchase Order Details -->
    <div v-else-if="order">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div class="flex-1">
          <div class="flex items-center space-x-4">
            <h1 class="text-2xl font-bold text-gray-900">
              {{ order.po_number }}
            </h1>
            <span
              :class="getStatusClass(order.status)"
              class="rounded-full px-3 py-1 text-sm font-medium"
            >
              {{ formatStatus(order.status) }}
            </span>
          </div>
          <p class="mt-1 text-sm text-gray-600">
            Purchase Order Details
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
            v-if="order.status === 'draft'"
            class="btn-primary"
            @click="editOrder"
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
          <!-- Order Header Information -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Order Information
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Supplier
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ order.supplier_name || 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Order Date
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDate(order.order_date) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Expected Delivery
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ order.expected_delivery_date ? formatDate(order.expected_delivery_date) : 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Currency
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ order.currency || 'USD' }}
                </dd>
              </div>
            </dl>
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
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Product
                    </th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      SKU
                    </th>
                    <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Quantity
                    </th>
                    <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Unit Price
                    </th>
                    <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Tax %
                    </th>
                    <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Line Total
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr
                    v-for="item in order.items"
                    :key="item.id"
                  >
                    <td class="px-3 py-4 text-sm text-gray-900">
                      {{ item.product_name || 'N/A' }}
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-500">
                      {{ item.product_sku || 'N/A' }}
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-900 text-right">
                      {{ item.quantity }}
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-900 text-right">
                      {{ formatCurrency(item.unit_price) }}
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-900 text-right">
                      {{ item.tax_percent || 0 }}%
                    </td>
                    <td class="px-3 py-4 text-sm font-medium text-gray-900 text-right">
                      {{ formatCurrency(item.line_total) }}
                    </td>
                  </tr>
                  <tr v-if="!order.items || order.items.length === 0">
                    <td
                      colspan="6"
                      class="px-3 py-8 text-center text-sm text-gray-500"
                    >
                      No items found
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
            <dl class="space-y-3">
              <div class="flex justify-between">
                <dt class="text-sm text-gray-600">
                  Subtotal
                </dt>
                <dd class="text-sm font-medium text-gray-900">
                  {{ formatCurrency(order.subtotal) }}
                </dd>
              </div>
              <div
                v-if="order.discount_amount > 0"
                class="flex justify-between"
              >
                <dt class="text-sm text-gray-600">
                  Discount
                </dt>
                <dd class="text-sm font-medium text-red-600">
                  -{{ formatCurrency(order.discount_amount) }}
                </dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-sm text-gray-600">
                  Tax Amount
                </dt>
                <dd class="text-sm font-medium text-gray-900">
                  {{ formatCurrency(order.tax_amount) }}
                </dd>
              </div>
              <div class="flex justify-between border-t pt-3">
                <dt class="text-base font-semibold text-gray-900">
                  Total Amount
                </dt>
                <dd class="text-base font-bold text-gray-900">
                  {{ formatCurrency(order.total_amount) }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Notes -->
          <div
            v-if="order.notes"
            class="bg-white shadow-sm rounded-lg p-6"
          >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Notes
            </h2>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">
              {{ order.notes }}
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
                v-if="order.status === 'draft'"
                class="w-full btn-primary justify-center"
                @click="sendOrder"
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
                Send to Supplier
              </button>
              <button
                v-if="order.status === 'sent'"
                class="w-full btn-success justify-center"
                @click="confirmOrder"
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
                Confirm Order
              </button>
              <button
                v-if="['draft', 'sent'].includes(order.status)"
                class="w-full btn-danger justify-center"
                @click="cancelOrder"
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
                Cancel Order
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
                  {{ formatDateTime(order.created_at) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Last Updated
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDateTime(order.updated_at) }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Goods Receipts Card -->
          <div
            v-if="order.receipts && order.receipts.length > 0"
            class="bg-white shadow-sm rounded-lg p-6"
          >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Goods Receipts
            </h2>
            <ul class="space-y-2">
              <li
                v-for="receipt in order.receipts"
                :key="receipt.id"
                class="text-sm"
              >
                <router-link
                  :to="{ name: 'purchasing-goods-receipt-detail', params: { id: receipt.id } }"
                  class="text-indigo-600 hover:text-indigo-900"
                >
                  {{ receipt.receipt_number }}
                </router-link>
                <span class="text-gray-500 ml-2">
                  ({{ formatDate(receipt.receipt_date) }})
                </span>
              </li>
            </ul>
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
import type { PurchaseOrder } from '@/api/purchasing'

const router = useRouter()
const route = useRoute()

const loading = ref(false)
const error = ref<string | null>(null)
const order = ref<PurchaseOrder | null>(null)

const orderId = route.params.id as string

const fetchPurchaseOrder = async () => {
  loading.value = true
  error.value = null
  
  try {
    order.value = await purchasingApi.getPurchaseOrder(orderId)
  } catch (err: any) {
    console.error('Failed to fetch purchase order:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load purchase order details.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'purchasing-orders' })
}

const editOrder = () => {
  router.push({ name: 'purchasing-order-edit', params: { id: orderId } })
}

const sendOrder = async () => {
  if (!confirm('Are you sure you want to send this purchase order to the supplier?')) return
  
  try {
    await purchasingApi.sendPurchaseOrder(orderId)
    await fetchPurchaseOrder()
  } catch (err: any) {
    console.error('Failed to send purchase order:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to send purchase order.'
  }
}

const confirmOrder = async () => {
  if (!confirm('Are you sure you want to confirm this purchase order?')) return
  
  try {
    await purchasingApi.confirmPurchaseOrder(orderId)
    await fetchPurchaseOrder()
  } catch (err: any) {
    console.error('Failed to confirm purchase order:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to confirm purchase order.'
  }
}

const cancelOrder = async () => {
  if (!confirm('Are you sure you want to cancel this purchase order?')) return
  
  try {
    await purchasingApi.cancelPurchaseOrder(orderId)
    await fetchPurchaseOrder()
  } catch (err: any) {
    console.error('Failed to cancel purchase order:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to cancel purchase order.'
  }
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    sent: 'bg-blue-100 text-blue-800',
    confirmed: 'bg-green-100 text-green-800',
    received: 'bg-purple-100 text-purple-800',
    cancelled: 'bg-red-100 text-red-800',
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

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: order.value?.currency || 'USD'
  }).format(amount)
}

onMounted(() => {
  fetchPurchaseOrder()
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
