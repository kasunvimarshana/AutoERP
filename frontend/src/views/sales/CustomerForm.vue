<template>
  <div class="customer-form">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          {{ isEdit ? 'Edit Customer' : 'Create Customer' }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          {{ isEdit ? 'Update customer information' : 'Add a new customer to your system' }}
        </p>
      </div>
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

    <!-- Error Alert -->
    <div
      v-if="error"
      class="mb-4 rounded-md bg-red-50 p-4"
    >
      <div class="flex">
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">
            {{ error }}
          </h3>
        </div>
      </div>
    </div>

    <!-- Form -->
    <form
      class="space-y-6"
      @submit.prevent="handleSubmit"
    >
      <!-- Basic Information -->
      <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Basic Information
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Customer Name -->
          <div class="md:col-span-2">
            <label
              for="customer_name"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Customer Name <span class="text-red-500">*</span>
            </label>
            <input
              id="customer_name"
              v-model="form.customer_name"
              type="text"
              required
              class="input"
              :class="{ 'border-red-500': errors.customer_name }"
              placeholder="Enter customer name"
            >
            <p
              v-if="errors.customer_name"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.customer_name }}
            </p>
          </div>

          <!-- Email -->
          <div>
            <label
              for="email"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Email <span class="text-red-500">*</span>
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              class="input"
              :class="{ 'border-red-500': errors.email }"
              placeholder="customer@example.com"
            >
            <p
              v-if="errors.email"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.email }}
            </p>
          </div>

          <!-- Customer Tier -->
          <div>
            <label
              for="customer_tier"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Customer Tier
            </label>
            <select
              id="customer_tier"
              v-model="form.customer_tier"
              class="input"
            >
              <option value="standard">
                Standard
              </option>
              <option value="premium">
                Premium
              </option>
              <option value="vip">
                VIP
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Contact Information -->
      <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Contact Information
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Phone -->
          <div>
            <label
              for="phone"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Phone
            </label>
            <input
              id="phone"
              v-model="form.phone"
              type="tel"
              class="input"
              placeholder="+1 234 567 8900"
            >
          </div>

          <!-- Mobile -->
          <div>
            <label
              for="mobile"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Mobile
            </label>
            <input
              id="mobile"
              v-model="form.mobile"
              type="tel"
              class="input"
              placeholder="+1 234 567 8900"
            >
          </div>

          <!-- Fax -->
          <div>
            <label
              for="fax"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Fax
            </label>
            <input
              id="fax"
              v-model="form.fax"
              type="tel"
              class="input"
              placeholder="+1 234 567 8900"
            >
          </div>

          <!-- Website -->
          <div>
            <label
              for="website"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Website
            </label>
            <input
              id="website"
              v-model="form.website"
              type="url"
              class="input"
              placeholder="https://example.com"
            >
          </div>
        </div>
      </div>

      <!-- Billing Address -->
      <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Billing Address
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Billing Address Line 1 -->
          <div class="md:col-span-2">
            <label
              for="billing_address_line1"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Address Line 1
            </label>
            <input
              id="billing_address_line1"
              v-model="form.billing_address_line1"
              type="text"
              class="input"
              placeholder="Enter billing address"
            >
          </div>

          <!-- Billing Address Line 2 -->
          <div class="md:col-span-2">
            <label
              for="billing_address_line2"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Address Line 2
            </label>
            <input
              id="billing_address_line2"
              v-model="form.billing_address_line2"
              type="text"
              class="input"
              placeholder="Apartment, suite, etc. (optional)"
            >
          </div>

          <!-- Billing City -->
          <div>
            <label
              for="billing_city"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              City
            </label>
            <input
              id="billing_city"
              v-model="form.billing_city"
              type="text"
              class="input"
              placeholder="Enter city"
            >
          </div>

          <!-- Billing State -->
          <div>
            <label
              for="billing_state"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              State/Province
            </label>
            <input
              id="billing_state"
              v-model="form.billing_state"
              type="text"
              class="input"
              placeholder="Enter state or province"
            >
          </div>

          <!-- Billing Postal Code -->
          <div>
            <label
              for="billing_postal_code"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Postal Code
            </label>
            <input
              id="billing_postal_code"
              v-model="form.billing_postal_code"
              type="text"
              class="input"
              placeholder="Enter postal code"
            >
          </div>

          <!-- Billing Country -->
          <div>
            <label
              for="billing_country"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Country
            </label>
            <input
              id="billing_country"
              v-model="form.billing_country"
              type="text"
              class="input"
              placeholder="Enter country"
            >
          </div>
        </div>
      </div>

      <!-- Shipping Address -->
      <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Shipping Address
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Shipping Address Line 1 -->
          <div class="md:col-span-2">
            <label
              for="shipping_address_line1"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Address Line 1
            </label>
            <input
              id="shipping_address_line1"
              v-model="form.shipping_address_line1"
              type="text"
              class="input"
              placeholder="Enter shipping address"
            >
          </div>

          <!-- Shipping Address Line 2 -->
          <div class="md:col-span-2">
            <label
              for="shipping_address_line2"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Address Line 2
            </label>
            <input
              id="shipping_address_line2"
              v-model="form.shipping_address_line2"
              type="text"
              class="input"
              placeholder="Apartment, suite, etc. (optional)"
            >
          </div>

          <!-- Shipping City -->
          <div>
            <label
              for="shipping_city"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              City
            </label>
            <input
              id="shipping_city"
              v-model="form.shipping_city"
              type="text"
              class="input"
              placeholder="Enter city"
            >
          </div>

          <!-- Shipping State -->
          <div>
            <label
              for="shipping_state"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              State/Province
            </label>
            <input
              id="shipping_state"
              v-model="form.shipping_state"
              type="text"
              class="input"
              placeholder="Enter state or province"
            >
          </div>

          <!-- Shipping Postal Code -->
          <div>
            <label
              for="shipping_postal_code"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Postal Code
            </label>
            <input
              id="shipping_postal_code"
              v-model="form.shipping_postal_code"
              type="text"
              class="input"
              placeholder="Enter postal code"
            >
          </div>

          <!-- Shipping Country -->
          <div>
            <label
              for="shipping_country"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Country
            </label>
            <input
              id="shipping_country"
              v-model="form.shipping_country"
              type="text"
              class="input"
              placeholder="Enter country"
            >
          </div>
        </div>
      </div>

      <!-- Business Information -->
      <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Business Information
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Tax ID -->
          <div>
            <label
              for="tax_id"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Tax ID / VAT Number
            </label>
            <input
              id="tax_id"
              v-model="form.tax_id"
              type="text"
              class="input"
              placeholder="Enter tax ID"
            >
          </div>

          <!-- Preferred Currency -->
          <div>
            <label
              for="preferred_currency"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Preferred Currency
            </label>
            <input
              id="preferred_currency"
              v-model="form.preferred_currency"
              type="text"
              maxlength="3"
              class="input"
              placeholder="USD"
            >
          </div>

          <!-- Payment Terms -->
          <div>
            <label
              for="payment_terms"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Payment Terms
            </label>
            <input
              id="payment_terms"
              v-model="form.payment_terms"
              type="text"
              class="input"
              placeholder="e.g., Net 30, Net 60"
            >
          </div>

          <!-- Payment Term Days -->
          <div>
            <label
              for="payment_term_days"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Payment Term Days
            </label>
            <input
              id="payment_term_days"
              v-model.number="form.payment_term_days"
              type="number"
              min="0"
              class="input"
              placeholder="30"
            >
          </div>

          <!-- Credit Limit -->
          <div>
            <label
              for="credit_limit"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Credit Limit
            </label>
            <input
              id="credit_limit"
              v-model.number="form.credit_limit"
              type="number"
              step="0.01"
              min="0"
              class="input"
              placeholder="0.00"
            >
          </div>

          <!-- Notes -->
          <div class="md:col-span-2">
            <label
              for="notes"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Notes
            </label>
            <textarea
              id="notes"
              v-model="form.notes"
              rows="4"
              class="input"
              placeholder="Enter any additional notes about this customer"
            />
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="flex items-center justify-end space-x-4">
        <button
          type="button"
          class="btn-secondary"
          @click="goBack"
        >
          Cancel
        </button>
        <button
          type="submit"
          class="btn-primary"
          :disabled="loading"
        >
          <span
            v-if="loading"
            class="flex items-center"
          >
            <svg
              class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              />
            </svg>
            Saving...
          </span>
          <span v-else>
            {{ isEdit ? 'Update Customer' : 'Create Customer' }}
          </span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { salesApi } from '@/api/sales'

const router = useRouter()
const route = useRoute()

const loading = ref(false)
const error = ref<string | null>(null)
const errors = reactive<Record<string, string>>({})

const isEdit = computed(() => !!route.params.id)
const customerId = computed(() => route.params.id as string)

const form = reactive({
  customer_name: '',
  email: '',
  phone: '',
  mobile: '',
  fax: '',
  website: '',
  tax_id: '',
  customer_tier: 'standard' as 'standard' | 'premium' | 'vip',
  payment_terms: '',
  payment_term_days: 0,
  credit_limit: 0,
  preferred_currency: 'USD',
  billing_address_line1: '',
  billing_address_line2: '',
  billing_city: '',
  billing_state: '',
  billing_country: '',
  billing_postal_code: '',
  shipping_address_line1: '',
  shipping_address_line2: '',
  shipping_city: '',
  shipping_state: '',
  shipping_country: '',
  shipping_postal_code: '',
  notes: '',
})

const fetchCustomer = async () => {
  if (!isEdit.value) return

  loading.value = true
  error.value = null
  
  try {
    const customer = await salesApi.getCustomer(customerId.value)
    
    // Populate form with customer data
    Object.assign(form, {
      customer_name: customer.customer_name || '',
      email: customer.email || '',
      phone: customer.phone || '',
      mobile: customer.mobile || '',
      fax: customer.fax || '',
      website: customer.website || '',
      tax_id: customer.tax_id || '',
      customer_tier: customer.customer_tier || 'standard',
      payment_terms: customer.payment_terms || '',
      payment_term_days: customer.payment_term_days || 0,
      credit_limit: customer.credit_limit || 0,
      preferred_currency: customer.preferred_currency || 'USD',
      billing_address_line1: customer.billing_address_line1 || '',
      billing_address_line2: customer.billing_address_line2 || '',
      billing_city: customer.billing_city || '',
      billing_state: customer.billing_state || '',
      billing_country: customer.billing_country || '',
      billing_postal_code: customer.billing_postal_code || '',
      shipping_address_line1: customer.shipping_address_line1 || '',
      shipping_address_line2: customer.shipping_address_line2 || '',
      shipping_city: customer.shipping_city || '',
      shipping_state: customer.shipping_state || '',
      shipping_country: customer.shipping_country || '',
      shipping_postal_code: customer.shipping_postal_code || '',
      notes: customer.notes || '',
    })
  } catch (err: any) {
    console.error('Failed to fetch customer:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load customer data.'
  } finally {
    loading.value = false
  }
}

const handleSubmit = async () => {
  // Clear previous errors
  Object.keys(errors).forEach(key => delete errors[key])
  error.value = null
  loading.value = true

  try {
    const payload = {
      customer_name: form.customer_name,
      email: form.email,
      phone: form.phone || undefined,
      mobile: form.mobile || undefined,
      fax: form.fax || undefined,
      website: form.website || undefined,
      tax_id: form.tax_id || undefined,
      customer_tier: form.customer_tier,
      payment_terms: form.payment_terms || undefined,
      payment_term_days: form.payment_term_days || undefined,
      credit_limit: form.credit_limit || undefined,
      preferred_currency: form.preferred_currency || undefined,
      billing_address_line1: form.billing_address_line1 || undefined,
      billing_address_line2: form.billing_address_line2 || undefined,
      billing_city: form.billing_city || undefined,
      billing_state: form.billing_state || undefined,
      billing_country: form.billing_country || undefined,
      billing_postal_code: form.billing_postal_code || undefined,
      shipping_address_line1: form.shipping_address_line1 || undefined,
      shipping_address_line2: form.shipping_address_line2 || undefined,
      shipping_city: form.shipping_city || undefined,
      shipping_state: form.shipping_state || undefined,
      shipping_country: form.shipping_country || undefined,
      shipping_postal_code: form.shipping_postal_code || undefined,
      notes: form.notes || undefined,
    }

    if (isEdit.value) {
      await salesApi.updateCustomer(customerId.value, payload)
    } else {
      await salesApi.createCustomer(payload)
    }

    // Navigate back to customers list
    router.push({ name: 'sales-customers' })
  } catch (err: any) {
    console.error('Failed to save customer:', err)
    
    // Handle validation errors
    if (err.response?.data?.errors) {
      Object.assign(errors, err.response.data.errors)
    }
    
    error.value = err.response?.data?.message || err.message || 'Failed to save customer. Please try again.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'sales-customers' })
}

onMounted(() => {
  if (isEdit.value) {
    fetchCustomer()
  }
})
</script>

<style scoped>
.input {
  @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm;
}

.btn-primary {
  @apply inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2;
}
</style>
