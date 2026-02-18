<template>
  <div class="warehouse-form">
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">
        {{ isEdit ? 'Edit Warehouse' : 'Create Warehouse' }}
      </h1>
      <p class="mt-1 text-sm text-gray-600">
        {{ isEdit ? 'Update warehouse information' : 'Add a new warehouse to your inventory system' }}
      </p>
    </div>

    <!-- Error Alert -->
    <div
      v-if="error"
      class="mb-6 rounded-md bg-red-50 p-4"
    >
      <div class="flex">
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">
            {{ error }}
          </h3>
        </div>
        <div class="ml-auto pl-3">
          <button
            class="inline-flex text-red-400 hover:text-red-600"
            @click="error = null"
          >
            <span class="sr-only">Dismiss</span>
            <svg
              class="h-5 w-5"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clip-rule="evenodd"
              />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Form -->
    <form
      class="space-y-6"
      @submit.prevent="handleSubmit"
    >
      <!-- Basic Information Card -->
      <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Basic Information
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Warehouse Name -->
          <div class="md:col-span-2">
            <label
              for="name"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Warehouse Name <span class="text-red-500">*</span>
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="input"
              :class="{ 'border-red-500': errors.name }"
              placeholder="Enter warehouse name"
            >
            <p
              v-if="errors.name"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.name }}
            </p>
          </div>

          <!-- Warehouse Code -->
          <div>
            <label
              for="code"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Warehouse Code <span class="text-red-500">*</span>
            </label>
            <input
              id="code"
              v-model="form.code"
              type="text"
              required
              class="input"
              :class="{ 'border-red-500': errors.code }"
              placeholder="Enter warehouse code"
            >
            <p
              v-if="errors.code"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.code }}
            </p>
          </div>

          <!-- Type -->
          <div>
            <label
              for="type"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Type <span class="text-red-500">*</span>
            </label>
            <select
              id="type"
              v-model="form.type"
              required
              class="input"
              :class="{ 'border-red-500': errors.type }"
            >
              <option value="">
                Select Type
              </option>
              <option value="warehouse">
                Warehouse
              </option>
              <option value="distribution_center">
                Distribution Center
              </option>
              <option value="retail_store">
                Retail Store
              </option>
              <option value="virtual">
                Virtual
              </option>
            </select>
            <p
              v-if="errors.type"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.type }}
            </p>
          </div>

          <!-- Capacity -->
          <div>
            <label
              for="capacity"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Capacity (optional)
            </label>
            <input
              id="capacity"
              v-model.number="form.capacity"
              type="number"
              min="0"
              class="input"
              placeholder="Enter capacity"
            >
          </div>

          <!-- Active Status -->
          <div class="flex items-center">
            <input
              id="is_active"
              v-model="form.is_active"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            >
            <label
              for="is_active"
              class="ml-2 block text-sm text-gray-900"
            >
              Active
            </label>
          </div>
        </div>
      </div>

      <!-- Address Information Card -->
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
              Address
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

          <!-- State/Province -->
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
              placeholder="Enter state/province"
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
              Country
            </label>
            <input
              id="country"
              v-model="form.country"
              type="text"
              class="input"
              placeholder="Enter country"
            >
          </div>
        </div>
      </div>

      <!-- Contact Information Card -->
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

          <!-- Contact Phone -->
          <div>
            <label
              for="contact_phone"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Contact Phone
            </label>
            <input
              id="contact_phone"
              v-model="form.contact_phone"
              type="tel"
              class="input"
              placeholder="Enter contact phone"
            >
          </div>

          <!-- Email -->
          <div class="md:col-span-2">
            <label
              for="email"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Email
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              class="input"
              placeholder="Enter email address"
            >
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
          :disabled="submitting"
        >
          <span v-if="submitting">
            <svg
              class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline"
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
            {{ isEdit ? 'Updating...' : 'Creating...' }}
          </span>
          <span v-else>
            {{ isEdit ? 'Update Warehouse' : 'Create Warehouse' }}
          </span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { inventoryApi } from '@/api/inventory'
import type { WarehouseFormData } from '@/types/inventory'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => !!route.params.id)
const warehouseId = computed(() => route.params.id as string | undefined)

const form = reactive<WarehouseFormData>({
  name: '',
  code: '',
  type: 'warehouse',
  address: '',
  city: '',
  state: '',
  postal_code: '',
  country: '',
  contact_person: '',
  contact_phone: '',
  email: '',
  capacity: null,
  is_active: true,
})

const errors = reactive<Record<string, string>>({})
const error = ref<string | null>(null)
const submitting = ref(false)

const fetchWarehouse = async () => {
  if (!isEdit.value || !warehouseId.value) return
  
  try {
    const warehouse = await inventoryApi.getWarehouse(warehouseId.value)
    
    // Populate form with warehouse data
    form.name = warehouse.name
    form.code = warehouse.code
    form.type = warehouse.type
    form.address = warehouse.address || ''
    form.city = warehouse.city || ''
    form.state = warehouse.state || ''
    form.postal_code = warehouse.postal_code || ''
    form.country = warehouse.country || ''
    form.contact_person = warehouse.contact_person || ''
    form.contact_phone = warehouse.contact_phone || ''
    form.email = warehouse.email || ''
    form.capacity = warehouse.capacity || null
    form.is_active = warehouse.is_active
  } catch (err: any) {
    console.error('Failed to fetch warehouse:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load warehouse details'
  }
}

const validateForm = (): boolean => {
  // Clear previous errors
  Object.keys(errors).forEach(key => delete errors[key])
  
  let isValid = true
  
  if (!form.name.trim()) {
    errors.name = 'Warehouse name is required'
    isValid = false
  }
  
  if (!form.code.trim()) {
    errors.code = 'Warehouse code is required'
    isValid = false
  }
  
  if (!form.type) {
    errors.type = 'Warehouse type is required'
    isValid = false
  }
  
  return isValid
}

const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }
  
  submitting.value = true
  error.value = null
  
  try {
    if (isEdit.value && warehouseId.value) {
      // Update existing warehouse
      await inventoryApi.updateWarehouse(warehouseId.value, form)
      router.push({ name: 'inventory-warehouse-detail', params: { id: warehouseId.value } })
    } else {
      // Create new warehouse
      const newWarehouse = await inventoryApi.createWarehouse(form)
      router.push({ name: 'inventory-warehouse-detail', params: { id: newWarehouse.id } })
    }
  } catch (err: any) {
    console.error('Failed to save warehouse:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to save warehouse. Please try again.'
    
    // Handle validation errors from backend
    if (err.response?.data?.errors) {
      Object.assign(errors, err.response.data.errors)
    }
  } finally {
    submitting.value = false
  }
}

const goBack = () => {
  if (isEdit.value && warehouseId.value) {
    router.push({ name: 'inventory-warehouse-detail', params: { id: warehouseId.value } })
  } else {
    router.push({ name: 'inventory-warehouses' })
  }
}

onMounted(() => {
  if (isEdit.value) {
    fetchWarehouse()
  }
})
</script>

<style scoped>
.input {
  @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm;
}

.btn-primary {
  @apply inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50;
}
</style>
