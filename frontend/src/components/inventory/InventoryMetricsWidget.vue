<template>
  <div class="bg-white rounded-lg shadow-md p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">
          Inventory Overview
        </h3>
        <p class="text-sm text-gray-500 mt-1">
          Real-time stock metrics
        </p>
      </div>
      <button
        :disabled="isRefreshing"
        class="p-2 text-gray-500 hover:text-gray-700 rounded-md hover:bg-gray-100 disabled:opacity-50"
        :class="{ 'animate-spin': isRefreshing }"
        @click="refreshData"
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
            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
          />
        </svg>
      </button>
    </div>

    <!-- Loading State -->
    <div
      v-if="loading && !metrics"
      class="flex items-center justify-center py-12"
    >
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600" />
    </div>

    <!-- Error State -->
    <div
      v-else-if="error"
      class="bg-red-50 border border-red-200 rounded-md p-4"
    >
      <div class="flex">
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
          <p class="text-sm text-red-800">
            {{ error }}
          </p>
        </div>
      </div>
    </div>

    <!-- Metrics Display -->
    <div
      v-else-if="metrics"
      class="space-y-6"
    >
      <!-- Key Metrics Grid -->
      <div class="grid grid-cols-2 gap-4">
        <!-- Total Products -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-blue-700">
                Total Products
              </p>
              <p class="text-2xl font-bold text-blue-900 mt-1">
                {{ formatNumber(metrics.total_products) }}
              </p>
            </div>
            <div class="p-3 bg-blue-200 rounded-full">
              <svg
                class="h-6 w-6 text-blue-700"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                />
              </svg>
            </div>
          </div>
        </div>

        <!-- Active Products -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-green-700">
                Active Products
              </p>
              <p class="text-2xl font-bold text-green-900 mt-1">
                {{ formatNumber(metrics.active_products) }}
              </p>
            </div>
            <div class="p-3 bg-green-200 rounded-full">
              <svg
                class="h-6 w-6 text-green-700"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </div>
          </div>
        </div>

        <!-- Low Stock Items -->
        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-yellow-700">
                Low Stock
              </p>
              <p class="text-2xl font-bold text-yellow-900 mt-1">
                {{ formatNumber(metrics.low_stock_count) }}
              </p>
            </div>
            <div class="p-3 bg-yellow-200 rounded-full">
              <svg
                class="h-6 w-6 text-yellow-700"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
              </svg>
            </div>
          </div>
        </div>

        <!-- Total Stock Value -->
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-purple-700">
                Stock Value
              </p>
              <p class="text-2xl font-bold text-purple-900 mt-1">
                {{ formatCurrency(metrics.total_stock_value) }}
              </p>
            </div>
            <div class="p-3 bg-purple-200 rounded-full">
              <svg
                class="h-6 w-6 text-purple-700"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div
        v-if="metrics.recent_activities && metrics.recent_activities.length > 0"
        class="mt-6"
      >
        <h4 class="text-sm font-semibold text-gray-900 mb-3">
          Recent Activity
        </h4>
        <div class="space-y-2">
          <div
            v-for="activity in metrics.recent_activities.slice(0, 3)"
            :key="activity.id"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-md hover:bg-gray-100 transition-colors"
          >
            <div class="flex items-center space-x-3">
              <div class="flex-shrink-0">
                <div
                  class="h-2 w-2 rounded-full"
                  :class="{
                    'bg-green-500': activity.type === 'purchase' || activity.type === 'adjustment_in',
                    'bg-red-500': activity.type === 'sale' || activity.type === 'adjustment_out',
                    'bg-blue-500': activity.type === 'transfer'
                  }"
                />
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">
                  {{ activity.description }}
                </p>
                <p class="text-xs text-gray-500">
                  {{ formatDate(activity.created_at) }}
                </p>
              </div>
            </div>
            <span
              class="text-sm font-medium"
              :class="{
                'text-green-600': activity.quantity > 0,
                'text-red-600': activity.quantity < 0
              }"
            >
              {{ activity.quantity > 0 ? '+' : '' }}{{ activity.quantity }}
            </span>
          </div>
        </div>
      </div>

      <!-- View All Link -->
      <div class="mt-4 pt-4 border-t border-gray-200">
        <router-link
          to="/inventory/products"
          class="text-sm font-medium text-blue-600 hover:text-blue-700 flex items-center justify-center"
        >
          View All Products
          <svg
            class="ml-1 h-4 w-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            />
          </svg>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRealTimeNotifications } from '@/composables/useRealTimeNotifications'
import { format } from 'date-fns'

interface InventoryMetrics {
  total_products: number
  active_products: number
  low_stock_count: number
  total_stock_value: number
  recent_activities?: Array<{
    id: string
    description: string
    type: string
    quantity: number
    created_at: string
  }>
}

const metrics = ref<InventoryMetrics | null>(null)
const loading = ref(true)
const isRefreshing = ref(false)
const error = ref<string | null>(null)

// Real-time updates
const { connect, disconnect } = useRealTimeNotifications()

const fetchMetrics = async () => {
  try {
    loading.value = true
    error.value = null

    // TODO: Replace with actual API call
    // const response = await inventoryApi.getMetrics()
    // metrics.value = response.data

    // Mock data for demonstration
    await new Promise(resolve => setTimeout(resolve, 1000))
    metrics.value = {
      total_products: 1247,
      active_products: 1186,
      low_stock_count: 23,
      total_stock_value: 1456789.50,
      recent_activities: [
        {
          id: '1',
          description: 'Product A - Stock Adjustment',
          type: 'adjustment_in',
          quantity: 50,
          created_at: new Date().toISOString()
        },
        {
          id: '2',
          description: 'Product B - Sale',
          type: 'sale',
          quantity: -15,
          created_at: new Date(Date.now() - 3600000).toISOString()
        },
        {
          id: '3',
          description: 'Product C - Purchase',
          type: 'purchase',
          quantity: 100,
          created_at: new Date(Date.now() - 7200000).toISOString()
        }
      ]
    }
  } catch (err: any) {
    error.value = err.message || 'Failed to fetch inventory metrics'
    console.error('Failed to fetch inventory metrics:', err)
  } finally {
    loading.value = false
    isRefreshing.value = false
  }
}

const refreshData = async () => {
  isRefreshing.value = true
  await fetchMetrics()
}

const formatNumber = (value: number): string => {
  return new Intl.NumberFormat().format(value)
}

const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(value)
}

const formatDate = (dateString: string): string => {
  try {
    return format(new Date(dateString), 'MMM d, h:mm a')
  } catch {
    return dateString
  }
}

onMounted(() => {
  fetchMetrics()
})

onUnmounted(() => {
  disconnect()
})
</script>
