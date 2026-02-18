<template>
  <div
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click.self="$emit('close')"
  >
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">
          Adjust Stock
        </h3>
        <p class="text-sm text-gray-600 mt-1">
          {{ product.name }}
        </p>
      </div>

      <form
        class="px-6 py-4 space-y-4"
        @submit.prevent="submitAdjustment"
      >
        <!-- Warehouse Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Warehouse</label>
          <select
            v-model="form.warehouse_id"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
          >
            <option value="">
              Select Warehouse
            </option>
            <option
              v-for="warehouse in warehouses"
              :key="warehouse.id"
              :value="warehouse.id"
            >
              {{ warehouse.name }}
            </option>
          </select>
        </div>

        <!-- Adjustment Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Adjustment Type</label>
          <select
            v-model="form.type"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
          >
            <option value="in">
              Stock In
            </option>
            <option value="out">
              Stock Out
            </option>
            <option value="adjustment">
              Adjustment
            </option>
          </select>
        </div>

        <!-- Quantity -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
          <input
            v-model.number="form.quantity"
            type="number"
            min="1"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
            placeholder="Enter quantity"
          >
        </div>

        <!-- Reference -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Reference (Optional)</label>
          <input
            v-model="form.reference"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
            placeholder="e.g., PO-12345"
          >
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
          <textarea
            v-model="form.notes"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
            placeholder="Enter adjustment notes"
          />
        </div>

        <div
          v-if="error"
          class="text-red-600 text-sm"
        >
          {{ error }}
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3 pt-4">
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
            {{ submitting ? 'Adjusting...' : 'Adjust Stock' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { inventoryApi } from '@/api/inventory'
import type { Product, Warehouse } from '@/types/inventory'

const props = defineProps<{
  product: Product
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'adjusted'): void
}>()

const warehouses = ref<Warehouse[]>([])
const form = ref({
  warehouse_id: '',
  type: 'in',
  quantity: 1,
  reference: '',
  notes: '',
})
const submitting = ref(false)
const error = ref<string | null>(null)

const fetchWarehouses = async () => {
  try {
    const response = await inventoryApi.getWarehouses()
    warehouses.value = response.data
  } catch (err: any) {
    error.value = 'Failed to load warehouses'
  }
}

const submitAdjustment = async () => {
  try {
    submitting.value = true
    error.value = null
    
    await inventoryApi.adjustStock({
      product_id: props.product.id,
      warehouse_id: form.value.warehouse_id,
      quantity: form.value.type === 'out' ? -form.value.quantity : form.value.quantity,
      transaction_type: form.value.type === 'adjustment' ? 'ADJUSTMENT' : form.value.type === 'in' ? 'PURCHASE' : 'SALE',
      reference: form.value.reference,
      notes: form.value.notes,
    })
    
    emit('adjusted')
  } catch (err: any) {
    error.value = err.message || 'Failed to adjust stock'
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchWarehouses()
})
</script>
