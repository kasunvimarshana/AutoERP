<template>
  <div class="supplier-form">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          {{ isEdit ? 'Edit Supplier' : 'Create Supplier' }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          {{ isEdit ? 'Update supplier information' : 'Add a new supplier to your system' }}
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
          <!-- Name -->
          <div class="md:col-span-2">
            <label
              for="name"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Supplier Name <span class="text-red-500">*</span>
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="input"
              :class="{ 'border-red-500': errors.name }"
              placeholder="Enter supplier name"
            >
            <p
              v-if="errors.name"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.name }}
            </p>
          </div>

          <!-- Code -->
          <div>
            <label
              for="code"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Supplier Code
            </label>
            <input
              id="code"
              v-model="form.code"
              type="text"
              class="input"
              :class="{ 'border-red-500': errors.code }"
              placeholder="Auto-generated if empty"
            >
            <p
              v-if="errors.code"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.code }}
            </p>
          </div>

          <!-- Status -->
          <div>
            <label
              for="status"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Status
            </label>
            <select
              id="status"
              v-model="form.status"
              class="input"
            >
              <option value="active">
                Active
              </option>
              <option value="suspended">
                Suspended
              </option>
              <option value="blocked">
                Blocked
              </option>
            </select>
          </div>

          <!-- Rating -->
          <div>
            <label
              for="rating"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Rating (1-5)
            </label>
            <input
              id="rating"
              v-model.number="form.rating"
              type="number"
              min="1"
              max="5"
              class="input"
              placeholder="Enter rating"
            >
          </div>
        </div>
      </div>

      <!-- Contact Information -->
      <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Contact Information
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Contact Person -->
          <div>
            <label
              for="contact_person"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Contact Person
            </label>
            <input
              id="contact_person"
              v-model="form.contact_person"
              type="text"
              class="input"
              placeholder="Enter contact person name"
            >
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
              placeholder="supplier@example.com"
            >
            <p
              v-if="errors.email"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.email }}
            </p>
          </div>

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
        </div>
      </div>

      <!-- Address Information -->
      <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Address Information
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Address -->
          <div class="md:col-span-2">
            <label
              for="address"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Street Address
            </label>
            <textarea
              id="address"
              v-model="form.address"
              rows="3"
              class="input"
              placeholder="Enter street address"
            />
          </div>

          <!-- City -->
          <div>
            <label
              for="city"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              City
            </label>
            <input
              id="city"
              v-model="form.city"
              type="text"
              class="input"
              placeholder="Enter city"
            >
          </div>

          <!-- State -->
          <div>
            <label
              for="state"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              State/Province
            </label>
            <input
              id="state"
              v-model="form.state"
              type="text"
              class="input"
              placeholder="Enter state or province"
            >
          </div>

          <!-- Postal Code -->
          <div>
            <label
              for="postal_code"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Postal Code
            </label>
            <input
              id="postal_code"
              v-model="form.postal_code"
              type="text"
              class="input"
              placeholder="Enter postal code"
            >
          </div>

          <!-- Country -->
          <div>
            <label
              for="country"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Country <span class="text-red-500">*</span>
            </label>
            <input
              id="country"
              v-model="form.country"
              type="text"
              required
              class="input"
              :class="{ 'border-red-500': errors.country }"
              placeholder="Enter country"
            >
            <p
              v-if="errors.country"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.country }}
            </p>
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

          <!-- Currency Code -->
          <div>
            <label
              for="currency_code"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Currency Code
            </label>
            <input
              id="currency_code"
              v-model="form.currency_code"
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
              placeholder="Enter any additional notes about this supplier"
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
            {{ isEdit ? 'Update Supplier' : 'Create Supplier' }}
          </span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { purchasingApi } from '@/api/purchasing'

const router = useRouter()
const route = useRoute()

const loading = ref(false)
const error = ref<string | null>(null)
const errors = reactive<Record<string, string>>({})

const isEdit = computed(() => !!route.params.id)
const supplierId = computed(() => route.params.id as string)

const form = reactive({
  name: '',
  code: '',
  contact_person: '',
  email: '',
  phone: '',
  mobile: '',
  address: '',
  city: '',
  state: '',
  country: '',
  postal_code: '',
  tax_id: '',
  payment_terms: '',
  credit_limit: 0,
  currency_code: 'USD',
  notes: '',
  status: 'active',
  rating: 0,
})

const fetchSupplier = async () => {
  if (!isEdit.value) return

  loading.value = true
  error.value = null
  
  try {
    const supplier = await purchasingApi.getSupplier(supplierId.value)
    
    // Populate form with supplier data
    Object.assign(form, {
      name: supplier.name || '',
      code: supplier.code || '',
      contact_person: supplier.contact_person || '',
      email: supplier.email || '',
      phone: supplier.phone || '',
      mobile: supplier.mobile || '',
      address: supplier.address || '',
      city: supplier.city || '',
      state: supplier.state || '',
      country: supplier.country || '',
      postal_code: supplier.postal_code || '',
      tax_id: supplier.tax_id || '',
      payment_terms: supplier.payment_terms || '',
      credit_limit: supplier.credit_limit || 0,
      currency_code: supplier.currency_code || 'USD',
      notes: supplier.notes || '',
      status: supplier.status || 'active',
      rating: supplier.rating || 0,
    })
  } catch (err: any) {
    console.error('Failed to fetch supplier:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load supplier data.'
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
      name: form.name,
      code: form.code || undefined,
      contact_person: form.contact_person || undefined,
      email: form.email,
      phone: form.phone || undefined,
      mobile: form.mobile || undefined,
      address: form.address || undefined,
      city: form.city || undefined,
      state: form.state || undefined,
      country: form.country,
      postal_code: form.postal_code || undefined,
      tax_id: form.tax_id || undefined,
      payment_terms: form.payment_terms || undefined,
      credit_limit: form.credit_limit || undefined,
      currency_code: form.currency_code || undefined,
      notes: form.notes || undefined,
      status: form.status,
      rating: form.rating || undefined,
    }

    if (isEdit.value) {
      await purchasingApi.updateSupplier(supplierId.value, payload)
    } else {
      await purchasingApi.createSupplier(payload)
    }

    // Navigate back to suppliers list
    router.push({ name: 'purchasing-suppliers' })
  } catch (err: any) {
    console.error('Failed to save supplier:', err)
    
    // Handle validation errors
    if (err.response?.data?.errors) {
      Object.assign(errors, err.response.data.errors)
    }
    
    error.value = err.response?.data?.message || err.message || 'Failed to save supplier. Please try again.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.back()
}

onMounted(() => {
  if (isEdit.value) {
    fetchSupplier()
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
