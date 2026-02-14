<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Stock Adjustment</h1>
      <p class="mt-1 text-sm text-gray-600">
        Adjust inventory levels for products
      </p>
    </div>

    <div class="bg-white shadow rounded-lg">
      <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div>
            <label for="product" class="block text-sm font-medium text-gray-700">
              Product *
            </label>
            <select
              id="product"
              v-model="form.product_id"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
              @change="handleProductChange"
            >
              <option value="">Select a product</option>
              <option v-for="product in products" :key="product.id" :value="product.id">
                {{ product.name }} - {{ product.sku }}
              </option>
            </select>
            <p v-if="errors.product_id" class="mt-1 text-sm text-red-600">{{ errors.product_id }}</p>
          </div>

          <div>
            <label for="branch" class="block text-sm font-medium text-gray-700">
              Branch
            </label>
            <select
              id="branch"
              v-model="form.branch_id"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            >
              <option value="">Main Branch</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Current Stock
            </label>
            <div class="text-2xl font-bold text-gray-900">
              {{ currentStock }}
            </div>
          </div>

          <div>
            <label for="adjustment_type" class="block text-sm font-medium text-gray-700">
              Adjustment Type *
            </label>
            <select
              id="adjustment_type"
              v-model="form.adjustment_type"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            >
              <option value="increase">Increase Stock</option>
              <option value="decrease">Decrease Stock</option>
              <option value="set">Set Stock Level</option>
            </select>
          </div>

          <div>
            <label for="quantity" class="block text-sm font-medium text-gray-700">
              Quantity *
            </label>
            <input
              id="quantity"
              v-model="form.quantity"
              type="number"
              required
              min="1"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
              :class="{ 'border-red-300': errors.quantity }"
            />
            <p v-if="errors.quantity" class="mt-1 text-sm text-red-600">{{ errors.quantity }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              New Stock Level
            </label>
            <div class="text-2xl font-bold" :class="newStockClass">
              {{ newStockLevel }}
            </div>
          </div>

          <div class="sm:col-span-2">
            <label for="reason" class="block text-sm font-medium text-gray-700">
              Reason *
            </label>
            <select
              id="reason"
              v-model="form.reason"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            >
              <option value="">Select a reason</option>
              <option value="purchase">Purchase/Receiving</option>
              <option value="sale">Sale</option>
              <option value="return">Return</option>
              <option value="damage">Damage/Loss</option>
              <option value="correction">Inventory Correction</option>
              <option value="transfer">Transfer</option>
              <option value="other">Other</option>
            </select>
            <p v-if="errors.reason" class="mt-1 text-sm text-red-600">{{ errors.reason }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="notes" class="block text-sm font-medium text-gray-700">
              Notes
            </label>
            <textarea
              id="notes"
              v-model="form.notes"
              rows="3"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
              placeholder="Additional information about this adjustment..."
            ></textarea>
          </div>
        </div>

        <div v-if="error" class="rounded-md bg-red-50 p-4">
          <p class="text-sm text-red-800">{{ error }}</p>
        </div>

        <div class="flex justify-end space-x-3">
          <Button variant="outline" type="button" @click="$router.back()">
            Cancel
          </Button>
          <Button type="submit" :loading="loading">
            Apply Adjustment
          </Button>
        </div>
      </form>
    </div>

    <div v-if="recentAdjustments.length > 0" class="mt-8">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Adjustments</h2>
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <ul class="divide-y divide-gray-200">
          <li v-for="adjustment in recentAdjustments" :key="adjustment.id" class="px-6 py-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-900">{{ adjustment.product }}</p>
                <p class="text-sm text-gray-500">{{ adjustment.reason }} - {{ adjustment.quantity }} units</p>
              </div>
              <div class="text-sm text-gray-500">{{ adjustment.date }}</div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useProductStore } from '../../../stores/product';
import Button from '../../../components/Button.vue';

const router = useRouter();
const productStore = useProductStore();

const form = ref({
  product_id: '',
  branch_id: '',
  adjustment_type: 'increase',
  quantity: 1,
  reason: '',
  notes: '',
});

const loading = ref(false);
const error = ref('');
const errors = ref({});
const products = ref([]);
const currentStock = ref(0);
const recentAdjustments = ref([]);

const selectedProduct = computed(() => {
  return products.value.find(p => p.id === form.value.product_id);
});

const newStockLevel = computed(() => {
  const qty = parseInt(form.value.quantity) || 0;
  const current = currentStock.value;
  
  switch (form.value.adjustment_type) {
    case 'increase':
      return current + qty;
    case 'decrease':
      return Math.max(0, current - qty);
    case 'set':
      return qty;
    default:
      return current;
  }
});

const newStockClass = computed(() => {
  const minStock = selectedProduct.value?.min_stock || 10;
  const newLevel = newStockLevel.value;
  
  if (newLevel === 0) return 'text-red-600';
  if (newLevel <= minStock) return 'text-yellow-600';
  return 'text-green-600';
});

onMounted(async () => {
  try {
    const response = await productStore.fetchProducts({ per_page: 100 });
    products.value = response.data;
  } catch (err) {
    error.value = 'Failed to load products';
  }
});

const handleProductChange = () => {
  const product = products.value.find(p => p.id === form.value.product_id);
  currentStock.value = product?.stock || 0;
};

const handleSubmit = async () => {
  loading.value = true;
  error.value = '';
  errors.value = {};

  try {
    // In real implementation, this would call an inventory adjustment API
    // For now, we update the product stock
    const newStock = newStockLevel.value;
    await productStore.updateProduct(form.value.product_id, {
      stock: newStock,
    });
    
    router.push({ name: 'inventory' });
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors;
    } else {
      error.value = err.response?.data?.message || 'Failed to apply adjustment';
    }
  } finally {
    loading.value = false;
  }
};
</script>
