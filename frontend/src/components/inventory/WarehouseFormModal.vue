<template>
  <div
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click.self="$emit('close')"
  >
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
      <div class="px-6 py-4 border-b border-gray-200 sticky top-0 bg-white">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ warehouse ? 'Edit Warehouse' : 'Add New Warehouse' }}
        </h3>
      </div>

      <form
        class="px-6 py-4 space-y-4"
        @submit.prevent="submitForm"
      >
        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Warehouse Name <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
            placeholder="e.g., Main Warehouse"
          >
        </div>

        <!-- Code -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Warehouse Code <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.code"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
            placeholder="e.g., WH-001"
          >
        </div>

        <!-- Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
          <select
            v-model="form.type"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
          >
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
        </div>

        <!-- Address -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
          <textarea
            v-model="form.address"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
            placeholder="Enter warehouse address"
          />
        </div>

        <!-- Contact Person -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
            <input
              v-model="form.contact_person"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
              placeholder="Enter contact person"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
            <input
              v-model="form.contact_phone"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
              placeholder="Enter phone number"
            >
          </div>
        </div>

        <!-- Email -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input
            v-model="form.email"
            type="email"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
            placeholder="warehouse@example.com"
          >
        </div>

        <!-- Capacity -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Capacity (sq ft)</label>
          <input
            v-model.number="form.capacity"
            type="number"
            min="0"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
            placeholder="Enter warehouse capacity"
          >
        </div>

        <!-- Active Status -->
        <div class="flex items-center">
          <input
            id="is_active"
            v-model="form.is_active"
            type="checkbox"
            class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
          >
          <label
            for="is_active"
            class="ml-2 block text-sm text-gray-700"
          >
            Active
          </label>
        </div>

        <div
          v-if="error"
          class="text-red-600 text-sm"
        >
          {{ error }}
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
          <button
            type="button"
            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition"
            @click="$emit('close')"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="submitting"
            class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 transition disabled:opacity-50"
          >
            {{ submitting ? 'Saving...' : 'Save Warehouse' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { inventoryApi } from '@/api/inventory'
import type { Warehouse, WarehouseFormData, WarehouseType } from '@/types/inventory'

const props = defineProps<{
  warehouse?: Warehouse
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved'): void
}>()

const form = ref<WarehouseFormData>({
  name: '',
  code: '',
  type: 'warehouse' as WarehouseType,
  address: '',
  contact_person: '',
  contact_phone: '',
  email: '',
  capacity: null,
  is_active: true,
})
const submitting = ref(false)
const error = ref<string | null>(null)

const initializeForm = () => {
  if (props.warehouse) {
    form.value = {
      name: props.warehouse.name || '',
      code: props.warehouse.code || '',
      type: props.warehouse.type || 'warehouse',
      address: props.warehouse.address || '',
      contact_person: props.warehouse.contact_person || '',
      contact_phone: props.warehouse.contact_phone || '',
      email: props.warehouse.email || '',
      capacity: props.warehouse.capacity || null,
      is_active: props.warehouse.is_active !== false,
    }
  }
}

const submitForm = async () => {
  try {
    submitting.value = true
    error.value = null
    
    if (props.warehouse) {
      await inventoryApi.updateWarehouse(props.warehouse.id, form.value)
    } else {
      await inventoryApi.createWarehouse(form.value)
    }
    
    emit('saved')
  } catch (err: any) {
    error.value = err.message || 'Failed to save warehouse'
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  initializeForm()
})
</script>
