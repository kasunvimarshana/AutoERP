<template>
  <div class="supplier-detail">
    <!-- Loading State -->
    <div
      v-if="loading"
      class="text-center py-12"
    >
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent" />
      <p class="mt-4 text-gray-600">
        Loading supplier details...
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

    <!-- Supplier Details -->
    <div v-else-if="supplier">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div class="flex-1">
          <div class="flex items-center space-x-4">
            <h1 class="text-2xl font-bold text-gray-900">
              {{ supplier.name }}
            </h1>
            <span
              :class="getStatusClass(supplier.status)"
              class="rounded-full px-3 py-1 text-sm font-medium"
            >
              {{ supplier.status || 'active' }}
            </span>
          </div>
          <p class="mt-1 text-sm text-gray-600">
            Supplier Code: {{ supplier.code || 'N/A' }}
          </p>
          <div
            v-if="supplier.rating"
            class="mt-2 flex items-center"
          >
            <span class="text-sm text-gray-600 mr-2">Rating:</span>
            <span
              v-for="n in 5"
              :key="n"
              class="text-yellow-400 text-lg"
            >
              {{ n <= (supplier.rating || 0) ? '★' : '☆' }}
            </span>
          </div>
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
            @click="editSupplier"
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
                  Contact Person
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ supplier.contact_person || 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Email
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  <a
                    :href="`mailto:${supplier.email}`"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    {{ supplier.email || 'N/A' }}
                  </a>
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Phone
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ supplier.phone || 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Mobile
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ supplier.mobile || 'N/A' }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Address Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Address
            </h2>
            <div class="text-sm text-gray-900">
              <p v-if="supplier.address">
                {{ supplier.address }}
              </p>
              <p v-if="supplier.city || supplier.state || supplier.postal_code">
                {{ [supplier.city, supplier.state, supplier.postal_code].filter(Boolean).join(', ') }}
              </p>
              <p v-if="supplier.country">
                {{ supplier.country }}
              </p>
              <p
                v-if="!supplier.address && !supplier.city && !supplier.country"
                class="text-gray-500"
              >
                No address information available
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
                  {{ supplier.tax_id || 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Currency
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ supplier.currency_code || 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Payment Terms
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ supplier.payment_terms || 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Credit Limit
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ supplier.credit_limit ? formatCurrency(supplier.credit_limit) : 'N/A' }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Notes Card -->
          <div
            v-if="supplier.notes"
            class="bg-white shadow-sm rounded-lg p-6"
          >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Notes
            </h2>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">
              {{ supplier.notes }}
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
                @click="createPurchaseOrder"
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
                New Purchase Order
              </button>
              <button
                v-if="supplier.status === 'suspended'"
                class="w-full btn-success justify-center"
                @click="activateSupplier"
              >
                Activate Supplier
              </button>
              <button
                v-else-if="supplier.status === 'active'"
                class="w-full btn-warning justify-center"
                @click="suspendSupplier"
              >
                Suspend Supplier
              </button>
              <button
                class="w-full btn-danger justify-center"
                @click="deleteSupplier"
              >
                Delete Supplier
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
                  {{ formatDate(supplier.created_at) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Last Updated
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDate(supplier.updated_at) }}
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
import { purchasingApi } from '@/api/purchasing'
import type { Supplier } from '@/types/purchasing'

const router = useRouter()
const route = useRoute()

const loading = ref(false)
const error = ref<string | null>(null)
const supplier = ref<Supplier | null>(null)

const supplierId = route.params.id as string

const fetchSupplier = async () => {
  loading.value = true
  error.value = null
  
  try {
    supplier.value = await purchasingApi.getSupplier(supplierId)
  } catch (err: any) {
    console.error('Failed to fetch supplier:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load supplier details.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'purchasing-suppliers' })
}

const editSupplier = () => {
  router.push({ name: 'purchasing-supplier-edit', params: { id: supplierId } })
}

const createPurchaseOrder = () => {
  router.push({
    name: 'purchasing-order-create',
    query: { supplier_id: supplierId }
  })
}

const activateSupplier = async () => {
  if (!confirm('Are you sure you want to activate this supplier?')) return
  
  try {
    await purchasingApi.activateSupplier(supplierId)
    await fetchSupplier()
  } catch (err: any) {
    console.error('Failed to activate supplier:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to activate supplier.'
  }
}

const suspendSupplier = async () => {
  if (!confirm('Are you sure you want to suspend this supplier?')) return
  
  try {
    await purchasingApi.suspendSupplier(supplierId)
    await fetchSupplier()
  } catch (err: any) {
    console.error('Failed to suspend supplier:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to suspend supplier.'
  }
}

const deleteSupplier = async () => {
  if (!confirm('Are you sure you want to delete this supplier? This action cannot be undone.')) return
  
  try {
    await purchasingApi.deleteSupplier(supplierId)
    router.push({ name: 'purchasing-suppliers' })
  } catch (err: any) {
    console.error('Failed to delete supplier:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to delete supplier.'
  }
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    suspended: 'bg-yellow-100 text-yellow-800',
    blocked: 'bg-red-100 text-red-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
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
    currency: supplier.value?.currency_code || 'USD'
  }).format(amount)
}

onMounted(() => {
  fetchSupplier()
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
