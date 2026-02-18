<template>
  <div class="customer-detail">
    <!-- Loading State -->
    <div
      v-if="loading"
      class="text-center py-12"
    >
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent" />
      <p class="mt-4 text-gray-600">
        Loading customer details...
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

    <!-- Customer Details -->
    <div v-else-if="customer">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div class="flex-1">
          <div class="flex items-center space-x-4">
            <h1 class="text-2xl font-bold text-gray-900">
              {{ customer.customer_name }}
            </h1>
            <span
              :class="getStatusClass(customer.is_active)"
              class="rounded-full px-3 py-1 text-sm font-medium"
            >
              {{ customer.is_active ? 'Active' : 'Inactive' }}
            </span>
            <span
              :class="getTierClass(customer.customer_tier)"
              class="rounded-full px-3 py-1 text-sm font-medium"
            >
              {{ customer.customer_tier.toUpperCase() }}
            </span>
          </div>
          <p class="mt-1 text-sm text-gray-600">
            Customer Code: {{ customer.customer_code || 'N/A' }}
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
            class="btn-primary"
            @click="editCustomer"
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
          <!-- Contact Information Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Contact Information
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Email
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  <a
                    :href="`mailto:${customer.email}`"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    {{ customer.email || 'N/A' }}
                  </a>
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Phone
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ customer.phone || 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Mobile
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ customer.mobile || 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Fax
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ customer.fax || 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Website
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  <a
                    v-if="customer.website"
                    :href="customer.website"
                    target="_blank"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    {{ customer.website }}
                  </a>
                  <span v-else>N/A</span>
                </dd>
              </div>
            </dl>
          </div>

          <!-- Billing Address Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Billing Address
            </h2>
            <div class="text-sm text-gray-900">
              <p v-if="customer.billing_address_line1">
                {{ customer.billing_address_line1 }}
              </p>
              <p v-if="customer.billing_address_line2">
                {{ customer.billing_address_line2 }}
              </p>
              <p v-if="customer.billing_city || customer.billing_state || customer.billing_postal_code">
                {{ [customer.billing_city, customer.billing_state, customer.billing_postal_code].filter(Boolean).join(', ') }}
              </p>
              <p v-if="customer.billing_country">
                {{ customer.billing_country }}
              </p>
              <p
                v-if="!customer.billing_address_line1 && !customer.billing_city && !customer.billing_country"
                class="text-gray-500"
              >
                No billing address information available
              </p>
            </div>
          </div>

          <!-- Shipping Address Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Shipping Address
            </h2>
            <div class="text-sm text-gray-900">
              <p v-if="customer.shipping_address_line1">
                {{ customer.shipping_address_line1 }}
              </p>
              <p v-if="customer.shipping_address_line2">
                {{ customer.shipping_address_line2 }}
              </p>
              <p v-if="customer.shipping_city || customer.shipping_state || customer.shipping_postal_code">
                {{ [customer.shipping_city, customer.shipping_state, customer.shipping_postal_code].filter(Boolean).join(', ') }}
              </p>
              <p v-if="customer.shipping_country">
                {{ customer.shipping_country }}
              </p>
              <p
                v-if="!customer.shipping_address_line1 && !customer.shipping_city && !customer.shipping_country"
                class="text-gray-500"
              >
                No shipping address information available
              </p>
            </div>
          </div>

          <!-- Business Information Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Business Information
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Tax ID / VAT
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ customer.tax_id || 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Currency
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ customer.preferred_currency || 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Payment Terms
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ customer.payment_terms || 'N/A' }} {{ customer.payment_term_days ? `(${customer.payment_term_days} days)` : '' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Credit Limit
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ customer.credit_limit ? formatCurrency(customer.credit_limit) : 'N/A' }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Statistics Card -->
          <div
            v-if="statistics"
            class="bg-white shadow-sm rounded-lg p-6"
          >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Customer Statistics
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Total Orders
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ statistics.total_orders }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Total Revenue
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatCurrency(statistics.total_revenue) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Average Order Value
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatCurrency(statistics.average_order_value) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Outstanding Balance
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatCurrency(statistics.outstanding_balance) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Available Credit
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatCurrency(statistics.available_credit) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Credit Utilization
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ (statistics.credit_utilization * 100).toFixed(2) }}%
                </dd>
              </div>
            </dl>
          </div>

          <!-- Notes Card -->
          <div
            v-if="customer.notes"
            class="bg-white shadow-sm rounded-lg p-6"
          >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Notes
            </h2>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">
              {{ customer.notes }}
            </p>
          </div>
        </div>

        <!-- Right Column - Actions & Stats -->
        <div class="space-y-6">
          <!-- Quick Actions Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Quick Actions
            </h2>
            <div class="space-y-2">
              <button
                class="w-full btn-secondary justify-center"
                @click="createSalesOrder"
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
                New Sales Order
              </button>
              <button
                v-if="!customer.is_active"
                class="w-full btn-success justify-center"
                @click="activateCustomer"
              >
                Activate Customer
              </button>
              <button
                v-else
                class="w-full btn-warning justify-center"
                @click="deactivateCustomer"
              >
                Deactivate Customer
              </button>
              <button
                class="w-full btn-danger justify-center"
                @click="deleteCustomer"
              >
                Delete Customer
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
                  {{ formatDate(customer.created_at) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Last Updated
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDate(customer.updated_at) }}
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
import type { Customer, CustomerStatistics } from '@/api/sales'

const router = useRouter()
const route = useRoute()

const loading = ref(false)
const error = ref<string | null>(null)
const customer = ref<Customer | null>(null)
const statistics = ref<CustomerStatistics | null>(null)

const customerId = route.params.id as string

const fetchCustomer = async () => {
  loading.value = true
  error.value = null
  
  try {
    customer.value = await salesApi.getCustomer(customerId)
    
    // Fetch customer statistics
    try {
      statistics.value = await salesApi.getCustomerStatistics(customerId)
    } catch (err) {
      console.warn('Failed to fetch customer statistics:', err)
    }
  } catch (err: any) {
    console.error('Failed to fetch customer:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load customer details.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'sales-customers' })
}

const editCustomer = () => {
  router.push({ name: 'sales-customer-edit', params: { id: customerId } })
}

const createSalesOrder = () => {
  router.push({
    name: 'sales-order-create',
    query: { customer_id: customerId }
  })
}

const activateCustomer = async () => {
  if (!confirm('Are you sure you want to activate this customer?')) return
  
  try {
    await salesApi.activateCustomer(customerId)
    await fetchCustomer()
  } catch (err: any) {
    console.error('Failed to activate customer:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to activate customer.'
  }
}

const deactivateCustomer = async () => {
  if (!confirm('Are you sure you want to deactivate this customer?')) return
  
  try {
    await salesApi.deactivateCustomer(customerId)
    await fetchCustomer()
  } catch (err: any) {
    console.error('Failed to deactivate customer:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to deactivate customer.'
  }
}

const deleteCustomer = async () => {
  if (!confirm('Are you sure you want to delete this customer? This action cannot be undone.')) return
  
  try {
    await salesApi.deleteCustomer(customerId)
    router.push({ name: 'sales-customers' })
  } catch (err: any) {
    console.error('Failed to delete customer:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to delete customer.'
  }
}

const getStatusClass = (isActive: boolean) => {
  return isActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
}

const getTierClass = (tier: string) => {
  const classes: Record<string, string> = {
    standard: 'bg-gray-100 text-gray-800',
    premium: 'bg-blue-100 text-blue-800',
    vip: 'bg-purple-100 text-purple-800',
  }
  return classes[tier] || 'bg-gray-100 text-gray-800'
}

const formatDate = (dateString: string) => {
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
    currency: customer.value?.preferred_currency || 'USD'
  }).format(amount)
}

onMounted(() => {
  fetchCustomer()
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
  @apply inline-flex items-center rounded-md bg-yellow-600 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2;
}

.btn-danger {
  @apply inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2;
}
</style>
